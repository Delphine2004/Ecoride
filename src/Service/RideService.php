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
use App\DTO\CreateRideDTO;
use App\Enum\RideStatus;
use App\Enum\BookingStatus;
use App\Enum\UserRoles;
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

    /**
     * Permet à un utilisateur CONDUCTEUR de rajouter un trajet.
     *
     * @param Ride $ride
     * @param integer $userId
     * @return Ride|null
     */
    public function addRide(
        CreateRideDTO $dto,
        int $userId,
    ): ?Ride {
        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        $this->ensureDriver($userId);


        // Vérification que le chauffeur a bien au moins une voiture
        if (!$this->carService->userHasCars($userId)) {
            throw new InvalidArgumentException("Le conducteur doit avoir au moins une voiture.");
        }


        // Création de l'objet Ride
        $ride = new Ride();

        // Remplissage de l'objet
        $ride->setRideDriverId($userId);
        $ride->setRideDepartureDateTime($dto->departureDateTime);
        $ride->setRideDeparturePlace($dto->departurePlace);
        $ride->setRideArrivalDateTime($dto->arrivalDateTime);
        $ride->setRideArrivalPlace($dto->arrivalPlace);
        $ride->setRidePrice($dto->price);
        $ride->setRideAvailableSeats($dto->availableSeats);
        $ride->setRideStatus($dto->rideStatus);

        // Enregistrement du trajet dans la BD.
        $this->rideWithUserRepository->insertRide($ride);


        // Déduction de la commission au conducteur
        $commission = $ride->getRideCommission();
        $user->setUserCredits($user->getUserCredits() - $commission);

        // Enregistrement de la modification des crédits
        $this->userRelationsRepository->updateUser(
            $user,
            [
                'credits' => $user->getUserCredits()
            ]
        );

        return $ride;
    }

    /**
     * Permet à un utilisateur PASSAGER de réserver un trajet.
     *
     * @param integer $rideId
     * @param integer $userId
     * @return Booking|null
     */
    public function bookRide(
        int $rideId,
        int $userId
    ): ?Booking {

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
        $this->ensurePassenger($userId);


        // Récupération du chauffeur
        $driver = $ride->getRideDriver();

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
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

    /**
     * Permet à un utilisateur CONDUCTEUR d'annuler un trajet.
     *
     * @param integer $rideId
     * @param integer $userId
     * @return Ride|null
     */
    public function cancelRide(
        int $rideId,
        int $userId
    ): ?Ride {

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
        if (!$this->roleService->hasAnyRole($userId, [
            UserRoles::CONDUCTEUR,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à annuler ce trajet.");
        }


        // Récupération de l'id du conducteur
        $driverId = $ride->getRideDriverId();
        // Récupération du chauffeur
        $driver = $ride->getRideDriver($driverId);

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
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

    /**
     * Permet à un utilisateur CONDUCTEUR de démarrer un trajet.
     *
     * @param Ride $ride
     * @param integer $userId
     * @return void
     */
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
        if (!$this->roleService->hasAnyRole($userId, [
            UserRoles::CONDUCTEUR,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à démarrer ce trajet.");
        }


        // Récupération de l'id du conducteur
        $driverId = $ride->getRideDriverId();
        // Récupération du chauffeur
        $driver = $ride->getRideDriver($driverId);

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
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

        // Notification des passagers
        $rideId = $ride->getRideId();
        $bookings = $this->bookingRelationsRepository->findBookingByRideId($rideId);
        foreach ($bookings as $booking) {
            $passenger = $this->userRelationsRepository->findUserById($booking->getPassengerId());
            $this->notificationService->sendRideStart($passenger, $ride);
        }

        // Notification du conducteur
        $this->notificationService->sendRideStart($driver, $ride);
    }

    /**
     * Permet à un utilisateur CONDUCTEUR de finaliser un trajet.
     *
     * @param Ride $ride
     * @param integer $userId
     * @return void
     */
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
        if (!$this->roleService->hasAnyRole($userId, [
            UserRoles::CONDUCTEUR,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à finaliser ce trajet.");
        }


        // Récupération du chauffeur
        $driver = $ride->getRideDriver();

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
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
        $ride->setRideStatus(RideStatus::ENATTENTE);

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


    /**
     * Récupére les trajets disponibles en fonction de la date, la ville de départ et la ville d'arrivée.
     *
     * @param DateTimeInterface $date
     * @param string $departurePlace
     * @param string $arrivalPlace
     * @return void
     */
    public function SearchRidesByDateAndPlaces(
        DateTimeInterface $date,
        string $departurePlace,
        string $arrivalPlace
    ) {
        return $this->rideWithUserRepository->findAllRidesByDateAndPlace($date, $departurePlace, $arrivalPlace);
    }


    /**
     * Récupére un trajet avec les passagers.
     *
     * @param integer $rideId
     * @return Ride|null
     */
    public function getRideWithPassengers(
        int $userId,
        int $rideId
    ): ?Ride {
        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        $this->ensureStaff($userId);

        return $this->rideWithUserRepository->findRideWithUsersByRideId($rideId);
    }


    //-------------Pour les conducteurs------------------
    /**
     * Récupére la liste brute des trajets d'un utilisateur CONDUCTEUR.
     *
     * @param integer $userId
     * @param integer $driverId
     * @return array
     */
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
        if (!$this->roleService->hasAnyRole($userId, [
            UserRoles::CONDUCTEUR,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }

        // Récupération du chauffeur
        $driver = $this->userRelationsRepository->findUserById($driverId);
        if (!$driver) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        return $this->rideWithUserRepository->fetchAllRidesByDriver($driverId);
    }

    /**
     * Récupére la liste d'objet Ride à venir d'un utilisateur CONDUCTEUR.
     *
     * @param integer $userId
     * @param integer $driverId
     * @return array
     */
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
        if (!$this->roleService->hasAnyRole($userId, [
            UserRoles::CONDUCTEUR,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }


        // Récupération du conducteur
        $driver = $this->userRelationsRepository->findUserById($driverId);

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        return $this->rideWithUserRepository->findUpcomingRidesByDriver($driverId);
    }

    /**
     * Récupére la liste brute des trajets passés d'un utilisateur CONDUCTEUR.
     *
     * @param integer $userId
     * @param integer $driverId
     * @return array
     */
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


        if (!$this->roleService->hasAnyRole($userId, [
            UserRoles::CONDUCTEUR,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }

        // Récupération du conducteur
        $driver = $this->userRelationsRepository->findUserById($driverId);

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }



        return $this->rideWithUserRepository->fetchPastRidesByDriver($driverId);
    }


    //-------------Pour les Passagers------------------
    /**
     * Récupére la liste brute des trajets d'un utilisateur PASSAGER.
     *
     * @param integer $userId
     * @param integer $passengerId
     * @return array
     */
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
        if (!$this->roleService->hasAnyRole($userId, [
            UserRoles::CONDUCTEUR,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }


        // Récupération du passager
        $passenger = $this->userRelationsRepository->findUserById($passengerId);

        // Vérification de l'existence du passeger
        if (!$passenger) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }



        return $this->rideWithUserRepository->fetchAllRidesByPassenger($passengerId);
    }

    /**
     * Récupére la liste d'objet Ride à venir d'un utilisateur PASSAGER.
     *
     * @param integer $userId
     * @param integer $passengerId
     * @return array
     */
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
        if (!$this->roleService->hasAnyRole($userId, [
            UserRoles::PASSAGER,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }


        // Récupération du passager
        $passenger = $this->userRelationsRepository->findUserById($passengerId);

        // Vérification de l'existence du passeger
        if (!$passenger) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        return $this->rideWithUserRepository->findUpcomingRidesByPassenger($passengerId);
    }

    /**
     * Récupére la liste brute des trajets passés d'un utilisateur PASSAGER.
     *
     * @param integer $userId
     * @param integer $passengerId
     * @return array
     */
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
        if (!$this->roleService->hasAnyRole($userId, [
            UserRoles::PASSAGER,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }


        // Récupération du passager
        $passenger = $this->userRelationsRepository->findUserById($passengerId);

        // Vérification de l'existence du passeger
        if (!$passenger) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        return $this->rideWithUserRepository->fetchPastRidesByPassenger($passengerId);
    }

    // -------------Pour le staff------------------
    public function getAllRidesByCreationDate(
        int $staffId,
        DateTimeInterface $creationDate
    ): array {

        // Récupération de l'staff
        $staff = $this->userRelationsRepository->findUserById($staffId);

        // Vérification de l'existence de l'staff
        if (!$staff) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        $this->ensureStaff($staffId);

        return $this->rideWithUserRepository->fetchAllRidesRowsByCreationDate($creationDate);
    }


    //-------------Pour les Admins------------------
    /**
     * Récupére le nombre de trajets effectués pour le jour J.
     *
     * @param integer $adminId
     * @return array
     */
    public function getNumberOfRidesFromToday(
        int $adminId
    ): array {

        // Récupération de l'admin
        $admin = $this->userRelationsRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        $this->ensureAdmin($adminId);

        return $this->rideWithUserRepository->countRidesByToday();
    }

    /**
     * Récupére le nombre de trajets effectués sur une période donnée.
     *
     * @param integer $adminId
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     * @return array
     */
    public function getNumberOfRidesOverPeriod(
        int $adminId,
        DateTimeInterface $start,
        DateTimeInterface $end
    ): array {

        // Récupération de l'admin
        $admin = $this->userRelationsRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        $this->ensureAdmin($adminId);

        return $this->rideWithUserRepository->countRidesByPeriod($start, $end);
    }

    /**
     * Récupére le nombre de commission gagné pour le jour J.
     *
     * @param integer $adminId
     * @return array
     */
    public function getTotalCommissionFromToday(
        int $adminId
    ): array {

        // Récupération de l'admin
        $admin = $this->userRelationsRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        $this->ensureAdmin($adminId);

        return $this->rideWithUserRepository->countCommissionByToday();
    }

    /**
     * Récupére le nombre de commission gagné sur une période donnée.
     *
     * @param integer $adminId
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     * @return array
     */
    public function getTotalCommissionOverPeriod(
        int $adminId,
        DateTimeInterface $start,
        DateTimeInterface $end
    ): array {

        // Récupération de l'admin
        $admin = $this->userRelationsRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        $this->ensureAdmin($adminId);

        return $this->rideWithUserRepository->countCommissionByPeriod($start, $end);
    }
}
