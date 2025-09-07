<?php

namespace App\Service;

use App\Repositories\RideWithUsersRepository;
use App\Repositories\BookingRelationsRepository;
use App\Services\BaseService;
use App\Models\Ride;
use App\Models\Booking;
use App\Enum\RideStatus;
use InvalidArgumentException;

class RideService extends BaseService
{

    public function __construct(
        private RideWithUsersRepository $rideWithUserRepository,
        private BookingRelationsRepository $bookingRelationsRepository
    ) {}

    // Vérifie que le trajet a encore des places disponibles.
    public function hasAvailableSeat(int $rideId): bool
    {
        $ride = $this->rideWithUserRepository->findRideById($rideId);
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }
        return $ride->getRideAvailableSeats() > 0;
    }


    //------------------CONDUCTEUR-----------------------------
    // Permet à un utilisateur CONDUCTEUR de rajouter un trajet.
    public function addRide(int $userId, Ride $ride): int
    {
        $this->ensureDriver($userId);
        return $this->rideWithUserRepository->insertRide($ride);
    }

    // Permet à un utilisateur CONDUCTEUR d'annuler un trajet.
    public function cancelRide(int $userId, int $rideId): void
    {
        $this->ensureDriver($userId);

        // Vérifications
        $ride = $this->rideWithUserRepository->findRideById($rideId);
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }

        if ($ride->getRideDriverId() !== $userId) {
            throw new InvalidArgumentException("Seulement le conducteur associé au trajet peut annuler son trajet.");
        }

        $ride->setRideStatus(RideStatus::ANNULE);

        $this->rideWithUserRepository->updateById($rideId, ['ride_status' => $ride->getRideStatus()]);
    }


    //------------------PASSAGER-----------------------------
    public function bookRide(int $userId, int $rideId): int
    {
        $this->ensurePassenger($userId);

        $ride = $this->rideWithUserRepository->findRideById($rideId);

        //Vérifications
        if (!$ride) {
            throw new InvalidArgumentException("Trajet introuvable.");
        }

        if ($ride->getRideAvailableSeats() <= 0) {
            throw new InvalidArgumentException("Trajet complet.");
        }

        if ($this->bookingRelationsRepository->userHasBooking($rideId, $userId)) {
            throw new InvalidArgumentException("Vous avez déjà réservé ce trajet.");
        }

        // Décrémentation
        $ride->decrementAvailableSeats();


        // modifier la disponibilité dans la BD.
        $this->rideWithUserRepository->updateById($rideId, [
            'available_seats' => $ride->getRideAvailableSeats()
        ]);

        // créer la réservation
        $booking = new Booking($rideId, $userId);

        // Ajout de la réservation
        return $this->bookingRelationsRepository->insertBooking($booking);
    }


    //------------------RECUPERATIONS------------------------
    // Récupére un trajet avec les passagers.
    public function getRideWithPassengers(int $rideId): ?Ride
    {
        return $this->rideWithUserRepository->findRideWithUsersByRideId($rideId);
    }

    //--------------------------------------------------
    // Récupére la liste brute des trajets d'un utilisateur CONDUCTEUR.
    public function getAllRidesByDriver(int $userId): array
    {
        $this->ensureDriver($userId);
        return $this->rideWithUserRepository->fetchAllRidesByDriver($userId);
    }

    // Récupére la liste d'objet Ride à venir d'un utilisateur CONDUCTEUR.
    public function getUpcomingRidesByDriver(int $userId): array
    {
        $this->ensureDriver($userId);
        return $this->rideWithUserRepository->findUpcomingRidesByDriver($userId);
    }

    // Récupére la liste brute des trajets passés d'un utilisateur CONDUCTEUR.
    public function getPastRidesByDriver(int $userId): array
    {
        $this->ensureDriver($userId);
        return $this->rideWithUserRepository->fetchPastRidesByDriver($userId);
    }
}
