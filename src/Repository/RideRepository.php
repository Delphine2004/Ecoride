<?php

namespace App\Repositories;

use App\Repositories\BaseRepository;
use App\Models\Ride;
use App\Enum\RideStatus;
use App\Enum\UserRoles;
use PDO;
use InvalidArgumentException;

/**
 * Cette classe gére la correspondance entre un trajet et la BDD.
 */

class RideRepository extends BaseRepository
{

    protected string $table = 'rides';
    protected string $primaryKey = 'ride_id';
    private array $allowedFields = ['ride_id', 'owner_id', 'departure_date_time', 'departure_place', 'arrival_date_time', 'arrival_place', 'price', 'available_seats', 'ride_status'];


    public function __construct(PDO $db)
    {
        parent::__construct($db);
    }


    /**
     * Remplit un objet Ride avec les données de la table rides.
     *
     * @param array $data
     * @return Ride
     */
    public function hydrateRide(array $data): Ride
    {
        return new Ride(
            rideId: (int)$data['ride_id'],
            driver: null, // car pas encore chargé
            departureDateTime: new \DateTimeImmutable($data['departure_date_time']),
            departurePlace: $data['departure_place'],
            arrivalDateTime: new \DateTimeImmutable($data['arrival_date_time']),
            arrivalPlace: $data['arrival_place'],
            price: (float)$data['price'],
            availableSeats: (int)$data['available_seats'],
            rideStatus: RideStatus::from($data['ride_status']),
            passengers: null, // car pas encore chargé
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
            'owner_id' => $ride->getRideDriver()->getUserId(),
            'departure_date_time' => $ride->getRideDepartureDateTime()->format('Y-m-d H:i:s'),
            'departure_place' => $ride->getRideDeparturePlace(),
            'arrival_date_time' => $ride->getRideArrivalDateTime()->format('Y-m-d H:i:s'),
            'arrival_place' => $ride->getRideArrivalPlace(),
            'price' => $ride->getRidePrice(),
            'available_seats' => $ride->getRideAvailableSeats(),
            'ride_status' => $ride->getRideStatus()->value
        ];
    }

    /**
     * Surcharge la fonction isAllowedField de BaseRepository
     *
     * @param string $field
     * @return boolean
     */
    protected function isAllowedField(string $field): bool
    {
        return in_array($field, $this->allowedFields, true);
    }


    // ------ Récupération ------ 

    /**
     * Récupére un trajet par son id.
     *
     * @param integer $rideId
     * @return Ride|null
     */
    public function findRideById(int $rideId): ?Ride
    {
        // Chercher l'élément
        $row = parent::findById($rideId);
        return $row ? $this->hydrateRide((array) $row) : null;
    }

    /**
     * Récupére tous les trajets avec pagination et tri.
     *
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllRides(
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'ride_id'
        );

        // Chercher les éléments.
        $rows = parent::findAll($orderBy, $orderDirection, $limit, $offset);
        return array_map(fn($row) => $this->hydrateRide((array) $row), $rows);
    }

    /**
     * Récupére un trajet selon un champs spécifique.
     *
     * @param string $field
     * @param mixed $value
     * @return Ride|null
     */
    public function findRideByField(
        string $field,
        mixed $value
    ): ?Ride {
        // Vérifie si le champ est autorisé.
        if (!$this->isAllowedField($field)) {
            return null;
        }

        // Chercher l'élément
        $row = parent::findOneByField($field, $value);
        return $row ? $this->hydrateRide((array) $row) : null;
    }

    /**
     * Récupére tous les trajets selon un champ spécifique avec pagination et tri.
     *
     * @param string $field
     * @param mixed $value
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllRidesByField(
        string $field,
        mixed $value,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        // Vérifie si le champ est autorisé.
        if (!$this->isAllowedField($field)) {
            return [];
        }

        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'ride_id'
        );

        // Chercher les éléments.
        $rows = parent::findAllByField($field, $value, $orderBy, $orderDirection, $limit, $offset);
        return array_map(fn($row) => $this->hydrateRide((array) $row), $rows);
    }


    //  ------ Récupérations spécifiques ---------

    /**
     * Récupére tous les trajets selon la date, le lieu de depart et le lieu d'arrivée.
     *
     * @param \DateTimeInterface $date
     * @param string $departurePlace
     * @param string|null $arrivalPlace
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findRideByDateAndPlace(
        \DateTimeInterface $date,
        string $departurePlace,
        ?string $arrivalPlace = null,
        string $orderBy = 'departure_date_time',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'departure_date_time'
        );

        // Construction du SQL
        $sql = "SELECT * 
                FROM {$this->table} r
                WHERE DATE(r.departure_date_time) = :date
                AND r.departure_place = :departurePlace";

        // Vérification de l'existance de la ville d'arrivée et ajout si existant.
        if ($arrivalPlace !== null) {
            $sql .= " AND r.arrival_place = :arrivalPlace";
        }

        // Tri et limite
        $sql .= " ORDER BY r.$orderBy $orderDirection 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;


        // Préparation de la requête
        $stmt = $this->db->prepare($sql);

        if ($arrivalPlace !== null) {
            $stmt->bindValue(':arrivalPlace', $arrivalPlace, PDO::PARAM_STR);
        }
        $stmt->bindValue(':date', $date->format('Y-m-d'), PDO::PARAM_STR);
        $stmt->bindValue(':departurePlace', $departurePlace, PDO::PARAM_STR);
        if ($arrivalPlace !== null) {
            $stmt->bindValue(':arrivalPlace', $arrivalPlace, PDO::PARAM_STR);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->hydrateRide((array) $row), $rows);
    }

    /**
     * Récupérer les trajets selon un utilisateur.
     *
     * @param integer $userId
     * @param UserRoles $role
     * @param string|null $rideStatus
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findRideByUser(
        int $userId,
        UserRoles $role,
        ?string $rideStatus = null,
        string $orderBy = 'departure_date_time',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'departure_date_time'
        );

        // Construction du SQL selon le rôle
        switch ($role) {
            case UserRoles::CONDUCTEUR:
                $sql = "SELECT r.*
                FROM {$this->table} r
                INNER JOIN users u ON r.owner_id = u.user_id
                WHERE u.user_id = :userId";
                break;
            case UserRoles::PASSAGER:
                $sql = "SELECT r.*
                FROM {$this->table} r
                INNER JOIN ride_passengers rp ON r.ride_id = rp.ride_id
                JOIN users u ON r.owner_id = u.user_id
                WHERE rp.user_id = :userId";
                break;
            default:
                throw new InvalidArgumentException("Rôle invalide : {$role->value}");
        }


        // Vérification de l'existance du status du trajet et ajout si existant.
        if ($rideStatus !== null) {
            $sql .= " AND r.ride_status = :rideStatus";
        }

        // Tri et limite
        $sql .= " ORDER BY r.$orderBy $orderDirection 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;


        // Préparation de la requête
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        if ($rideStatus !== null) {
            $stmt->bindValue(':rideStatus', $rideStatus, PDO::PARAM_STR);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->hydrateRide((array) $row), $rows);
    }


    //------------------------------------------


    // ------ Mise à jour ------ 
    /**
     * Met à jour les données concernant un trajet.
     *
     * @param Ride $ride
     * @return boolean
     */
    public function updateRide(Ride $ride): bool
    {
        return $this->updateById($ride->getRideId(), $this->mapRideToArray($ride));
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
    public function deleteRide(int $rideId): bool
    {
        return $this->deleteById($rideId);
    }
}
