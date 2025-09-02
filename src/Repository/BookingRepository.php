<?php

//------A VERIFIER ET A DOCUMENTER


namespace App\Repositories;

use App\Repositories\BaseRepository;
use App\Models\Booking;
use App\Enum\BookingStatus;
use PDO;

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

    /**
     * Remplit un objet Booking avec les données brute de la table bookings.
     *
     * @param array $data
     * @return Booking
     */
    public function hydrateBooking(array $data): Booking
    {
        return new Booking(
            bookingId: (int)$data['booking_id'],
            ride: null, // car pas encore chargé
            passenger: null, // car pas encore chargé
            driver: null, // car pas encore chargé
            bookingStatus: BookingStatus::from($data['booking_status']),
            createdAt: !empty($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : null,
            updatedAt: !empty($data['updated_at']) ? new \DateTimeImmutable($data['updated_at']) : null
        );
    }

    /**
     * Transforme Booking en tableau pour insert et update.
     *
     * @param Booking $booking
     * @return array
     */
    private function mapBookingToArray(Booking $booking): array
    {
        return [
            'ride_id' => $booking->getBookingRide()?->getRideId(),
            'passenger_id' => $booking->getBookingPassenger()?->getUserId(),
            'driver_id' => $booking->getBookingDriver()?->getUserId(),
            'booking_status' => $booking->getBookingStatus()->value,

        ];
    }


    // ------ Récupération ------ 

    /**
     * Récupére une réservation par son id.
     *
     * @param integer $bookingId
     * @return Booking|null
     */
    public function findBookingById(int $bookingId): ?Booking
    {
        $row = parent::findById($bookingId);
        return $row ? $this->hydrateBooking((array) $row) : null;
    }

    /**
     * Récupére toutes les réservations avec pagination et tri.
     *
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllBookings(
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        $rows = parent::findAll($orderBy, $orderDirection, $limit, $offset);
        return array_map(fn($row) => $this->hydrateBooking((array) $row), $rows);
    }

    /**
     * Récupére une réservation selon un champ spécifique.
     *
     * @param string $field
     * @param mixed $value
     * @return Booking|null
     */
    public function findBookingByField(
        string $field,
        mixed $value
    ): ?Booking {
        $row = parent::findOneByField($field, $value);
        return $row ? $this->hydrateBooking((array) $row) : null;
    }

    /**
     * Récupére toutes les réservations selon un champ spécifique avec pagination et tri.
     *
     * @param string $field
     * @param mixed $value
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @param [type] $extraValue
     * @return array
     */
    public function findAllBookingsByField(
        string $field,
        mixed $value,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0,
        $extraValue = null
    ): array {
        $rows = parent::findAllByField($field, $value, $orderBy, $orderDirection, $limit, $offset, $extraValue);
        return array_map(fn($row) => $this->hydrateBooking((array) $row), $rows);
    }


    //------ Récupération spécifique-----

    /**
     * Récupére toutes les réservations d'un trajet par l'id des trajets.
     *
     * @param integer $rideId
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllBookingsByRideId(
        int $rideId,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->findAllBookingsByField('ride_id', $rideId, $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupére toutes les réservations par le statut
     *
     * @param BookingStatus $bookingStatus
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllBookingsByStatus(
        BookingStatus $bookingStatus,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->findAllBookingsByField('booking_status', $bookingStatus->value, $orderBy, $orderDirection, $limit, $offset);
    }


    // ----------- Pour les conducteurs --------------------
    /**
     * Récupére la réservation d'un conducteur
     *
     * @param integer $driverId
     * @return Booking|null
     */
    public function findBookingByDriver(int $driverId): ?Booking
    {
        return $this->findBookingByField('driver_id', $driverId);
    }

    /**
     * Récupére toutes les réservations d'un conducteur
     *
     * @param integer $driverId
     * @param BookingStatus|null $bookingStatus
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllBookingsByDriver(
        int $driverId,
        ?BookingStatus $bookingStatus = null,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->findAllBookingsByField('driver_id', $driverId, $orderBy, $orderDirection, $limit, $offset, $bookingStatus?->value);
    }

    /**
     * Récupére toutes les réservations à venir pour un conducteur
     *
     * @param integer $driverId
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findUpcomingBookingsByDriver(
        int $driverId,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->findAllBookingsByDriver($driverId, BookingStatus::CONFIRMEE, $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupére toutes les réservations à venir pour un chauffeur
     *
     * @param integer $driverId
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findPastBookingsByDriver(
        int $driverId,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->findAllBookingsByDriver($driverId, BookingStatus::PASSEE, $orderBy, $orderDirection, $limit, $offset);
    }


    // ----------- Pour les passagers --------------------
    /**
     * Récupére la réservation d'un passager
     *
     * @param integer $passengerId
     * @return Booking|null
     */
    public function findBookingByUser(int $passengerId): ?Booking
    {
        return $this->findBookingByField('passenger_id', $passengerId);
    }

    /**
     * Récupére toutes les réservations d'un passager
     *
     * @param integer $passengerId
     * @param BookingStatus|null $bookingStatus
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllBookingsByPassenger(
        int $passengerId,
        ?BookingStatus $bookingStatus = null,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->findAllBookingsByField('passenger_id', $passengerId, $orderBy, $orderDirection, $limit, $offset, $bookingStatus?->value);
    }

    /**
     * Récupére toutes les réservations à venir pour un passager
     *
     * @param integer $passengerId
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findUpcomingBookingsByPassenger(
        int $passengerId,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->findAllBookingsByPassenger($passengerId, BookingStatus::CONFIRMEE, $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupére toutes les réservations à venir pour un passager
     *
     * @param integer $passengerId
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findPastBookingsByPassenger(
        int $passengerId,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->findAllBookingsByPassenger($passengerId, BookingStatus::PASSEE, $orderBy, $orderDirection, $limit, $offset);
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
