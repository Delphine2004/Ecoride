<?php

namespace App\Services;

use App\Repositories\RideWithUsersRepository;
use App\Repositories\BookingRelationsRepository;
use App\Repositories\UserRelationsRepository;
use App\Services\BookingService;
use App\Services\CarService;
use App\Services\NotificationService;
use App\Models\Ride;
use App\Models\Booking;
use App\Enum\RideStatus;
use App\Enum\BookingStatus;
use InvalidArgumentException;
use DateTimeInterface;

class RideService extends BaseService
{

    public function __construct(
        private RideWithUsersRepository $rideWithUserRepository,
        private BookingRelationsRepository $bookingRelationsRepository,
        private UserRelationsRepository $userRelationsRepository,
        private BookingService $bookingService,
        private CarService $carService,
        private NotificationService $notificationService

    ) {
        parent::__construct();
    }


    //-----------------ACTIONS------------------------------

    // Permet à un utilisateur CONDUCTEUR de rajouter un trajet.
    public function addRide(
        Ride $ride,
        int $userId
    ): Ride {
        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        $this->ensureDriver(!$userId);


        // Récupération de l'id du conducteur
        $driverId = $ride->getRideDriverId();
        // Récupération du chauffeur
        $driver = $ride->getRideDriver($driverId);

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Conducteur introuvable.");
        }


        // Vérification que le chauffeur a bien au moins une voiture
        if (!$this->carService->userHasCars($driverId)) {
            throw new InvalidArgumentException("Le conducteur doit avoir au moins une voiture.");
        }


        // Déduction de la commission
        $commission = $ride->getRideCommission();
        $driver->setUserCredits($driver->getUserCredits() - $commission);

        // Enregistrement de la modification des crédits
        $this->userRelationsRepository->updateUser(
            $driver,
            [
                'credits' => $driver->getUserCredits()
            ]
        );


        $this->rideWithUserRepository->insertRide($ride);

        return $ride;
    }

    // Permet à un utilisateur PASSAGER de réserver un trajet.
    public function bookRide(
        int $rideId,
        int $userId
    ): Booking {

        // Récupération du trajet
        $ride = $this->rideWithUserRepository->findRideById($rideId);

        // Vérification de l'existence du trajet
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }
        // Vérification du remplissage du trajet
        if (!$ride->hasAvailableSeat()) {
            throw new InvalidArgumentException("Trajet complet.");
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
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à réserver un trajet.");
        }


        // Récupération du chauffeur
        $driver = $ride->getRideDriver();

        // Vérification de l'existance du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Conducteur introuvable.");
        }

        // Vérification des crédits du passager
        $passenger = $this->userRelationsRepository->findUserById($userId);
        if ($passenger->getUserCredits() < $ride->getRidePrice()) {
            throw new InvalidArgumentException("Crédits insuffisants.");
        }


        // Décrémentation les crédits du passager
        $passenger->setUserCredits($passenger->getUserCredits() - $ride->getRidePrice());
        $this->userRelationsRepository->updateUser(
            $passenger,
            [
                'credits' => $passenger->getUserCredits()
            ]
        );

        // Création de la réservation - dédrémentation du siége incluse
        $booking = $this->bookingService->createBooking($ride, $driver, $passenger, $userId);

        // Notification
        $this->notificationService->sendRideConfirmationToPassenger($passenger, $ride);

        return $booking;
    }

    // Permet à un utilisateur CONDUCTEUR d'annuler un trajet.
    public function cancelRide(
        int $rideId,
        int $userId
    ): Ride {

        // Récupération de l'entité Ride
        $ride = $this->rideWithUserRepository->findRideById($rideId);

        // Vérification de l'existence du trajet
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }

        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (
            !$this->roleService->hasRole($userId, 'CONDUCTEUR') &&
            !$this->roleService->hasRole($userId, 'EMPLOYE') &&
            !$this->roleService->hasRole($userId, 'ADMIN')
        ) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à annuler ce trajet.");
        }


        // Récupération de l'id du conducteur
        $driverId = $ride->getRideDriverId();
        // Récupération du chauffeur
        $driver = $ride->getRideDriver($driverId);

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Conducteur introuvable.");
        }


        // Vérification qu'il s'agit bien du conducteur
        if ($ride->getRideDriverId() !== $driverId) {
            throw new InvalidArgumentException("Seulement le conducteur associé au trajet peut annuler son trajet.");
        }

        // Vérification du status de la réservation.
        if (in_array($ride->getRideStatus(), [RideStatus::ANNULE, RideStatus::ENCOURS, RideStatus::TERMINE], true)) {
            throw new InvalidArgumentException("Le trajet ne peut pas être annulé.");
        }

        // Mise à jour du status
        $ride->setRideStatus(RideStatus::ANNULE);


        // Récupération des réservations, mise à jour des réservations et remboursement
        $bookings = $this->bookingRelationsRepository->findBookingByRideId($rideId);

        foreach ($bookings as $booking) {

            // Mise à jour du statut de la réservation 
            $booking->setBookingStatus(BookingStatus::ANNULEE);

            // Enregistrement du statut en BD
            $this->bookingRelationsRepository->updateBooking($booking->getBookingId(), [
                'booking_status' => $booking->getBookingStatus()
            ]);

            // Défini le remboursement au passager
            $passenger = $this->userRelationsRepository->findUserById($booking->getPassengerId());
            $passenger->setUserCredits($passenger->getUserCredits() + $ride->getRidePrice());

            // Enregistrement des crédits du passager en BD
            $this->userRelationsRepository->updateUser($passenger, [
                'credits' => $passenger->getUserCredits()
            ]);

            // Envoi l'annulation
            $this->notificationService->sendRideCancelationToPassenger($passenger, $ride);
        }

        // Enregistrement du statut du trajet en BD
        $this->rideWithUserRepository->updateRide($ride, [
            'ride_status' => $ride->getRideStatus()
        ]);


        // Envoi de confirmation
        $driver = $ride->getRideDriver();
        $this->notificationService->sendRideCancelationToDriver($driver, $ride);

        return $ride;
    }

    // Permet à un utilisateur CONDUCTEUR de démarrer un trajet
    public function startRide(
        Ride $ride,
        int $userId
    ): void {

        // Vérification de l'existence du trajet
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }

        // Vérification du status du trajet.
        if ($ride->getRideStatus() !== RideStatus::DISPONIBLE && $ride->getRideStatus() !== RideStatus::COMPLET) {
            throw new InvalidArgumentException("Le trajet n'a pas le statut DISPONIBLE ou COMPLET.");
        }


        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (
            !$this->roleService->hasRole($userId, 'CONDUCTEUR') &&
            !$this->roleService->hasRole($userId, 'EMPLOYE') &&
            !$this->roleService->hasRole($userId, 'ADMIN')
        ) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à démarrer ce trajet.");
        }


        // Récupération de l'id du conducteur
        $driverId = $ride->getRideDriverId();
        // Récupération du chauffeur
        $driver = $ride->getRideDriver($driverId);

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Conducteur introuvable.");
        }

        // Vérification que l'utilisateur est bien le conducteur du trajet
        if ($ride->getRideDriverId() !== $driverId) {
            throw new InvalidArgumentException("Seulement le conducteur associé au trajet peut demarrer son trajet.");
        }



        // Modification du status
        $ride->setRideStatus(RideStatus::ENCOURS);

        // Enregistrement dans la BD
        $this->rideWithUserRepository->updateRide(
            $ride,
            [
                'ride_status' => $ride->getRideStatus()
            ]
        );
    }

    // Permet à un utilisateur CONDUCTEUR de finaliser un trajet
    public function finalizeRide(
        Ride $ride,
        int $userId
    ): void {

        // Vérification de l'existence du trajet
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }


        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (
            !$this->roleService->hasRole($userId, 'CONDUCTEUR') &&
            !$this->roleService->hasRole($userId, 'EMPLOYE') &&
            !$this->roleService->hasRole($userId, 'ADMIN')
        ) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à finaliser ce trajet.");
        }




        // Récupération du chauffeur
        $driver = $ride->getRideDriver();

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Conducteur introuvable.");
        }


        // Récupération de l'id du conducteur
        $driverId = $ride->getRideDriverId();

        // Vérification que l'utilisateur est bien le conducteur du trajet
        if ($ride->getRideDriverId() !== $driverId) {
            throw new InvalidArgumentException("Seulement le conducteur associé au trajet peut finaliser son trajet.");
        }

        // Vérification du status de la réservation.
        if ($ride->getRideStatus() !== RideStatus::ENCOURS) {
            throw new InvalidArgumentException("Le trajet n'a pas le statut ENCOURS.");
        }

        // Modification du status
        $ride->setRideStatus(RideStatus::TERMINE);

        // Enregistrement dans la BD
        $this->rideWithUserRepository->updateRide(
            $ride,
            [
                'ride_status' => $ride->getRideStatus()
            ]
        );

        // Récupération du trajet
        $rideId = $ride->getRideId();

        // Créditer le conducteur avec le total des passagers
        $bookings = $this->bookingRelationsRepository->findBookingByRideId($rideId);
        $totalCredits = 0;

        // Calcule du total du coût de chaque passager
        foreach ($bookings as $booking) {
            if ($booking->getBookingStatus() === BookingStatus::CONFIRMEE) {
                $totalCredits += $ride->getRidePrice();

                // Mise à jour du statut de réservation
                $booking->setBookingStatus(BookingStatus::PASSEE);
                $this->bookingRelationsRepository->updateBooking(
                    $booking->getBookingId(),
                    [
                        'booking_status' => $booking->getBookingStatus()
                    ]
                );

                // Notification du passager
                $passenger = $this->userRelationsRepository->findUserById($booking->getPassengerId());
                $this->notificationService->sendRideFinalizationToPassenger($passenger, $ride);
            }
        }
        $driver = $this->userRelationsRepository->findUserById($driverId);
        $driver->setUserCredits($driver->getUserCredits() + $totalCredits);
        $this->userRelationsRepository->updateUser(
            $driver,
            [
                'credits' => $driver->getUserCredits()
            ]
        );

        // Notification du conducteur
        $this->notificationService->sendRideFinalizationToDriver($driver, $ride);
    }



    //------------------RECUPERATIONS------------------------

    // Récupére un trajet avec les passagers.
    public function getRideWithPassengers(
        int $rideId
    ): ?Ride {
        return $this->rideWithUserRepository->findRideWithUsersByRideId($rideId);
    }

    //-------------Pour les conducteurs------------------
    // Récupére la liste brute des trajets d'un utilisateur CONDUCTEUR.
    public function getAllRidesByDriver(
        int $userId,
        int $driverId
    ): array {

        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence des utilisateurs
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (
            !$this->roleService->hasRole($userId, 'CONDUCTEUR') &&
            !$this->roleService->hasRole($userId, 'EMPLOYE') &&
            !$this->roleService->hasRole($userId, 'ADMIN')
        ) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }

        // Récupération du chauffeur
        $driver = $this->userRelationsRepository->findUserById($driverId);
        if (!$driver) {
            throw new InvalidArgumentException("Conducteur introuvable.");
        }

        return $this->rideWithUserRepository->fetchAllRidesByDriver($driverId);
    }

    // Récupére la liste d'objet Ride à venir d'un utilisateur CONDUCTEUR.
    public function getUpcomingRidesByDriver(
        int $userId,
        int $driverId
    ): array {
        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence des utilisateurs
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (
            !$this->roleService->hasRole($userId, 'CONDUCTEUR') &&
            !$this->roleService->hasRole($userId, 'EMPLOYE') &&
            !$this->roleService->hasRole($userId, 'ADMIN')
        ) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }


        // Récupération du conducteur
        $driver = $this->userRelationsRepository->findUserById($driverId);

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Conducteur introuvable.");
        }

        return $this->rideWithUserRepository->findUpcomingRidesByDriver($driverId);
    }

    // Récupére la liste brute des trajets passés d'un utilisateur CONDUCTEUR.
    public function getPastRidesByDriver(
        int $userId,
        int $driverId
    ): array {
        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }


        if (
            !$this->roleService->hasRole($userId, 'CONDUCTEUR') &&
            !$this->roleService->hasRole($userId, 'EMPLOYE') &&
            !$this->roleService->hasRole($userId, 'ADMIN')
        ) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }

        // Récupération du conducteur
        $driver = $this->userRelationsRepository->findUserById($driverId);

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Conducteur introuvable.");
        }



        return $this->rideWithUserRepository->fetchPastRidesByDriver($driverId);
    }


    //-------------Pour les Passagers------------------
    // Récupére la liste brute des trajets d'un utilisateur PASSAGER.
    public function getAllRidesByPassenger(
        int $userId,
        int $passengerId
    ): array {
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
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }


        // Récupération du passager
        $passenger = $this->userRelationsRepository->findUserById($passengerId);

        // Vérification de l'existence du passeger
        if (!$passenger) {
            throw new InvalidArgumentException("Passager introuvable.");
        }



        return $this->rideWithUserRepository->fetchAllRidesByPassenger($passengerId);
    }

    // Récupére la liste d'objet Ride à venir d'un utilisateur PASSAGER.
    public function getUpcomingRidesByPassenger(
        int $userId,
        int $passengerId
    ): array {
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
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }


        // Récupération du passager
        $passenger = $this->userRelationsRepository->findUserById($passengerId);

        // Vérification de l'existence du passeger
        if (!$passenger) {
            throw new InvalidArgumentException("Passager introuvable.");
        }

        return $this->rideWithUserRepository->findUpcomingRidesByPassenger($passengerId);
    }

    // Récupére la liste brute des trajets passés d'un utilisateur PASSAGER.
    public function getPastRidesByPassenger(
        int $userId,
        int $passengerId
    ): array {
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
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }


        // Récupération du passager
        $passenger = $this->userRelationsRepository->findUserById($passengerId);

        // Vérification de l'existence du passeger
        if (!$passenger) {
            throw new InvalidArgumentException("Passager introuvable.");
        }

        return $this->rideWithUserRepository->fetchPastRidesByPassenger($passengerId);
    }

    //-------------Pour les Admins------------------
    // Récupére le nombre de trajets effectués pour le jour J.
    public function getNumberOfRidesFromToday(
        int $adminId
    ): array {

        // Récupération de l'admin
        $admin = $this->userRelationsRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Admin introuvable.");
        }

        $this->ensureAdmin($adminId);

        return $this->rideWithUserRepository->countRidesByToday();
    }

    // Récupére le nombre de trajets effectués sur une période donnée.
    public function getNumberOfRidesOverPeriod(
        int $adminId,
        DateTimeInterface $start,
        DateTimeInterface $end
    ): array {

        // Récupération de l'admin
        $admin = $this->userRelationsRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Admin introuvable.");
        }

        $this->ensureAdmin($adminId);

        return $this->rideWithUserRepository->countRidesByPeriod($start, $end);
    }

    // Récupére le nombre de commission gagné pour le jour J.
    public function getTotalCommissionFromToday(
        int $adminId
    ): array {

        // Récupération de l'admin
        $admin = $this->userRelationsRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Admin introuvable.");
        }

        $this->ensureAdmin($adminId);

        return $this->rideWithUserRepository->countCommissionByToday();
    }

    // Récupére le nombre de commission gagné sur une période donnée.
    public function getTotalCommissionOverPeriod(
        int $adminId,
        DateTimeInterface $start,
        DateTimeInterface $end
    ): array {

        // Récupération de l'admin
        $admin = $this->userRelationsRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Admin introuvable.");
        }

        $this->ensureAdmin($adminId);

        return $this->rideWithUserRepository->countCommissionByPeriod($start, $end);
    }
}
