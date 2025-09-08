<?php

namespace App\Service;

use App\Repositories\RideWithUsersRepository;
use App\Repositories\BookingRelationsRepository;
use app\Repositories\UserRelationsRepository;
use App\Services\BaseService;
use App\Service\BookingService;
use App\Models\Ride;
use App\Models\Booking;
use App\Enum\RideStatus;
use App\Enum\BookingStatus;
use InvalidArgumentException;

class RideService extends BaseService
{

    public function __construct(
        private RideWithUsersRepository $rideWithUserRepository,
        private BookingRelationsRepository $bookingRelationsRepository,
        private UserRelationsRepository $userRelationsRepository,
        private BookingService $bookingService
    ) {
        parent::__construct();
    }


    //-----------------ACTIONS------------------------------

    // Permet à un utilisateur CONDUCTEUR de rajouter un trajet.
    public function addRide(int $userId, Ride $ride): int
    {
        $this->ensureDriver($userId);
        return $this->rideWithUserRepository->insertRide($ride);
    }

    // Permet à un utilisateur PASSAGER de réserver un trajet.
    public function bookRide(int $rideId, int $passengerId): Booking
    {
        // Vérification de la permission
        $this->ensurePassenger($passengerId);

        // Récupération du trajet
        $ride = $this->rideWithUserRepository->findRideById($rideId);


        //Vérifications
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }
        // Vérification du remplissage du trajet
        if (!$ride->hasAvailableSeat()) {
            throw new InvalidArgumentException("Trajet complet.");
        }

        //Récupération du chauffeur aprés avoir validé Ride
        $driver = $ride->getRideDriver();
        //Vérification de l'existance du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Conducteur introuvable.");
        }

        // Vérifier que le passager a assez de crédits
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

        // Création de la réservation - dédrémentation incluse
        $booking = $this->bookingService->createBooking($ride, $driver, $passenger);

        //Envoi de confirmation à placer ici

        return $booking;
    }

    // Permet à un utilisateur CONDUCTEUR d'annuler un trajet.
    public function cancelRide(int $userId, int $rideId): void
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
        if ($ride->getRideStatus() === RideStatus::ANNULE) {
            throw new InvalidArgumentException("Le trajet est déjà annulée.");
        }

        // Mise à jour du status
        $ride->setRideStatus(RideStatus::ANNULE);

        // Récupération des réservations et mise à jour des réservations
        $bookings = $this->bookingRelationsRepository->findBookingByRideId($rideId);

        foreach ($bookings as $booking) {
            $booking->setBookingStats(BookingStatus::ANNULEE);
            $this->bookingRelationsRepository->updateBooking($booking->getBookingId(), [
                'booking_status' => $booking->getBookingStatus()
            ]);

            // Trouver le passager à chaque tour de boucle
            $passenger = $this->userRelationsRepository->findUserById($booking->getPassengerId());

            // Lui définir son remboursement
            $passenger->setCredits($passenger->getCredits() + $ride->getRidePrice());

            // Enregistrement de ses crédits en BD
            $this->userRelationsRepository->updateUser($passenger, [
                'credits' => $passenger->getCredits()
            ]);
        }

        // Enregistrement du statut du trajet en BD
        $this->rideWithUserRepository->updateById($rideId, [
            'ride_status' => $ride->getRideStatus()
        ]);

        // ---->> ENVOIE DE LA CONFIRMATION D'ANNULATION AU CONDUCTEUR ET AUX PASSAGERS
    }

    // Permet à un utilisateur CONDUCTEUR de démarrer un trajet
    public function startRide() {}

    // Permet à un utilisateur CONDUCTEUR de finaliser un trajet
    public function finalizeRide(int $rideId) {}

    //------------------RECUPERATIONS------------------------

    // Récupére un trajet avec les passagers.
    public function getRideWithPassengers(int $rideId): ?Ride
    {
        return $this->rideWithUserRepository->findRideWithUsersByRideId($rideId);
    }


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
}
