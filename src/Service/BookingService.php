<?php

namespace App\Service;

use App\Model\Booking;
use App\Model\Ride;
use App\Model\User;
use App\Enum\BookingStatus;
use App\Enum\RideStatus;
use App\Enum\UserRoles;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;


class BookingService extends BaseService
{


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
     * Permet à un utilisateur PASSAGER de créer une réservation.
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
        $this->ensurePassenger($userId);


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

        // vérification des crédits du passager
        if ($passenger->getUserCredits() < $ride->getRidePrice()) {
            throw new InvalidArgumentException("Crédits insuffisants.");
        }

        // Création de Booking
        $booking = new Booking();

        $booking->setBookingRide($ride);
        $booking->setBookingPassenger($passenger);
        $booking->setBookingDriver($driver);
        $booking->setBookingStatus(BookingStatus::CONFIRMEE);


        // Décrémentation du nombre de place disponible
        $ride->decrementAvailableSeats();
        $seatsLeft = $ride->getRideAvailableSeats();

        // Modification du statut si complet
        if ($seatsLeft === 0) {
            $ride->setRideStatus(RideStatus::COMPLET);
        }

        // Décrémentation les crédits du passager
        $passenger->setUserCredits($passenger->getUserCredits() - $ride->getRidePrice());


        //Enregistrement en BD
        $this->userRepository->updateUser(
            $passenger,
            [
                'credits' => $passenger->getUserCredits()
            ]
        );
        $this->bookingRepository->insertBooking($booking);
        $this->rideRepository->updateRide($ride);


        // Notification
        $this->sendRideConfirmationToPassenger($passenger, $ride);


        return $booking;
    }


    /**
     * Permet à un utilisateur PASSAGER OU EMPLOYE OU ADMIN d'annuler un réservation.
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
        if (!$this->hasAnyRole($userId, [
            UserRoles::PASSAGER,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à annuler cette réservation.");
        }

        // Vérification qu'il s'agit bien du passager
        if ($this->isPassenger($userId)) {
            if ($booking->getBookingPassengerId() !== $userId) {
                throw new InvalidArgumentException("Le conducteur ne correspond pas à ce trajet.");
            }
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
            $this->sendBookingCancelationToPassenger($passenger, $booking);
            $this->sendBookingCancelationToDriver($driver, $booking);

            // Enregistrement des modifications de l'utilisateur en BD
            $this->userRepository->updateUser($passenger);
        } else {

            // Envoi des confirmations avec frais
            $this->sendBookingLateCancelationToPassenger($passenger, $booking);
            $this->sendBookingLateCancelationToDriver($driver, $booking);
        }
        return $booking;
    }

    /**
     * Permet à un utilisateur PASSAGER OU EMPLOYE OU ADMIN de confirmer une réservation à la fin du trajet.
     *
     * @param integer $bookingId
     * @param integer $userId
     * @return void
     */
    public function finalizeBooking(
        int $bookingId,
        int $userId
    ): void {
        // Récupération de la réservation
        $booking = $this->bookingRepository->findBookingById($bookingId);

        // Vérification de l'existence de la réservation
        if (!$booking) {
            throw new InvalidArgumentException("Réservation introuvable.");
        }

        // Vérification du status de la réservation.
        if ($booking->getBookingStatus() !== BookingStatus::ENCOURS) {
            throw new InvalidArgumentException("La réservation n'a pas le statut ENCOURS.");
        }


        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (!$this->hasAnyRole($userId, [
            UserRoles::PASSAGER,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à finaliser cette réservation.");
        }

        // Vérification qu'il s'agit bien du passager
        if ($this->isPassenger($userId)) {
            if ($booking->getBookingPassengerId() !== $userId) {
                throw new InvalidArgumentException("Le conducteur ne correspond pas à ce trajet.");
            }
        }

        // Modification du statut de la réservation
        $booking->setBookingStatus(BookingStatus::PASSEE);

        // Enregistrement dans la bd.
        $this->bookingRepository->updateBooking(
            $booking,
            [
                'booking_status' => $booking->getBookingStatus()
            ]
        );
    }

    //------------------RECUPERATIONS------------------------

    /**
     * Permet à un utilisateur PASSAGER OU EMPLOYE OU ADMIN de récupèrer une réservations.
     *
     * @param integer $bookingId
     * @param integer $userId
     * @return Booking|null
     */
    public function getBooking(
        int $bookingId,
        int $userId
    ): ?Booking {
        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (!$this->hasAnyRole($userId, [
            UserRoles::PASSAGER,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }

        return $this->bookingRepository->findBookingById($bookingId);
    }


    //------- Pour les passagers passagers uniquement ---------

    /**
     * Permet à un utilisateur PASSAGER OU EMPLOYE OU ADMIN de récupèrer la liste d'objet Booking à vénir d'une utilisateur PASSAGER.
     *
     * @param integer $passengerId
     * @param integer $userId
     * @return array
     */
    public function listUpcomingBookingsByPassenger(
        int $passengerId,
        int $userId
    ): array {
        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (!$this->hasAnyRole($userId, [
            UserRoles::PASSAGER,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }


        // Récupération du passager
        $passenger = $this->userRepository->findUserById($passengerId);

        // Vérification de l'existence du passeger
        if (!$passenger) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification qu'il s'agit bien du passager
        if ($this->isPassenger($userId)) {
            if ($userId !== $passengerId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->bookingRepository->findUpcomingBookingsByPassenger($passengerId);
    }

    /**
     * Permet à un utilisateur PASSAGER OU EMPLOYE OU ADMIN de récupèrer la liste brute des réservations passées d'une utilisateur PASSAGER.
     *
     * @param integer $passengerId
     * @param integer $userId
     * @return array
     */
    public function listPastBookingsByPassenger(
        int $passengerId,
        int $userId
    ): array {
        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (!$this->hasAnyRole($userId, [
            UserRoles::PASSAGER,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }


        // Récupération du passager
        $passenger = $this->userRepository->findUserById($passengerId);

        // Vérification de l'existence du passeger
        if (!$passenger) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification qu'il s'agit bien du passager
        if ($this->isPassenger($userId)) {
            if ($userId !== $passengerId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->bookingRepository->fetchPastBookingsByPassenger($passengerId);
    }


    //------- Pour le staff uniquement ---------

    /**
     * Permet à un membre du personnel de récupèrer la liste des réservations selon la date départ.
     *
     * @param DateTimeImmutable $departureDate
     * @param integer $staffId
     * @return array
     */
    public function listBookingsByDepartureDate(
        DateTimeImmutable $departureDate,
        int $staffId
    ): array {
        // Récupération du membre du personnel
        $staff = $this->userRepository->findUserById($staffId);

        // Vérification de l'existence du membre du personnel
        if (!$staff) {
            throw new InvalidArgumentException("Membre du personnel introuvable.");
        }

        // Vérification de la permission
        $this->ensureStaff($staffId);

        return $this->bookingRepository->findAllBookingsByDepartureDate($departureDate);
    }

    /**
     * Permet à un membre du personnel de récupèrer la liste des réservations selon le statut de réservation.
     *
     * @param BookingStatus $bookingStatus
     * @param integer $staffId
     * @return array
     */
    public function listBookingsByStatus(
        BookingStatus $bookingStatus,
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

        return $this->bookingRepository->fetchAllBookingsByStatus($bookingStatus);
    }

    /**
     * Permet à un membre du personnel de récupèrer la liste des réservations selon la date de création.
     *
     * @param DateTimeInterface $creationDate
     * @param integer $staffId
     * @return array
     */
    public function listBookingsByCreatedAt(
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
