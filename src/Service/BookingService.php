<?php

namespace App\Services;

use App\Repositories\BookingRelationsRepository;
use App\Repositories\RideWithUsersRepository;
use App\Repositories\UserRelationsRepository;
use App\Services\NotificationService;
use App\Models\Booking;
use App\Models\Ride;
use App\Models\User;
use App\Enum\BookingStatus;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

// Pas besoin de mise à jour de la date car fait dans Booking

class BookingService extends BaseService
{

    public function __construct(
        private BookingRelationsRepository $bookingRelationsRepository,
        private RideWithUsersRepository $rideWithUsersRepository,
        private UserRelationsRepository $userRelationsRepository,
        private NotificationService $notificationService

    ) {
        parent::__construct();
    }


    //--------------VERIFICATION-----------------

    // Vérifie que le passager n'a pas déjà une réservation
    public function userHasBooking(
        User $user,
        Ride $ride
    ): bool {
        $booking = $this->bookingRelationsRepository->findBookingByFields([
            'user_id' => $user->getUserId(),
            'ride_id' => $ride->getRideId()
        ]);
        return $booking !== null;
    }


    //-----------------ACTIONS------------------------------

    /**
     * Création d'une réservation - Fonction utilisé dans bookRide
     *
     * @param Ride $ride
     * @param User $driver
     * @param User $passenger
     * @param integer $userId
     * @return Booking
     */
    public function createBooking(
        Ride $ride,
        User $driver,
        User $passenger,
        int $userId
    ): Booking {

        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (
            !$this->roleService->hasRole($userId, 'PASSAGER') &&
            !$this->roleService->hasRole($userId, 'EMPLOYE') &&
            !$this->roleService->hasRole($userId, 'ADMIN')
        ) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à annuler cette réservation.");
        }


        // Vérification de l'existence des entités
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

    /**
     * Permet à un utilisateur PASSAGER d'annuler une réservation.
     *
     * @param integer $bookingId
     * @param integer $userId
     * @return Booking
     */
    public function cancelBooking(
        int $bookingId,
        int $userId
    ): Booking {

        // Récupération de l'entité Booking
        $booking = $this->bookingRelationsRepository->findBookingById($bookingId);

        // Vérification de l'existence de la réservation
        if (!$booking) {
            throw new InvalidArgumentException("Réservation introuvable.");
        }

        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (
            !$this->roleService->hasRole($userId, 'PASSAGER') &&
            !$this->roleService->hasRole($userId, 'EMPLOYE') &&
            !$this->roleService->hasRole($userId, 'ADMIN')
        ) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à annuler cette réservation.");
        }

        // Vérification que la réservation appartient au passager
        $passengerId = $booking->getBookingPassenger()->getUserId();
        if ($booking->getBookingPassengerId() !== $userId) {
            throw new InvalidArgumentException("Seulement le passager associé au trajet peut annuler sa réservation.");
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

        // Enregistrement des modifications de trajet en BD
        $this->rideWithUsersRepository->updateRide($ride); // permet de conserver l'historique

        // Enregistrement des modifications de réservation en BD
        $this->bookingRelationsRepository->updateBooking($booking);

        // Récupération des utilisateurs
        $passenger = $this->userRelationsRepository->findUserById($passengerId);
        $driverId = $booking->getBookingDriver()->getUserId();
        $driver = $this->userRelationsRepository->findUserById($driverId);

        // Préparation des variables
        $today = (new DateTimeImmutable());
        $rideDate = $ride->getRideDepartureDateTime();
        $refundableDeadLine = (clone $rideDate)->modify('-2 days');

        // Vérification des conditions d'annulation
        if ($today <= $refundableDeadLine) {

            // Remboursement
            $passenger->setUserCredits($passenger->getUserCredits() + $ride->getRidePrice());

            // Envoi des confirmations sans frais
            $this->notificationService->sendBookingCancelationToPassenger($passenger, $booking);
            $this->notificationService->sendBookingCancelationToDriver($driver, $booking);

            // Enregistrement des modifications de l'utilisateur en BD
            $this->userRelationsRepository->updateUser($passenger);
        } else {

            // Envoi des confirmations avec frais
            $this->notificationService->sendBookingLateCancelationToPassenger($passenger, $booking);
            $this->notificationService->sendBookingLateCancelationToDriver($driver, $booking);
        }
        return $booking;
    }



    //------------------RECUPERATIONS------------------------

    /**
     * Récupére la réservation avec l'objet Ride et les objets User liés à la réservation
     *
     * @param integer $bookingId
     * @param integer $employeeId
     * @return Booking|null
     */
    public function getBookingWithRideAndUsers(
        int $bookingId,
        int $employeeId
    ): ?Booking {

        // Récupération de la réservation
        $booking = $this->bookingRelationsRepository->findBookingById($bookingId);

        // Vérification de l'existence de la réservation
        if (!$booking) {
            throw new InvalidArgumentException("Réservation introuvable.");
        }


        // Récupération de l'employé
        $employee = $this->userRelationsRepository->findUserById($employeeId);

        // Vérification de l'existence de l'employé
        if (!$employee) {
            throw new InvalidArgumentException("Employé introuvable.");
        }

        // Vérification de la permission
        $this->ensureEmployee($employeeId);


        return $this->bookingRelationsRepository->findBookingWithRideAndUsersByBookingId($bookingId);
    }

    /**
     * Récupére la liste des réservations en fonction de la date de création pour les utilisateurs avec le rôle EMPLOYEE
     *
     * @param DateTimeInterface $creationDate
     * @param integer $employeeId
     * @return array
     */
    public function getBookingListByDate(
        DateTimeInterface $creationDate,
        int $employeeId
    ): array {

        // Récupération de l'employé
        $employee = $this->userRelationsRepository->findUserById($employeeId);

        // Vérification de l'existence de l'employé
        if (!$employee) {
            throw new InvalidArgumentException("Employé introuvable.");
        }

        // Vérification de la permission
        $this->ensureEmployee($employeeId);

        return $this->bookingRelationsRepository->fetchAllBookingsByCreatedAt($creationDate);
    }
}
