<?php

namespace App\Repositories;

use App\Repositories\BaseRepository;
use App\Models\Booking;
use App\Enum\BookingStatus;
use DateTimeInterface;
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

    public function __construct(
        PDO $db,
        private UserRepository $userRepository,
        private RideRepository $rideRepository
    ) {
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


    // ------ Récupérations ------ 

    /**
     * Récupére un objet Booking par son id.
     *
     * @param integer $bookingId
     * @return Booking|null
     */
    public function findBookingById(int $bookingId): ?Booking
    {
        // Chercher l'élément
        $row = parent::findById($bookingId);
        return $row ? $this->hydrateBooking((array) $row) : null;
    }

    /**
     * Récupére la liste des objets Booking avec pagination et tri.
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
        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'booking_id'
        );

        // Chercher les éléments.
        $rows = parent::findAll($orderBy, $orderDirection, $limit, $offset);
        return array_map(fn($row) => $this->hydrateBooking((array) $row), $rows);
    }

    /**
     * Récupére une liste brute de réservation avec pagination et tri.
     *
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function fetchAllBookingsRows(
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'booking_id'
        );
        // Chercher les éléments.
        $rows = parent::findAll($orderBy, $orderDirection, $limit, $offset);
        return $rows;
    }


    /**
     * Récupére un objet Booking selon un ou plusieurs champs spécifiques.
     *
     * @param array $criteria
     * @return Booking|null
     */
    public function findBookingByFields(
        array $criteria = []
    ): ?Booking {
        // Pas nécessaire de vérifier les champs car table pivot.
        // Chercher l'élément
        $row = parent::findOneByFields($criteria);
        return $row ? $this->hydrateBooking((array) $row) : null;
    }

    /**
     * Récupére la liste des objets Booking selon un champ spécifique avec pagination et tri.
     *
     * @param array $criteria
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @param [type] $extraValue
     * @return array
     */
    public function findAllBookingsByFields(
        array $criteria = [],
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {

        // Pas nécessaire de vérifier les champs car table pivot.
        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'booking_id'
        );

        // Chercher les éléments.
        $rows = parent::findAllByFields($criteria, $orderBy, $orderDirection, $limit, $offset);
        return array_map(fn($row) => $this->hydrateBooking((array) $row), $rows);
    }

    /**
     * Récupére la liste brute de réservation selon un champ spécifique avec pagination et tri.
     *
     * @param array $criteria
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function fetchAllBookingsRowsByFields(
        array $criteria = [],
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {

        // Pas nécessaire de vérifier les champs car table pivot.
        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'booking_id'
        );
        // Chercher les éléments.
        $rows = parent::findAllByFields($criteria, $orderBy, $orderDirection, $limit, $offset);
        return $rows;
    }

    //------ Récupération spécifique-----

    /**
     * Récupére un objet Booking par l'id du trajet.
     *
     * @param integer $rideId
     * @return Booking|null
     */
    public function findBookingByRideId(int $rideId): ?Booking
    {
        return $this->findBookingByFields(['ride_id' => $rideId]);
    }


    /**
     * Récupére la liste des objets Booking par le statut de réservation.
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
        return $this->findAllBookingsByFields(['booking_status' => $bookingStatus->value], $orderBy, $orderDirection, $limit, $offset);
    }


    /**
     * Récupére la liste des objets Booking par date de départ.
     *
     * @param DateTimeInterface $departureDate
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllBookingsByDepartureDate(
        DateTimeInterface $departureDate,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->findAllBookingsByFields(['departure_date_time' => $departureDate], $orderBy, $orderDirection, $limit, $offset);
    }


    /**
     * Récupére la liste des objets Booking par la date de création.
     *
     * @param DateTimeInterface $createdAt
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllBookingsByCreatedAt(
        DateTimeInterface $createdAt,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->findAllBookingsByFields(['created_at' => $createdAt], $orderBy, $orderDirection, $limit, $offset);
    }


    // ------ Pour les conducteurs-------

    /**
     * Récupére un objet Booking par l'id du conducteur.
     *
     * @param integer $driverId
     * @return Booking|null
     */
    public function findOneBookingByDriverId(int $driverId): ?Booking
    {
        return $this->findBookingByFields(['driver_id' => $driverId]);
    }

    /**
     * Récupére la liste des objets Booking par l'id conducteur.
     *
     * @param integer $driverId
     * @param BookingStatus|null $bookingStatus
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllBookingsByDriverId(
        int $driverId,
        ?BookingStatus $bookingStatus = null,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        $criteria = ['driver_id' => $driverId];
        if ($bookingStatus) {
            $criteria['booking_status'] = $bookingStatus->value;
        }
        return $this->findAllBookingsByFields($criteria, $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupére la liste des objets Booking à venir pour un conducteur.
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

        return $this->findAllBookingsByDriverId($driverId, BookingStatus::CONFIRMEE, $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupére la liste des objets Booking passé pour un conducteur.
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
        return $this->findAllBookingsByDriverId($driverId, BookingStatus::PASSEE, $orderBy, $orderDirection, $limit, $offset);
    }


    //------- Pour les passagers---------

    /**
     * Récupére un objet Booking par l'id d'un passager.
     *
     * @param integer $passengerId
     * @return Booking|null
     */
    public function findOneBookingByPassengerId(int $passengerId): ?Booking
    {
        return $this->findBookingByFields(['passenger_id' => $passengerId]);
    }

    /**
     * Récupére la liste des objets Booking par l'id passager.
     *
     * @param integer $passengerId
     * @param BookingStatus|null $bookingStatus
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllBookingsByPassengerId(
        int $passengerId,
        ?BookingStatus $bookingStatus = null,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        $criteria = ['passenger_id' => $passengerId];
        if ($bookingStatus) {
            $criteria['booking_status'] = $bookingStatus->value;
        }
        return $this->findAllBookingsByFields($criteria, $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupére la liste des objets Booking à venir pour un passager.
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
        return $this->findAllBookingsByPassengerId($passengerId, BookingStatus::CONFIRMEE, $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupére la liste des objets Booking passés pour un passager.
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
        return $this->findAllBookingsByPassengerId($passengerId, BookingStatus::PASSEE, $orderBy, $orderDirection, $limit, $offset);
    }


    //------------------------------------------


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
