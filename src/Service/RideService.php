<?php

namespace App\Service;

use App\Repository\RideRepository;
use App\Service\BookingService;
use App\Service\UserService;
use App\Service\NotificationService;
use App\Service\CarService;
use App\Model\Ride;
use App\Enum\RideStatus;
use App\Enum\BookingStatus;
use App\DTO\CreateRideDTO;
use App\DTO\UpdateUserDTO;
use DateTimeImmutable;
use InvalidArgumentException;
use DateTimeInterface;


class RideService
{
    public function __construct(
        protected RideRepository $rideRepository,
        protected UserService $userService,
        protected NotificationService $notificationService,
        protected BookingService $bookingService,
        protected CarService $carService
    ) {}

    /**
     * Vérifie que le trajet existe.
     *
     * @param integer $rideId
     * @return void
     */
    public function checkIfRideExists(int $rideId)
    {
        // Récupération de l'entité Ride
        $ride = $this->rideRepository->findRideById($rideId);

        // Vérification de l'existence du trajet
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }
    }

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
    ): Ride {

        $this->userService->checkIfUserExists($userId);
        $this->userService->ensureDriver($userId);

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

        // Récupération de l'utilisateur
        $driver = $this->userService->getUserById($userId);


        // Déduction de la commission au conducteur
        $commission = $ride->getRideCommission();

        // Vérifier que le conducteur a assez de crédit pour payer la commission
        if ($driver->getUserCredits() < $commission) {
            throw new InvalidArgumentException("Crédits insuffisants pour créer ce trajet.");
        }

        $newCreditsBalance = $driver->getUserCredits() - $commission;

        $userDto = new UpdateUserDTO(['credits' => $newCreditsBalance]);


        // Enregistrement de la modification des crédits
        $this->userService->updateProfile($userDto, $userId);

        // Enregistrement dans la bd
        $this->rideRepository->insertRide($ride);

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
    ): Ride {

        $this->checkIfRideExists($rideId);

        $this->userService->checkIfUserExists($userId);
        $this->userService->ensureDriverAndStaff($userId);

        $ride = $this->rideRepository->findRideById($rideId);

        // Vérification qu'il s'agit bien du conducteur
        if ($this->userService->isDriver($userId)) {
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
        $bookings = $this->bookingService->getAllBookingsByRideId($rideId, $userId);

        foreach ($bookings as $booking) {
            // Récupérer le passager
            $passengerId = $booking->getBookingPassengerId();
            $passenger = $booking->getBookingPassenger();


            // Mise à jour du statut de la réservation 
            $bookingStatus = $booking->setBookingStatus(BookingStatus::ANNULEE);

            // Enregistrement dans la bd
            $this->bookingService->updateBookingStatus($booking->getBookingId(), $bookingStatus, $passengerId);


            // Défini le remboursement au passager
            $newCreditsBalance = $passenger->getUserCredits() + $ride->getRidePrice();

            $passengerDto = new UpdateUserDTO(['credits' => $newCreditsBalance]);

            // Enregistrement des crédits du passager en BD
            $this->userService->updateProfile($passengerDto, $userId);

            // Envoi de la notification
            $this->notificationService->sendRideCancelationToPassenger($passenger, $ride);
        }

        // Enregistrement du statut du trajet en BD
        $this->rideRepository->updateRide($ride, [
            'ride_status' => $ride->getRideStatus()
        ]);


        // Envoi de confirmation
        $driver = $this->userService->getUserById($userId);
        $this->notificationService->sendRideCancelationToDriver($driver, $ride);

        return $ride;
    }

    public function updateRideStatus(
        int $rideId,
        RideStatus $rideStatus
    ): Ride {
        // récupération de l'Objet Ride
        $ride = $this->rideRepository->findRideById($rideId);

        $ride->setRideStatus($rideStatus);

        return $ride;
    }

    public function updateRideAvailableSeats(
        int $rideId,
        int $seat
    ): Ride {
        // récupération de l'Objet Ride
        $ride = $this->rideRepository->findRideById($rideId);

        $ride->setRideAvailableSeats($seat);

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
        int $rideId,
        int $userId
    ): Ride {

        $this->checkIfRideExists($rideId);

        $this->userService->checkIfUserExists($userId);
        $this->userService->ensureDriverAndStaff($userId);

        // Récupération de l'objet Ride
        $ride = $this->rideRepository->findRideById($rideId);

        // Vérification du status du trajet.
        if ($ride->getRideStatus() !== RideStatus::DISPONIBLE && $ride->getRideStatus() !== RideStatus::COMPLET) {
            throw new InvalidArgumentException("Le trajet n'a pas le statut DISPONIBLE ou COMPLET.");
        }

        // Vérification qu'il s'agit bien du conducteur
        if ($this->userService->isDriver($userId)) {
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


        // Récupération des passagers
        $passengers = $ride->getRidePassengers();

        foreach ($passengers as $passenger) {
            $booking = $this->bookingService->getBookingByPassengerAndRide($passenger, $rideId);

            // Enregistrement dans la BD
            $this->bookingService->updateBookingStatus($booking->getBookingId(), BookingStatus::ENCOURS);

            $this->notificationService->sendRideStartToPassenger($passenger, $ride);
        }

        // Notification du conducteur
        $driver = $this->userService->getUserById($userId);
        $this->notificationService->sendRideStartToDriver($driver, $ride);

        return $ride;
    }

    /**
     * Permet à un utilisateur CONDUCTEUR OU EMPLOYE OU ADMIN d'arrêter un trajet.
     *
     * @param Ride $ride
     * @param integer $userId
     * @return void
     */
    public function stopRide(
        int $rideId,
        int $userId
    ): Ride {

        $this->checkIfRideExists($rideId);

        $this->userService->checkIfUserExists($userId);
        $this->userService->ensureDriverAndStaff($userId);

        // Récupération de l'objet Ride
        $ride = $this->rideRepository->findRideById($rideId);

        // Vérification qu'il s'agit bien du conducteur
        if ($this->userService->isDriver($userId)) {
            if ($ride->getRideDriverId() !== $userId) {
                throw new InvalidArgumentException("Le conducteur ne correspond pas à ce trajet.");
            }
        }


        // Vérification du status de la réservation.
        if ($ride->getRideStatus() !== RideStatus::ENCOURS) {
            throw new InvalidArgumentException("Le trajet n'a pas le statut ENCOURS.");
        }

        // Récupération de l'utilisateur
        $driver = $this->userService->getUserById($userId);

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
        $passengers = $ride->getRidePassengers();

        foreach ($passengers as $passenger) {
            // Envoi de la demande de confirmation de fin de trajet
            $this->notificationService->sendRideFinalizationRequestToPassenger($passenger, $ride);
        }

        // Notification du conducteur
        $this->notificationService->sendRideConfirmationStopToDriver($driver, $ride);

        return $ride;
    }


    /**
     * Permet à un utilisateur CONDUCTEUR OU EMPLOYE OU ADMIN de finaliser le trajet.
     *
     * @param Ride $ride
     * @param integer $userId
     * @return void
     */
    public function finalizeRide(
        int $rideId,
        int $userId
    ): Ride {

        $this->checkIfRideExists($rideId);
        $this->userService->checkIfUserExists($userId);

        $this->userService->ensureDriverAndStaff($userId);

        // Récupération de l'objet Ride
        $ride = $this->rideRepository->findRideById($rideId);


        // Vérification du status du trajet
        if ($ride->getRideStatus() !== RideStatus::ENATTENTE) {
            throw new InvalidArgumentException("Le trajet n'a pas le statut ENATTENTE.");
        }


        //-----Récupération des crédits / modification des statuts de réservation / notification des passagers-----

        $allConfirmed = true;
        $totalCredits = 0;
        // Récupération des passagers
        $passengersId = $ride->getRidePassengers();

        foreach ($passengersId as $passengerId) {
            // Récupération de la réservation
            $booking = $this->bookingService->getBookingByPassengerAndRide($passengerId, $ride->getRideId());

            if (!$booking || $booking->getBookingStatus() !== BookingStatus::PASSEE) {
                $allConfirmed = false;
                break;
            }

            // Ajout du prix du trajet dans la variable $totalCredits
            $totalCredits += $ride->getRidePrice();
        }


        // -------Créditer le conducteur avec le total des passagers si tous ont confirmé

        if (!$allConfirmed) {
            return $ride;
        } else {
            // Récupération de l'utilisateur
            $driver = $this->userService->getUserById($userId);

            $this->userService->checkIfUserExists($driver->getUserId());


            // Ajout des crédits au conducteur
            $newCreditsBalance = $driver->setUserCredits($driver->getUserCredits() + $totalCredits);

            $userDto = new UpdateUserDTO(['credits' => $newCreditsBalance]);

            $this->userService->updateProfile($userDto, $driver->getUserId());

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

            return $ride;
        }
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
    ): Ride {
        $this->userService->checkIfUserExists($userId);
        $this->userService->ensureUser($userId);
        $this->checkIfRideExists($rideId);


        return $this->rideRepository->findRideWithUsersByRideId($rideId);
    }

    /**
     * Permet de récupèrer les trajets disponibles en fonction de la date, la ville de départ et la ville d'arrivée.
     *
     * @param DateTimeImmutable $date
     * @param string $departurePlace
     * @param string $arrivalPlace
     * @return array
     */
    public function listRidesByDateAndPlaces(
        DateTimeImmutable $date,
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

        $this->userService->checkIfUserExists($userId);
        $this->userService->ensureDriverAndStaff($userId);

        $this->userService->checkIfUserExists($driverId);

        // Vérification qu'il s'agit bien du conducteur
        if ($this->userService->isDriver($userId)) {
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

        $this->userService->checkIfUserExists($userId);
        $this->userService->ensureDriverAndStaff($userId);

        $this->userService->checkIfUserExists($driverId);

        // Vérification qu'il s'agit bien du conducteur
        if ($this->userService->isDriver($userId)) {
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
        $this->userService->checkIfUserExists($userId);
        $this->userService->ensurePassengerAndStaff($userId);

        $this->userService->checkIfUserExists($passengerId);

        // Vérification qu'il s'agit bien du passager
        if ($this->userService->isPassenger($userId)) {
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
        $this->userService->checkIfUserExists($userId);
        $this->userService->ensurePassengerAndStaff($userId);

        $this->userService->checkIfUserExists($passengerId);

        // Vérification qu'il s'agit bien du passager
        if ($this->userService->isPassenger($userId)) {
            if ($userId !== $passengerId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->rideRepository->fetchPastRidesByPassenger($passengerId);
    }

    //------- Pour le staff uniquement ---------
    /**
     * Permet à un membre du personnel de récupèrer la liste des objets Booking par date de création.
     *
     * @param DateTimeInterface $creationDate
     * @param integer $staffId
     * @return array
     */
    public function listRidesByCreationDate(
        DateTimeInterface $creationDate,
        int $staffId
    ): array {

        $this->userService->checkIfUserExists($staffId);
        $this->userService->ensureStaff($staffId);

        return $this->rideRepository->findAllRidesByCreationDate($creationDate);
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

        $this->userService->checkIfUserExists($adminId);
        $this->userService->ensureAdmin($adminId);

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

        $this->userService->checkIfUserExists($adminId);
        $this->userService->ensureAdmin($adminId);

        return $this->rideRepository->countRidesByPeriod($start, $end);
    }

    /**
     * Permet à un admin de récupèrer le total des commissions gagnées.
     *
     * @param integer $adminId
     * @return array
     */
    public function getTotalCommission(
        int $adminId
    ): array {

        $this->userService->checkIfUserExists($adminId);
        $this->userService->ensureAdmin($adminId);

        return $this->rideRepository->countCommissionByFields([]);
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

        $this->userService->checkIfUserExists($adminId);
        $this->userService->ensureAdmin($adminId);

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

        $this->userService->checkIfUserExists($adminId);
        $this->userService->ensureAdmin($adminId);

        return $this->rideRepository->countCommissionByPeriod($start, $end);
    }
}
