<?php

namespace App\Service;

use App\Repository\RideRepository;
use App\Repository\BookingRepository;

use App\Security\AuthService;
use App\Service\NotificationService;
use App\Service\CarService;

use App\Model\Ride;
use App\Model\Booking;
use App\Model\User;

use App\Enum\RideStatus;
use App\Enum\BookingStatus;

use App\DTO\CreateRideDTO;
use App\DTO\UpdateUserDTO;

use DateTimeImmutable;
use InvalidArgumentException;


class RideService
{
    public function __construct(
        protected RideRepository $rideRepository,
        protected BookingRepository $bookingRepository,
        protected AuthService $authService,
        protected CarService $carService,
        protected NotificationService $notificationService
    ) {}

    //--------------VERIFICATIONS-----------------

    /**
     * Vérifie que le trajet existe.
     *
     * @param integer $rideId
     * @return void
     */
    public function checkIfRideExists(
        int $rideId
    ): void {
        // Récupération de l'entité Ride
        $ride = $this->rideRepository->findRideById($rideId);

        // Vérification de l'existence du trajet
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }
    }

    /**
     * Vérifie que la réservation existe.
     *
     * @param integer $bookingId
     * @return void
     */
    public function checkIfBookingExists(
        int $bookingId
    ): void {
        // Récupération de l'entité Booking
        $booking = $this->bookingRepository->findBookingById($bookingId);

        // Vérification de l'existence de la réservation
        if (!$booking) {
            throw new InvalidArgumentException("Réservation introuvable.");
        }
    }

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

    //-----------------ACTIONS RIDE------------------------------

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

        $this->authService->checkIfUserExists($userId);
        $this->authService->ensureDriver($userId);

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
        $driver = $this->authService->getUserById($userId);


        // Déduction de la commission au conducteur
        $commission = $ride->getRideCommission();

        // Vérifier que le conducteur a assez de crédit pour payer la commission
        if ($driver->getUserCredits() < $commission) {
            throw new InvalidArgumentException("Crédits insuffisants pour créer ce trajet.");
        }

        $newCreditsBalance = $driver->getUserCredits() - $commission;

        $userDto = new UpdateUserDTO(['credits' => $newCreditsBalance]);


        // Enregistrement de la modification des crédits
        $this->authService->updateProfile($userDto, $userId);

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

        $this->authService->checkIfUserExists($userId);
        $this->authService->ensureDriverAndStaff($userId);

        $ride = $this->rideRepository->findRideById($rideId);

        // Vérification qu'il s'agit bien du conducteur
        if ($this->authService->isDriver($userId)) {
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
        $bookings = $this->bookingRepository->findAllBookingByRideId($rideId);

        foreach ($bookings as $booking) {
            // Récupérer le passager
            $passengerId = $booking->getBookingPassengerId();
            $passenger = $booking->getBookingPassenger();


            // Mise à jour du statut de la réservation 
            $booking->setBookingStatus(BookingStatus::ANNULEE);

            // Enregistrement dans la bd
            $this->bookingRepository->updateBooking($booking);


            // Défini le remboursement au passager
            $newCreditsBalance = $passenger->getUserCredits() + $ride->getRidePrice();

            $passengerDto = new UpdateUserDTO(['credits' => $newCreditsBalance]);

            // Enregistrement des crédits du passager en BD
            $this->authService->updateProfile($passengerDto, $userId);

            // Envoi de la notification
            $this->notificationService->sendRideCancelationToPassenger($passenger, $ride);
        }

        // Enregistrement du statut du trajet en BD
        $this->rideRepository->updateRide($ride, [
            'ride_status' => $ride->getRideStatus()
        ]);


        // Envoi de confirmation
        $driver = $this->authService->getUserById($userId);
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
        int $rideId,
        int $userId
    ): Ride {

        $this->checkIfRideExists($rideId);

        $this->authService->checkIfUserExists($userId);
        $this->authService->ensureDriverAndStaff($userId);

        // Récupération de l'objet Ride
        $ride = $this->rideRepository->findRideById($rideId);

        // Vérification du status du trajet.
        if ($ride->getRideStatus() !== RideStatus::DISPONIBLE && $ride->getRideStatus() !== RideStatus::COMPLET) {
            throw new InvalidArgumentException("Le trajet n'a pas le statut DISPONIBLE ou COMPLET.");
        }

        // Vérification qu'il s'agit bien du conducteur
        if ($this->authService->isDriver($userId)) {
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

        // Mise à jour du statut et envoi de notification
        foreach ($passengers as $passenger) {
            $booking = $this->bookingRepository->findBookingByPassengerAndRide($passenger, $rideId);

            $booking->setBookingStatus(BookingStatus::ENCOURS);

            $this->bookingRepository->updateBooking($booking);

            $this->notificationService->sendRideStartToPassenger($passenger, $ride);
        }

        // Notification du conducteur
        $driver = $this->authService->getUserById($userId);
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

        $this->authService->checkIfUserExists($userId);
        $this->authService->ensureDriverAndStaff($userId);

        // Récupération de l'objet Ride
        $ride = $this->rideRepository->findRideById($rideId);

        // Vérification qu'il s'agit bien du conducteur
        if ($this->authService->isDriver($userId)) {
            if ($ride->getRideDriverId() !== $userId) {
                throw new InvalidArgumentException("Le conducteur ne correspond pas à ce trajet.");
            }
        }


        // Vérification du status de la réservation.
        if ($ride->getRideStatus() !== RideStatus::ENCOURS) {
            throw new InvalidArgumentException("Le trajet n'a pas le statut ENCOURS.");
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

        // Récupération de l'utilisateur
        $driver = $this->authService->getUserById($userId);

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
        $this->authService->checkIfUserExists($userId);

        $this->authService->ensureDriverAndStaff($userId);

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
            $booking = $this->bookingRepository->findBookingByPassengerAndRide($passengerId, $ride->getRideId());

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
            $driver = $this->authService->getUserById($userId);

            $this->authService->checkIfUserExists($driver->getUserId());


            // Ajout des crédits au conducteur
            $newCreditsBalance = $driver->setUserCredits($driver->getUserCredits() + $totalCredits);

            $userDto = new UpdateUserDTO(['credits' => $newCreditsBalance]);

            $this->authService->updateProfile($userDto, $driver->getUserId());

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


    //-----------------ACTIONS BOOKING--------------------------

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
    ): Booking {

        $this->authService->checkIfUserExists($userId);
        $this->authService->isPassenger($userId);


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
            $rideStatus = $ride->setRideStatus(RideStatus::COMPLET);
        }

        // Décrémentation les crédits du passager
        $creditsToDeduct =  $ride->getRidePrice();
        $newCreditsBalance = $passenger->getUserCredits() - $creditsToDeduct;

        $userDto = new UpdateUserDTO(['credits' => $newCreditsBalance]);

        //Enregistrement des modifications
        $this->authService->updateProfile($userDto, $passenger->getUserId());
        $this->bookingRepository->insertBooking($booking);
        $this->rideRepository->updateRide($ride);

        // Notification
        $this->notificationService->sendRideConfirmationToPassenger($passenger, $ride);

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
    ): Booking {

        $this->checkIfBookingExists($bookingId);
        $this->authService->checkIfUserExists($userId);
        $this->authService->ensurePassengerAndStaff($userId);

        // Récupération de l'entité Booking
        $booking = $this->bookingRepository->findBookingById($bookingId);


        // Vérification que la réservation appartient au passager
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
        $this->rideRepository->updateRide($ride);

        // Enregistrement des modifications de réservation en BD
        $this->bookingRepository->updateBooking($booking);

        // Récupération des utilisateurs
        $passenger = $booking->getBookingPassenger();
        $driver = $booking->getBookingDriver();

        // Préparation des variables
        $today = (new DateTimeImmutable());
        $rideDate = $ride->getRideDepartureDateTime();
        $refundableDeadLine = (clone $rideDate)->modify('-2 days');

        // Vérification des conditions d'annulation
        if ($today <= $refundableDeadLine) {

            // Envoi des confirmations sans frais
            $this->notificationService->sendBookingCancelationToPassenger($passenger, $booking);
            $this->notificationService->sendBookingCancelationToDriver($driver, $booking);

            // Remboursement
            $newCreditsBalance = $passenger->getUserCredits() + $ride->getRidePrice();

            $passengerDto = new UpdateUserDTO(['credits' => $newCreditsBalance]);

            // Enregistrement des modifications de l'utilisateur en BD
            $this->authService->updateProfile($passengerDto, $userId);
        } else {

            // Envoi des confirmations avec frais
            $this->notificationService->sendBookingLateCancelationToPassenger($passenger, $booking);
            $this->notificationService->sendBookingLateCancelationToDriver($driver, $booking);
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
    ): Booking {
        $this->checkIfBookingExists($bookingId);

        // Récupération de la réservation
        $booking = $this->bookingRepository->findBookingById($bookingId);

        // Vérification du status de la réservation.
        if ($booking->getBookingStatus() !== BookingStatus::ENCOURS) {
            throw new InvalidArgumentException("La réservation n'a pas le statut ENCOURS.");
        }

        $this->authService->checkIfUserExists($userId);
        $this->authService->ensurePassengerAndStaff($userId);

        // Vérification qu'il s'agit bien du passager
        if ($this->authService->isPassenger($userId)) {
            if ($booking->getBookingPassengerId() !== $userId) {
                throw new InvalidArgumentException("Le passager ne correspond pas à ce trajet.");
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

        return $booking;
    }


    //------------------RECUPERATIONS RIDE------------------------

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
        $this->authService->checkIfUserExists($userId);
        $this->authService->ensureUser($userId);
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

    //------------------RECUPERATIONS BOOKING------------------------

    /**
     * Permet à un utilisateur PASSAGER OU EMPLOYE OU ADMIN de récupèrer une réservations.
     *
     * @param integer $bookingId
     * @param integer $userId
     * @return Booking|null
     */
    public function getBooking(
        int $bookingId
    ): Booking {
        return $this->bookingRepository->findBookingById($bookingId);
    }

    public function getBookingByPassengerAndRide(
        int $passengerId,
        int $rideId
    ): Booking {
        return $this->bookingRepository->findBookingByPassengerAndRide($passengerId, $rideId);
    }
}
