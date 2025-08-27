<?php

namespace App\Repositories;

use App\Repository\BaseModel;
use App\Models\Ride;
use App\Enum\RideStatus;
use PDO;
use InvalidArgumentException;

/**
 * Cette classe gére la correspondance entre un trajet et la BDD.
 */

class RideRepository extends BaseModel
{

    /**
     * @var string Le nom de la table en BDD
     */

    protected string $table = 'rides';

    protected string $primaryKey = 'ride_id';

    private UserRepository $userRepository;

    private array $allowedFields = ['ride_id', 'departure_date_time', 'departure_place', 'arrival_date_time', 'arrival_place', 'price', 'available_seats', 'status', 'owner_id'];


    public function __construct(PDO $db, UserRepository $userRepository)
    {
        parent::__construct($db);
        $this->userRepository = $userRepository;
    }


    /**
     * Fonction qui remplit un objet Ride avec les données de la table Ride lors de l'instanciation.
     *
     * @param array $data
     * @return Ride
     */
    private function hydrateRide(array $data): Ride
    {
        // Rechercher le conducteur du trajet.
        $driver = $this->userRepository->findUserById($data['owner_id']);

        if (!$driver) {
            throw new InvalidArgumentException("Le conducteur du trajet {$data['ride_id']} est introuvable.");
        }
        return new Ride(
            id: (int)$data['ride_id'],
            driver: $this->userRepository->findUserById($data['owner_id']),
            departureDateTime: new \DateTimeImmutable($data['departure_date_time']),
            departurePlace: $data['departure_place'],
            arrivalDateTime: new \DateTimeImmutable($data['arrival_date_time']),
            arrivalPlace: $data['arrival_place'],
            price: (float)$data['price'],
            availableSeats: (int)$data['available_seats'],
            status: RideStatus::from($data['status']),
            createdAt: !empty($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : null,
            updatedAt: !empty($data['updated_at']) ? new \DateTimeImmutable($data['updated_at']) : null

        );
    }

    /**
     * Transforme Ride en tableau pour insert et update.
     *
     * @param Ride $ride
     * @return array
     */
    private function mapRideToArray(Ride $ride): array
    {
        return [
            'departure_date_time' => $ride->getDepartureDateTime()->format('Y-m-d H:i:s'),
            'departure_place' => $ride->getDeparturePlace(),
            'arrival_date_time' => $ride->getArrivalDateTime()->format('Y-m-d H:i:s'),
            'arrival_place' => $ride->getArrivalPlace(),
            'price' => $ride->getPrice(),
            'available_seats' => $ride->getAvailableSeats(),
            'status' => $ride->getStatus()->value

        ];
    }


    // ------ Récupération ------ 

    /**
     * Récupére un trajet par son id.
     *
     * @param integer $id
     * @return Ride|null
     */
    public function findRideById(int $id): ?Ride
    {
        $row = parent::findById($id);
        return $row ? $this->hydrateRide((array) $row) : null;
    }

    /**
     * Récupére tous les trajets avec pagination et tri.
     *
     * @return array
     */
    public function findAllRides(int $limit = 50, int $offset = 0, ?string $orderBy = null, string $orderDirection = 'DESC'): array
    {
        $rows = parent::findAll($limit, $offset, $orderBy, $orderDirection);
        return array_map(fn($row) => $this->hydrateRide((array) $row), $rows);
    }

    /**
     * Récupére un trajet selon un champs spécifique.
     *
     * @param string $field
     * @param mixed $value
     * @return Ride|null
     */
    public function findRideByField(string $field, mixed $value): ?Ride
    {
        if (!in_array($field, $this->allowedFields)) {
            throw new InvalidArgumentException("Champ non autorisé ; $field");
        }

        $row = parent::findOneByField($field, $value);
        return $row ? $this->hydrateRide((array) $row) : null;
    }

    /**
     * Récupére tous les trajets selon un champ spécifique avec pagination et tri.
     *
     * @param string $field
     * @param mixed $value
     * @return array
     */
    public function findAllRidesByField(string $field, mixed $value, int $limit = 50, int $offset = 0, ?string $orderBy = null, string $orderDirection = 'DESC'): array
    {
        if (!in_array($field, $this->allowedFields)) {
            throw new InvalidArgumentException("Champ non autorisé ; $field");
        }

        $rows = parent::findAllByField($field, $value, $limit, $offset, $orderBy, $orderDirection);
        return array_map(fn($row) => $this->hydrateRide((array) $row), $rows);
    }




    //  ------ Récupérations scpécifiques ---------

    /**
     * Récupére tous les trajets selon la date, le lieu de depart et le lieu d'arrivée.
     *
     * @param \DateTimeInterface $date
     * @param string $departurePlace
     * @param string|null $arrivalPlace
     * @return array
     */
    public function findRideByDateAndPlace(\DateTimeInterface $date, string $departurePlace, ?string $arrivalPlace = null): array
    {
        $sql = "SELECT * 
        FROM {$this->table} r
        WHERE DATE(r.departure_date_time) = :date
        AND r.departure_place = :departurePlace";

        $params = [
            'date' => $date->format('Y-m-d'),
            'departurePlace' => $departurePlace
        ];

        if ($arrivalPlace !== null) {
            $sql .= " AND r.arrival_place = :arrivalPlace";
            $params['arrivalPlace'] = $arrivalPlace;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->hydrateRide((array) $row), $rows);
    }

    // Pour les conducteurs
    /**
     * Trouver tous les trajets d'un conducteur
     *
     * @param integer $driverId
     * @param string|null $status
     * @return array
     */
    public function findRidesByDriver(int $driverId, ?string $status = null): array
    {
        $sql = "SELECT r.*
                FROM {$this->table} r
                INNER JOIN users u ON r.owner_id = u.user_id
                WHERE u.user_id = :driverId";

        $params = ['driverId' => $driverId];

        if ($status !== null) {
            $sql .= " AND r.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY r.departure_date_time DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->hydrateRide((array) $row), $rows);
    }

    /**
     * Trouver tous les trajets à venir d'un conducteur
     *
     * @param integer $driverId
     * @return array
     */
    public function findUpcomingRidesByDriver(int $driverId): array
    {
        return $this->findRidesByDriver($driverId, RideStatus::DISPONIBLE->value);
    }

    /**
     * Trouver tous les trajets passés d'un conducteur
     *
     * @param integer $driverId
     * @return array
     */
    public function findPastRidesByDriver(int $driverId): array
    {
        return $this->findRidesByDriver($driverId, RideStatus::PASSE->value);
    }

    //Pour les passagers
    /**
     * Trouver tous les trajets d'un passager
     *
     * @param integer $userId
     * @param string|null $status
     * @return array
     */
    public function findRidesByPassenger(int $userId, ?string $status = null): array
    {
        $sql = "SELECT r.*
                FROM {$this->table} r
                INNER JOIN ride_passengers rp ON r.ride_id = rp.ride_id
                WHERE rp.user_id = :id";
        $params = ['id' => $userId];

        if ($status !== null) {
            $sql .= " AND r.status = :status";
            $params['status'] = $status;
        }

        $sql .= "ORDER BY r.departure_date_time DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->hydrateRide((array) $row), $rows);
    }

    /**
     * Trouver tous les trajets à venir d'un passager
     *
     * @param integer $userId
     * @return array
     */
    public function findUpcomingRidesByPassenger(int $userId): array
    {
        return $this->findRidesByPassenger($userId, RideStatus::DISPONIBLE->value);
    }

    /**
     * Trouver tous les trajets passés d'un passager
     *
     * @param integer $userId
     * @return array
     */
    public function findPastRidesByPassenger(int $userId): array
    {
        return $this->findRidesByPassenger($userId, RideStatus::PASSE->value);
    }


    // ------ Mise à jour ------ 
    /**
     * Met à jour les données concernant un trajet.
     *
     * @param Ride $ride
     * @return boolean
     */
    public function updateRide(Ride $ride): bool
    {
        return $this->updateById($ride->getId(), $this->mapRideToArray($ride));
    }

    // ------ Insertion ------ 
    /**
     * Insert un trajet dans la BD.
     *
     * @param Ride $ride
     * @return integer
     */
    public function insertRide(Ride $ride): int
    {
        return $this->insert($this->mapRideToArray($ride));
    }

    // ------ Suppression ------ 
    /**
     * Supprime un trajet de la BD.
     *
     * @param integer $id
     * @return boolean
     */
    public function deleteRide(int $id): bool
    {
        return $this->deleteById($id);
    }
}
