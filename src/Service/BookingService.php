<?php

namespace App\Services;

use App\Repositories\BookingRepository;
use App\Repositories\RideRepository;
use App\Repositories\UserRepository;
use App\Services\NotificationService;
use App\Models\Booking;
use App\Models\Ride;
use App\Models\User;
use App\Enum\BookingStatus;
use App\Enum\RideStatus;
use App\Enum\UserRoles;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

// Pas besoin de mise à jour de la date car fait dans Booking

class BookingService extends BaseService
{

    public function __construct(
        private BookingRepository $bookingRepository,
        private RideRepository $rideRepository,
        private UserRepository $userRepository,
        private NotificationService $notificationService

    ) {
        parent::__construct();
    }


    //--------------VERIFICATION-----------------

    /**
     * Vérifie que le passager n'a pas déjà une réservation
     *
     * @param User $user
     * @param Ride $ride
     * @return boolean
     */
    public function userHasBooking(
        User $user,
        Ride $ride
    ): bool {
        $booking = $this->bookingRepository->findBookingByFields([
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
     * @return Booking|null
     */
    public function createBooking(
        Ride $ride,
        User $driver,
        User $passenger,
        int $userId
    ): ?Booking {

        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (!$this->roleService->hasAnyRole($userId, [
            UserRoles::PASSAGER,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à créer une réservation.");
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


        // Création de Booking
        $booking = new Booking();

        $booking->setBookingRide($ride);
        $booking->setBookingPassenger($passenger);
        $booking->setBookingDriver($driver);
        $booking->setBookingStatus(BookingStatus::CONFIRMEE);


        //Enregistrement en BD
        $this->bookingRepository->insertBooking($booking);


        // Décrémentation du nombre de place disponible
        $ride->decrementAvailableSeats();
        $seatsLeft = $ride->getRideAvailableSeats();

        // Modification du statut si complet
        if ($seatsLeft === 0) {
            $ride->setRideStatus(RideStatus::COMPLET);
        }

        return $booking;
    }

    /**
     * Permet à un utilisateur PASSAGER d'annuler une réservation.
     *
     * @param integer $bookingId
     * @param integer $userId
     * @return Booking|null
     */
    public function cancelBooking(
        int $bookingId,
        int $userId
    ): ?Booking {

        // Récupération de l'entité Booking
        $booking = $this->bookingRepository->findBookingById($bookingId);

        // Vérification de l'existence de la réservation
        if (!$booking) {
            throw new InvalidArgumentException("Réservation introuvable.");
        }

        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (!$this->roleService->hasAnyRole($userId, [
            UserRoles::PASSAGER,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
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
        $this->rideRepository->updateRide($ride); // permet de conserver l'historique

        // Enregistrement des modifications de réservation en BD
        $this->bookingRepository->updateBooking($booking);

        // Récupération des utilisateurs
        $passenger = $this->userRepository->findUserById($passengerId);
        $driverId = $booking->getBookingDriver()->getUserId();
        $driver = $this->userRepository->findUserById($driverId);

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
            $this->userRepository->updateUser($passenger);
        } else {

            // Envoi des confirmations avec frais
            $this->notificationService->sendBookingLateCancelationToPassenger($passenger, $booking);
            $this->notificationService->sendBookingLateCancelationToDriver($driver, $booking);
        }
        return $booking;
    }



    //------------------RECUPERATIONS------------------------



    /**
     * Récupére la liste des réservations en fonction de la date de création pour les utilisateurs de l'entreprise.
     *
     * @param DateTimeInterface $creationDate
     * @param integer $employeeId
     * @return array
     */
    public function getBookingListByDate(
        DateTimeInterface $creationDate,
        int $staffId
    ): array {

        // Récupération du membre du personnel
        $staffMember = $this->userRepository->findUserById($staffId);

        // Vérification de l'existence du membre du personnel
        if (!$staffMember) {
            throw new InvalidArgumentException("Membre du personnel introuvable.");
        }

        // Vérification de la permission
        $this->ensureStaff($staffId);

        return $this->bookingRepository->fetchAllBookingsByCreatedAt($creationDate);
    }
}
