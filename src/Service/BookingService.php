<?php

namespace App\Service;

use App\Models\Booking;
use App\Repositories\BookingRelationsRepository;
use App\Repositories\RideWithUsersRepository;
use App\Repositories\UserRepository;
use App\Models\Ride;
use App\Models\User;
use App\Services\BaseService;
use App\Enum\BookingStatus;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

// Pas besoin de mise à jour de la date car fait dans Booking

class BookingService extends BaseService
{
    // Promotion des propriétés (depuis PHP 8)
    public function __construct(
        private BookingRelationsRepository $bookingRelationsRepository,
        private RideWithUsersRepository $rideWithUsersRepository,
        private UserRepository $userRepository,

    ) {
        parent::__construct();
    }


    //--------------VERIFICATION-----------------

    // Vérifie que le passager n'a pas déjà une réservation
    public function userHasBooking(User $user, Ride $ride): bool
    {
        $booking = $this->bookingRelationsRepository->findBookingByFields([
            'user_id' => $user->getUserId(),
            'ride_id' => $ride->getRideId()
        ]);
        return $booking !== null;
    }


    //-----------------ACTIONS------------------------------

    // Création d'une réservation - Fonction utilisé dabs bookRide
    public function createBooking(Ride $ride, User $driver, User $passenger): Booking
    {
        // Vérification de la permission
        $passengerId = $passenger->getUserId();
        $this->ensurePassenger($passengerId);

        // Vérification de l'existance des entités
        if (!$ride  || !$driver || !$passenger) {
            throw new InvalidArgumentException("Trajet, conducteur ou passager introuvable.");
        }

        // Vérification de doublon de réservation
        if ($this->userHasBooking($passenger, $ride)) {
            throw new InvalidArgumentException("L'utilisateur a déjà une réservation.");
        }

        // Vérification du remplissage du trajet
        if (!$ride->hasAvailableSeat()) {
            throw new InvalidArgumentException("Trajet complet.");
        }

        // Décrémentation du nombre de place disponible
        $ride->decrementAvailableSeats();

        // Création de Booking
        $booking = new Booking(
            ride: $ride,
            passenger: $passenger,
            driver: $driver
        );

        //Enregistrement en BD
        $this->bookingRelationsRepository->insertBooking($booking);

        return $booking;
    }

    // Permet à un utilisateur PASSAGER d'annuler une réservation.
    public function cancelBooking(int $userId, int $bookingId): void
    {

        // Récupération de l'entité Booking
        $booking = $this->bookingRelationsRepository->findBookingById($bookingId);

        // Vérification de l'existence de la réservation
        if (!$booking) {
            throw new InvalidArgumentException("Réservation introuvable.");
        }

        // Récupération de l'utilisateur
        $passengerId = $booking->getBookingPassenger()->getUserId();

        // Vérification des permissions.
        if (
            $userId !== $passengerId &&
            !$this->roleService->isAdmin($userId) &&
            !$this->roleService->isEmployee($userId)
        ) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à annuler cette réservation.");
        }


        // Vérification du status de la réservation.
        if ($booking->getBookingStatus() === BookingStatus::ANNULEE) {
            throw new InvalidArgumentException("La réservation est déjà annulée.");
        }

        // Mise à jour du status
        $booking->setBookingStatus(BookingStatus::ANNULEE);

        // Mise à jour des places disponibles
        $ride = $booking->getBookingRide();
        $ride->incrementAvailableSeats();
        $this->rideWithUsersRepository->updateRide($ride); // permet de conserver l'historique


        // Enregistrement en BD
        $this->bookingRelationsRepository->updateBooking($booking);

        // ---->> ENVOIE DE LA CONFIRMATION D'ANNULATION AU PASSAGER ET AU CONDUCTEUR
    }


    //------------------RECUPERATIONS------------------------

    // Récupére la réservation avec l'objet Ride et les objets User liés à la réservation
    public function getBookingWithRideAndUsers(int $bookingId): ?Booking
    {
        // Récupération de la réservation
        $booking = $this->bookingRelationsRepository->findBookingById($bookingId);

        // Vérification de l'existence de la réservation
        if (!$booking) {
            throw new InvalidArgumentException("Réservation introuvable.");
        }

        return $this->bookingRelationsRepository->findBookingWithRideAndUsersByBookingId($bookingId);
    }

    // Récupére la liste des réservations en fonction de la date de création pour les utilisateurs avec le rôle EMPLOYEE
    public function getBookingListByDate(DateTimeInterface $creationDate, int $employeeId)
    {
        $this->ensureEmployee($employeeId);
        return $this->bookingRelationsRepository->fetchAllBookingsByCreatedAt($creationDate);
    }
}
