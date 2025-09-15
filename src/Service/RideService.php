<?php

namespace App\Services;

use App\Models\Ride;
use App\Enum\RideStatus;
use App\Enum\BookingStatus;
use App\Enum\UserRoles;
use App\DTO\CreateRideDTO;
use App\Repositories\RideRepository;
use App\Repositories\BookingRepository;
use App\Repositories\UserRepository;
use App\Services\CarService;
use App\Services\NotificationService;
use InvalidArgumentException;
use DateTimeInterface;


class RideService extends BaseService
{

    public function __construct(
        private RideRepository $rideRepository,
        private BookingRepository $bookingRepository,
        private UserRepository $userRepository,
        private CarService $carService,
        private NotificationService $notificationService

    ) {}


    //-----------------ACTIONS------------------------------


    /**
     * Permet à un utilisateur CONDUCTEUR d'ajouter un trajet.
     *
     * @param CreateRideDTO $dto
     * @param integer $userId
     * @return Ride|null
     */
    public function createRide(
        CreateRideDTO $dto,
        int $userId,
    ): ?Ride {
        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        $this->ensureDriver($userId);


        // Vérification que le chauffeur a une voiture
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
        $this->rideRepository->insertRide($ride);


        // Déduction de la commission au conducteur
        $commission = $ride->getRideCommission();
        $user->setUserCredits($user->getUserCredits() - $commission);

        // Enregistrement de la modification des crédits
        $this->userRepository->updateUser(
            $user,
            [
                'credits' => $user->getUserCredits()
            ]
        );

        return $ride;
    }


    /**
     * Permet à un utilisateur CONDUCTEUR OU EMPLOYE OU ADMIN d'annuler un trajet.
     * Commission non remboursable.
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
        $ride = $this->rideRepository->findRideById($rideId);

        // Vérification de l'existence du trajet
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }

        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

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



        // Récupération du chauffeur
        $driver = $ride->getRideDriver();

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }


        // Vérification qu'il s'agit bien du conducteur
        if ($this->roleService->isDriver($userId)) {
            if ($ride->getRideDriverId() !== $userId) {
                throw new InvalidArgumentException("Le conducteur ne correspond pas à ce trajet.");
            }
        }

        // Vérification du status de la réservation.
        if (in_array($ride->getRideStatus(), [RideStatus::ANNULE, RideStatus::ENCOURS, RideStatus::TERMINE], true)) {
            throw new InvalidArgumentException("Le trajet ne peut pas être annulé.");
        }

        // Mise à jour du status
        $ride->setRideStatus(RideStatus::ANNULE);


        // Récupération des réservations, mise à jour des réservations et remboursement
        $bookings = $this->bookingRepository->findBookingByRideId($rideId);

        foreach ($bookings as $booking) {

            // Mise à jour du statut de la réservation 
            $booking->setBookingStatus(BookingStatus::ANNULEE);

            // Enregistrement du statut en BD
            $this->bookingRepository->updateBooking($booking->getBookingId(), [
                'booking_status' => $booking->getBookingStatus()
            ]);

            // Défini le remboursement au passager
            $passenger = $this->userRepository->findUserById($booking->getPassengerId());
            $passenger->setUserCredits($passenger->getUserCredits() + $ride->getRidePrice());

            // Enregistrement des crédits du passager en BD
            $this->userRepository->updateUser($passenger, [
                'credits' => $passenger->getUserCredits()
            ]);

            // Envoi l'annulation
            $this->notificationService->sendRideCancelationToPassenger($passenger, $ride);
        }

        // Enregistrement du statut du trajet en BD
        $this->rideRepository->updateRide($ride, [
            'ride_status' => $ride->getRideStatus()
        ]);


        // Envoi de confirmation
        $driver = $ride->getRideDriver();
        $this->notificationService->sendRideCancelationToDriver($driver, $ride);

        return $ride;
    }



    /**
     * Permet à un utilisateur CONDUCTEUR OU EMPLOYE OU ADMIN de démarrer un trajet.
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
        $user = $this->userRepository->findUserById($userId);

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

        // Vérification qu'il s'agit bien du conducteur
        if ($this->roleService->isDriver($userId)) {
            if ($ride->getRideDriverId() !== $userId) {
                throw new InvalidArgumentException("Le conducteur ne correspond pas à ce trajet.");
            }
        }

        // Modification du status
        $ride->setRideStatus(RideStatus::ENCOURS);

        // Enregistrement dans la BD
        $this->rideRepository->updateRide(
            $ride,
            [
                'ride_status' => $ride->getRideStatus()
            ]
        );

        // Notification des passagers et modification du statut de la réservation des passagers
        $rideId = $ride->getRideId();
        $bookings = $this->bookingRepository->findBookingByRideId($rideId);
        foreach ($bookings as $booking) {
            $passenger = $this->userRepository->findUserById($booking->getPassengerId());
            $booking->setBookingStatus(BookingStatus::ENCOURS);

            // Enregistrement dans la BD
            $this->bookingRepository->updateBooking(
                $booking,
                [
                    'booking_status' => $booking->getBookingStatus()
                ]
            );

            $this->notificationService->sendRideStart($passenger, $ride);
        }

        // Notification du conducteur
        $driver = $ride->getRideDriver();
        $this->notificationService->sendRideStart($driver, $ride);
    }

    /**
     * Permet à un utilisateur CONDUCTEUR OU EMPLOYE OU ADMIN d'arrêter un trajet.
     *
     * @param Ride $ride
     * @param integer $userId
     * @return void
     */
    public function stopRide(
        Ride $ride,
        int $userId
    ): void {

        // Vérification de l'existence du trajet
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }


        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

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

        // Vérification qu'il s'agit bien du conducteur
        if ($this->roleService->isDriver($userId)) {
            if ($ride->getRideDriverId() !== $userId) {
                throw new InvalidArgumentException("Le conducteur ne correspond pas à ce trajet.");
            }
        }


        // Vérification du status de la réservation.
        if ($ride->getRideStatus() !== RideStatus::ENCOURS) {
            throw new InvalidArgumentException("Le trajet n'a pas le statut ENCOURS.");
        }

        // Récupération du chauffeur
        $driver = $ride->getRideDriver();

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }


        // Modification du status
        $ride->setRideStatus(RideStatus::ENATTENTE);

        // Enregistrement dans la BD
        $this->rideRepository->updateRide(
            $ride,
            [
                'ride_status' => $ride->getRideStatus()
            ]
        );


        // ------------- Envoi des mails aux utilisateur----------
        // Récupération des passagers
        $passengersId = $ride->getRidePassengers();

        foreach ($passengersId as $passengerId) {
            // Récupération du passager
            $passenger = $this->userRepository->findUserById($passengerId);

            // Vérification de l'existence du passager
            if (!$passenger) {
                throw new InvalidArgumentException("Utilisateur introuvable.");
            }

            // Envoi de la demande de confirmation de fin de trajet
            $this->notificationService->sendRideFinalizationRequestToPassenger($passenger, $ride);
        }

        // Notification du conducteur
        $this->notificationService->sendRideConfirmationStopToDriver($driver, $ride);
    }


    /**
     * Permet à un utilisateur CONDUCTEUR OU EMPLOYE OU ADMIN de finaliser le trajet.
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
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à finaliser ce trajet.");
        }

        // Vérification qu'il s'agit bien du conducteur
        if ($this->roleService->isDriver($userId)) {
            if ($ride->getRideDriverId() !== $userId) {
                throw new InvalidArgumentException("Le conducteur ne correspond pas à ce trajet.");
            }
        }

        // Vérification du status du trajet
        if ($ride->getRideStatus() !== RideStatus::ENATTENTE) {
            throw new InvalidArgumentException("Le trajet n'a pas le statut ENATTENTE.");
        }


        // Confirmation de la réservation du passager
        // Récupération de sa réservation
        $booking = $this->bookingRepository->findBookingByPassengerAndRide($user->getUserId(), $ride->getRideId());
        if (!$booking) {
            throw new InvalidArgumentException("Réservation introuvable pour cet utilisateur.");
        }

        // Vérification du statut de la réservation
        if ($booking->getBookingStatus() === BookingStatus::PASSEE) {
            throw new InvalidArgumentException("La réservation a déjà été validée.");
        }

        // Modification du statut de sa réservation
        $booking->setBookingStatus(BookingStatus::PASSEE);

        // Enregistrement dans la bd.
        $this->bookingRepository->updateBooking(
            $booking,
            [
                'booking_status' => $booking->getBookingStatus()
            ]
        );

        // Envoi de la confirmation de finalisation
        $this->notificationService->sendRideFinalizationToPassenger($user, $ride);


        //-----Récupération des crédits / modification des statuts de réservation / notification des passagers-----
        // Récupération des passagers
        $allConfirmed = true;
        $totalCredits = 0;
        $passengersId = $ride->getRidePassengers();

        foreach ($passengersId as $passengerId) {
            $booking = $this->bookingRepository->findBookingByPassengerAndRide($passengerId, $ride->getRideId());

            if (!$booking || $booking->getBookingStatus() !== BookingStatus::PASSEE) {
                $allConfirmed = false;
                break;
            }

            // Ajout du prix du trajet dans la variable $totalCredits
            $totalCredits += $ride->getRidePrice();
        }


        // -------Créditer le conducteur avec le total des passagers si tous ont confirmé

        if ($allConfirmed) {
            // Récupération du conducteur
            $driver = $this->userRepository->findUserById($ride->getRideDriverId());

            // Vérification de l'existence du conducteur
            if (!$driver) {
                throw new InvalidArgumentException("Conducteur introuvable.");
            }
        }

        // Ajout des crédits au conducteur
        $driver->setUserCredits($driver->getUserCredits() + $totalCredits);

        $this->userRepository->updateUser(
            $driver,
            [
                'credits' => $driver->getUserCredits()
            ]
        );

        // Modification du statut du trajet
        $ride->setRideStatus(RideStatus::TERMINE);

        // Enregistrements dans la bd.
        $this->rideRepository->updateRide(
            $ride,
            [
                'ride_status' => $ride->getRideStatus()
            ]
        );

        // Notification du conducteur
        $this->notificationService->sendRideFinalizationToDriver($driver, $ride);
    }

    //------------------RECUPERATIONS------------------------

    /**
     * Permet à un utilisateur de récupèrer un trajet avec les passagers.
     *
     * @param integer $rideId
     * @param integer $userId
     * @return Ride|null
     */
    public function getRideWithPassengers(
        int $rideId,
        int $userId
    ): ?Ride {
        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        $this->ensureUser($userId);

        return $this->rideRepository->findRideWithUsersByRideId($rideId);
    }

    /**
     * Permet de récupèrer les trajets disponibles en fonction de la date, la ville de départ et la ville d'arrivée.
     *
     * @param DateTimeInterface $date
     * @param string $departurePlace
     * @param string $arrivalPlace
     * @return array
     */
    public function listRidesByDateAndPlaces(
        DateTimeInterface $date,
        string $departurePlace,
        string $arrivalPlace
    ): array {
        return $this->rideRepository->findAllRidesByDateAndPlace($date, $departurePlace, $arrivalPlace);
    }


    //-------------Pour les conducteurs------------------


    /**
     * Permet à un utilisateur CONDUCTEUR OU EMPLOYE OU ADMIN de récupèrer la liste d'objet Ride à venir d'un utilisateur CONDUCTEUR.
     *
     * @param integer $driverId
     * @param integer $userId
     * @return array
     */
    public function listUpcomingRidesByDriver(
        int $driverId,
        int $userId
    ): array {
        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

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
        $driver = $this->userRepository->findUserById($driverId);

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification qu'il s'agit bien du conducteur
        if ($this->roleService->isDriver($userId)) {
            if ($userId !== $driverId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->rideRepository->findUpcomingRidesByDriver($driverId);
    }

    /**
     * Permet à un utilisateur CONDUCTEUR OU EMPLOYE OU ADMIN de récupèrer la liste brute des trajets passés d'un utilisateur CONDUCTEUR.
     *
     * @param integer $driverId
     * @param integer $userId
     * @return array
     */
    public function listPastRidesByDriver(
        int $driverId,
        int $userId
    ): array {
        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

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
        $driver = $this->userRepository->findUserById($driverId);

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification qu'il s'agit bien du conducteur
        if ($this->roleService->isDriver($userId)) {
            if ($userId !== $driverId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->rideRepository->fetchPastRidesByDriver($driverId);
    }


    //-------------Pour les Passagers------------------


    /**
     * Permet à un utilisateur PASSAGER OU EMPLOYE OU ADMIN de récupèrer la liste d'objet Ride à venir d'un utilisateur PASSAGER.
     *
     * @param integer $passengerId
     * @param integer $userId
     * @return array
     */
    public function listUpcomingRidesByPassenger(
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
        if (!$this->roleService->hasAnyRole($userId, [
            UserRoles::PASSAGER,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }

        // Récupération du passager
        $passenger = $this->userRepository->findUserById($passengerId);

        // Vérification de l'existence du passager
        if (!$passenger) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification qu'il s'agit bien du passager
        if ($this->roleService->isPassenger($userId)) {
            if ($userId !== $passengerId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->rideRepository->findUpcomingRidesByPassenger($passengerId);
    }

    /**
     * Permet à un utilisateur PASSAGER OU EMPLOYE OU ADMIN de récupèrer la liste brute des trajets passés d'un utilisateur PASSAGER.
     *
     * @param integer $passengerId
     * @param integer $userId
     * @return array
     */
    public function listPastRidesByPassenger(
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
        if (!$this->roleService->hasAnyRole($userId, [
            UserRoles::PASSAGER,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à accéder à ces informations.");
        }

        // Récupération du passager
        $passenger = $this->userRepository->findUserById($passengerId);

        // Vérification de l'existence du passager
        if (!$passenger) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification qu'il s'agit bien du passager
        if ($this->roleService->isPassenger($userId)) {
            if ($userId !== $passengerId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->rideRepository->fetchPastRidesByPassenger($passengerId);
    }

    //------- Pour le staff uniquement ---------
    /**
     * Permet à un membre du personnel de récupèrer la liste brute des réservations par date de création.
     *
     * @param DateTimeInterface $creationDate
     * @param integer $staffId
     * @return array
     */
    public function listRidesByCreationDate(
        DateTimeInterface $creationDate,
        int $staffId
    ): array {

        // Récupération du membre du personnel
        $staff = $this->userRepository->findUserById($staffId);

        // Vérification de l'existence du membre du personnel
        if (!$staff) {
            throw new InvalidArgumentException("Membre du personnel introuvable..");
        }

        // Vérification de la permission
        $this->ensureStaff($staffId);

        return $this->rideRepository->fetchAllRidesRowsByCreationDate($creationDate);
    }


    //-------------Pour les Admins------------------
    /**
     * Permet à un admin de récupèrer le nombre de trajets effectués pour le jour J.
     *
     * @param integer $adminId
     * @return array
     */
    public function getNumberOfRidesFromToday(
        int $adminId
    ): array {

        // Récupération de l'admin
        $admin = $this->userRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification de la permission
        $this->ensureAdmin($adminId);

        return $this->rideRepository->countRidesByToday();
    }

    /**
     * Permet à un admin de récupèrer le nombre de trajets effectués sur une période donnée.
     *
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     * @param integer $adminId
     * @return array
     */
    public function getNumberOfRidesOverPeriod(
        DateTimeInterface $start,
        DateTimeInterface $end,
        int $adminId
    ): array {

        // Récupération de l'admin
        $admin = $this->userRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification de la permission
        $this->ensureAdmin($adminId);

        return $this->rideRepository->countRidesByPeriod($start, $end);
    }

    /**
     * Permet à un admin de récupèrer le total des commissions gagnées pour le jour J.
     *
     * @param integer $adminId
     * @return array
     */
    public function getTotalCommissionFromToday(
        int $adminId
    ): array {

        // Récupération de l'admin
        $admin = $this->userRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification de la permission
        $this->ensureAdmin($adminId);

        return $this->rideRepository->countCommissionByToday();
    }

    /**
     * Permet à un admin de récupèrer le total des commissions gagnées sur une période donnée.
     *
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     * @param integer $adminId
     * @return array
     */
    public function getTotalCommissionOverPeriod(
        DateTimeInterface $start,
        DateTimeInterface $end,
        int $adminId
    ): array {

        // Récupération de l'admin
        $admin = $this->userRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification de la permission
        $this->ensureAdmin($adminId);

        return $this->rideRepository->countCommissionByPeriod($start, $end);
    }
}
