<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\User;
use App\Models\Ride;
use App\Repositories\BookingRepository;
use App\Repositories\UserRepository;
use App\Repositories\RideRepository;
use PDO;
use InvalidArgumentException;

/**
 * Cette classe gére la correspondance entre des utilisateurs et un trajet
 */

class BookingRelationsRepository extends BookingRepository
{

    protected string $table = 'bookings';
    protected string $primaryKey = 'booking_id';

    private UserRepository $userRepository;
    private RideRepository $rideRepository;

    public function __construct(PDO $db, UserRepository $userRepository, RideRepository $rideRepository)
    {
        parent::__construct($db);
        $this->userRepository = $userRepository;
        $this->rideRepository = $rideRepository;
    }


    public function hydrateBookingRelation(Booking $booking): Booking
    {
        $ride = $this->rideRepository->findRideById($booking['ride_id']);
        $passenger = $this->userRepository->findUserById($booking['passenger_id']);
        $driver = $this->userRepository->findUserById($booking['driver_id']);

        if (!$ride) {
            throw new InvalidArgumentException("Le trajet pour la réservation {$booking['booking_id']} est introuvable.");
        }

        if (!$passenger) {
            throw new InvalidArgumentException("Aucun passager pour la réservation {$booking['booking_id']} n'est trouvable.");
        }

        if (!$driver) {
            throw new InvalidArgumentException("Le conducteur pour la réservation {$booking['booking_id']} est introuvable.");
        }
        return new Booking(
            bookingId: $booking->getBookingId(),
            ride: $ride,
            passenger: $passenger,
            driver: $driver,
            bookingStatus: $booking->getBookingStatus(),
        );
    }


    // Récuperer une réservation avec le trajet et les passagers.
    //public function findBookingByRideAndPassenger(): ?Booking {}

    // Récupérer une réservation avec le trajet et les utilisateurs participants
    //public function findBookingWithRideAndUsers():?Booking {}
}
