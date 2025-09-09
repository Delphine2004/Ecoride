<?php

namespace App\Services;

use App\Repositories\RideWithUsersRepository;
use App\Repositories\BookingRelationsRepository;
use app\Repositories\UserRelationsRepository;
use App\Services\BaseService;
use App\Services\BookingService;
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
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }


    //-----------------ACTIONS------------------------------

    // Permet à un utilisateur CONDUCTEUR de rajouter un trajet.
    public function addRide(Ride $ride, int $userId): int
    {
        // Vérification de la permission
        $this->ensureDriver($userId);

        // Récupération du chauffeur
        $driver = $ride->getRideDriver($userId);

        // Déduction de la commission
        $commission = $ride->getRideCommission();
        $driver->setCredits($driver->getCredits() - $commission);

        // Enregistrement de la modification des crédits
        $this->userRelationsRepository->updateUser(
            $driver,
            [
                'credits' => $driver->getCredits()
            ]
        );


        return $this->rideWithUserRepository->insertRide($ride);
    }

    // Permet à un utilisateur PASSAGER de réserver un trajet.
    public function bookRide(int $rideId, int $passengerId): Booking
    {
        // Vérification de la permission
        $this->ensurePassenger($passengerId);

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

        // Récupération du chauffeur aprés avoir validé Ride
        $driver = $ride->getRideDriver();
        // Vérification de l'existance du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Conducteur introuvable.");
        }

        // Vérification des crédits du passager
        $passenger = $this->userRelationsRepository->findUserById($passengerId);
        if ($passenger->getCredits() < $ride->getRidePrice()) {
            throw new InvalidArgumentException("Crédits insuffisants.");
        }


        // Décrémentation les crédits du passager
        $passenger->setCredits($passenger->getCredits() - $ride->getRidePrice());
        $this->userRelationsRepository->updateUser(
            $passenger,
            [
                'credits' => $passenger->getCredits()
            ]
        );

        // Création de la réservation - dédrémentation du siége incluse
        $booking = $this->bookingService->createBooking($ride, $driver, $passenger);

        // Notification
        $this->notificationService->sendRideConfirmationToPassenger($passenger, $ride);

        return $booking;
    }

    // Permet à un utilisateur CONDUCTEUR d'annuler un trajet.
    public function cancelRide(int $rideId, int $userId): Ride
    {
        // Vérification des permissions
        $this->ensureDriver($userId);

        // Récupération de l'entité Ride
        $ride = $this->rideWithUserRepository->findRideById($rideId);

        // Vérification de l'existence du trajet
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }

        // Vérification qu'il s'agit bien du conducteur
        if ($ride->getRideDriverId() !== $userId) {
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
            $booking->setBookingStatus(BookingStatus::ANNULEE);
            $this->bookingRelationsRepository->updateBooking($booking->getBookingId(), [
                'booking_status' => $booking->getBookingStatus()
            ]);

            // Trouver le passager
            $passenger = $this->userRelationsRepository->findUserById($booking->getPassengerId());

            // Définir le remboursement au passager
            $passenger->setCredits($passenger->getCredits() + $ride->getRidePrice());

            // Enregistrer les crédits du passager en BD
            $this->userRelationsRepository->updateUser($passenger, [
                'credits' => $passenger->getCredits()
            ]);

            // Notifier 
            $this->notificationService->sendRideCancelationToPassenger($passenger, $ride);
        }

        // Enregistrement du statut du trajet en BD
        $this->rideWithUserRepository->updateRide($ride, [
            'ride_status' => $ride->getRideStatus()
        ]);


        //Envoi de confirmation
        $driver = $ride->getRideDriver();
        $this->notificationService->sendRideCancelationToDriver($driver, $ride);

        return $ride;
    }

    // Permet à un utilisateur CONDUCTEUR de démarrer un trajet
    public function startRide(int $rideId, int $driverId): void
    {
        // Vérification des permissions
        $this->ensureDriver($driverId);

        // Récupération du trajet
        $ride = $this->rideWithUserRepository->findRideById($rideId);

        // Vérification de l'existence du trajet
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }

        // Vérification qu'il s'agit bien du conducteur
        if ($ride->getRideDriverId() !== $driverId) {
            throw new InvalidArgumentException("Seulement le conducteur associé au trajet peut demarrer son trajet.");
        }

        // Vérification du status du trajet.
        if ($ride->getRideStatus() !== RideStatus::DISPONIBLE && $ride->getRideStatus() !== RideStatus::COMPLET) {
            throw new InvalidArgumentException("Le trajet n'a pas le statut DISPONIBLE ou COMPLET.");
        }

        // Modification du status
        $ride->setRideStatus(RideStatus::ENCOURS);

        // Enregistrement dans la BD
        $this->rideWithUserRepository->updateRide($ride, [
            'ride_status' => $ride->getRideStatus()
        ]);
    }

    // Permet à un utilisateur CONDUCTEUR de finaliser un trajet
    public function finalizeRide(int $rideId, int $driverId): void
    {
        // Vérification des permissions
        $this->ensureDriver($driverId);

        // Récupération du trajet
        $ride = $this->rideWithUserRepository->findRideById($rideId);

        // Vérification de l'existence du trajet
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }

        // Vérification qu'il s'agit bien du conducteur
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
        $driver->setCredits($driver->getCredits() + $totalCredits);
        $this->userRelationsRepository->updateUser(
            $driver,
            [
                'credits' => $driver->getCredits()
            ]
        );

        // Notification du conducteur
        $this->notificationService->sendRideFinalizationToDriver($driver, $ride);
    }



    //------------------RECUPERATIONS------------------------

    // Récupére un trajet avec les passagers.
    public function getRideWithPassengers(int $rideId): ?Ride
    {
        return $this->rideWithUserRepository->findRideWithUsersByRideId($rideId);
    }

    //-------------Pour les conducteurs------------------
    // Récupére la liste brute des trajets d'un utilisateur CONDUCTEUR.
    public function getAllRidesByDriver(int $driverId): array
    {
        $this->ensureDriver($driverId);
        return $this->rideWithUserRepository->fetchAllRidesByDriver($driverId);
    }

    // Récupére la liste d'objet Ride à venir d'un utilisateur CONDUCTEUR.
    public function getUpcomingRidesByDriver(int $driverId): array
    {
        $this->ensureDriver($driverId);
        return $this->rideWithUserRepository->findUpcomingRidesByDriver($driverId);
    }

    // Récupére la liste brute des trajets passés d'un utilisateur CONDUCTEUR.
    public function getPastRidesByDriver(int $driverId): array
    {
        $this->ensureDriver($driverId);
        return $this->rideWithUserRepository->fetchPastRidesByDriver($driverId);
    }


    //-------------Pour les Passagers------------------
    // Récupére la liste brute des trajets d'un utilisateur PASSAGER.
    public function getAllRidesByPassenger(int $passengerId): array
    {
        $this->ensurePassenger($passengerId);
        return $this->rideWithUserRepository->fetchAllRidesByPassenger($passengerId);
    }

    // Récupére la liste d'objet Ride à venir d'un utilisateur PASSAGER.
    public function getUpcomingRidesByPassenger(int $passengerId): array
    {
        $this->ensurePassenger($passengerId);
        return $this->rideWithUserRepository->findUpcomingRidesByPassenger($passengerId);
    }

    // Récupére la liste brute des trajets passés d'un utilisateur PASSAGER.
    public function getPastRidesByPassenger(int $passengerId): array
    {
        $this->ensurePassenger($passengerId);
        return $this->rideWithUserRepository->fetchPastRidesByPassenger($passengerId);
    }

    //-------------Pour les Admins------------------
    // Récupére le nombre de trajets effectués pour le jour J.
    public function getNumberOfRidesFromToday(int $adminId): ?array
    {
        $this->ensureAdmin($adminId);
        return $this->rideWithUserRepository->countRidesByToday();
    }

    // Récupére le nombre de trajets effectués sur une période donnée.
    public function getNumberOfRidesOverPeriod(
        int $adminId,
        DateTimeInterface $start,
        DateTimeInterface $end
    ): ?array {
        $this->ensureAdmin($adminId);
        return $this->rideWithUserRepository->countRidesByPeriod($start, $end);
    }

    // Récupére le nombre de commission gagné pour le jour J.
    public function getTotalCommissionFromToday(int $adminId): ?array
    {
        $this->ensureAdmin($adminId);
        return $this->rideWithUserRepository->countCommissionByToday();
    }

    // Récupére le nombre de commission gagné sur une période donnée.
    public function getTotalCommissionOverPeriod(
        int $adminId,
        DateTimeInterface $start,
        DateTimeInterface $end
    ): ?array {
        $this->ensureAdmin($adminId);
        return $this->rideWithUserRepository->countCommissionByPeriod($start, $end);
    }
}
