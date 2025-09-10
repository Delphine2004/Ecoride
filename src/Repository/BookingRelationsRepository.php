<?php

namespace App\Repositories;

use App\Repositories\BookingRepository;
use App\Repositories\UserRepository;
use App\Repositories\RidewithUsersRepository;
use App\Models\Booking;
use App\Models\User;
use PDO;
use InvalidArgumentException;

/**
 * Cette classe gére la correspondance entre une réservation et des utilisateurs et la BDD.
 */

class BookingRelationsRepository extends BookingRepository
{

    protected string $table = 'bookings';
    protected string $primaryKey = 'booking_id';

    public function __construct(
        PDO $db,
        private RidewithUsersRepository $rideWithUserRepository,
        private RideRepository $rideRepository,
        private UserRepository $userRepository
    ) {
        parent::__construct($db);
    }

    //  ------ Récupérations spécifiques ---------

    /**
     * Récupére un objet Booking avec la liste des objets Ride et User (conducteur et passagers).
     *
     * @param integer $bookingId
     * @return Booking|null
     */
    public function findBookingWithRideAndUsersByBookingId(
        int $bookingId,
    ): ?Booking {

        // Récuperation de la réservation
        $booking = $this->findBookingById($bookingId);
        if (!$booking) {
            return null;
        }

        // Récupération du trajet et des utilisateurs
        $ride = $this->rideWithUserRepository->findRideWithUsersByRideId($booking->getBookingRideId());

        if ($ride) {
            $booking->setBookingRide($ride);
        }

        return $booking;
    }

    /**
     * Récupére la liste des objets Bookings avec la liste brute du trajet et des participants avec tri et pagination.
     *
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllBookingsWithRideAndUsers(
        string $orderBy = 'user_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        // Récuperation de la réservation
        $bookings = $this->findAllBookingsByFields([], $orderBy, $orderDirection, $limit, $offset);

        foreach ($bookings as $booking) {
            // Récupération du trajet
            $ride = $this->rideWithUserRepository->findRideWithUsersByRideId($booking->getRideId());
            if ($ride) {
                $booking->setBookingRide($ride);
            }
        }
        return $bookings;
    }

    /**
     * Récupére la liste brute des réservations avec la liste brute du trajet et des participants avec tri et pagination.
     *
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function fetchAllBookingsWithRideAndUsers(
        string $orderBy = 'user_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        // Récuperation de la réservation
        $bookings = $this->fetchAllBookingsRowsByFields([], $orderBy, $orderDirection, $limit, $offset);

        foreach ($bookings as $i => $booking) {
            // Récupération du trajet et des utilisateurs
            $ride = $this->rideWithUserRepository->findRideById($booking['ride_id']);
            $bookings[$i]['ride'] = $ride;
        }
        return $bookings;
    }
}
