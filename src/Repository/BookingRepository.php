<?php

namespace App\Repositories;

use App\Enum\BookingStatus;
use App\Models\Booking;
use App\Repository\BaseRepository;
use PDO;
use InvalidArgumentException;

/**
 * Cette classe gére la correspondance entre un trajet et des utilisateurs et la BDD.
 */

class BookingRepository extends BaseRepository
{

    /**
     * @var string Le nom de la table en BDD
     */
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

    public function hydrateBooking(array $data): Booking
    {
        $ride = $this->rideRepository->findRideById($data['ride_id']);
        $passenger = $this->userRepository->findUserById($data['passenger_id']);
        $driver = $this->userRepository->findUserById($data['driver_id']);

        if (!$ride) {
            throw new InvalidArgumentException("Le trajet pour la réservation {$data['booking_id']} est introuvable.");
        }

        if (!$passenger) {
            throw new InvalidArgumentException("Aucun passager pour la réservation {$data['booking_id']} n'est introuvable.");
        }

        if (!$driver) {
            throw new InvalidArgumentException("Le conducteur pour la réservation {$data['booking_id']} est introuvable.");
        }

        return new Booking(
            bookingId: (int)$data['booking_id'],
            ride: $ride,
            passenger: $passenger,
            driver: $driver,
            bookingStatus: BookingStatus::from($data['booking_status']),
            createdAt: !empty($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : null,
            updatedAt: !empty($data['updated_at']) ? new \DateTimeImmutable($data['updated_at']) : null
        );
    }


    private function mapBookingToArray(Booking $booking): array
    {
        return [
            'ride' => $booking->getBookingRide()->getRideId(),
            'passenger' => $booking->getBookingPassenger()->getUserId(),
            'driver' => $booking->getBookingDriver()->getUserId(),
            'status' => $booking->getBookingStatus()->value,

        ];
    }


    // ------ Récupération ------ 

    public function findBookingById(int $bookingId): ?Booking
    {
        $row = parent::findById($bookingId);
        return $row ? $this->hydrateBooking((array) $row) : null;
    }

    public function findAllBookings(int $limit = 50, int $offset = 0, ?string $orderBy = null, string $orderDirection = 'DESC'): array
    {
        $rows = parent::findAll($limit, $offset, $orderBy, $orderDirection);
        return array_map(fn($row) => $this->hydrateBooking((array) $row), $rows);
    }



    // ------ Mise à jour ------ 

    public function updateBooking(Booking $booking): bool
    {
        return $this->updateById($booking->getBookingId(), $this->mapBookingToArray($booking));
    }

    // ------ Insertion ------ 

    public function insertBooking(Booking $booking): int
    {
        return $this->insert($this->mapBookingToArray($booking));
    }

    // ------ Suppression ------ 

    public function deleteBooking(int $bookingId): bool
    {
        return $this->deleteById($bookingId);
    }
}
