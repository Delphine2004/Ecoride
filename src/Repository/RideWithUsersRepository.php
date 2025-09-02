<?php

namespace App\Repositories;

use App\Repositories\RideRepository;
use App\Repositories\UserRepository;
use App\Models\Ride;
use App\Models\User;
use PDO;

/**
 * Cette classe gére la correspondance entre un trajet et les utilisateurs et la BDD.
 */

class RideWithUsersRepository extends RideRepository
{

    protected string $table = 'rides';
    protected string $primaryKey = 'ride_id';

    private UserRepository $userRepository;

    public function __construct(PDO $db, UserRepository $userRepository)
    {
        parent::__construct($db);
        $this->userRepository = $userRepository;
    }


    /**
     * Transforme les lignes SQL en Objet User
     *
     * @param array $row
     * @param string $prefix
     * @return User
     */
    private function mapUser(array $row, string $prefix = ''): User
    {
        //$prefix -> permet de distinguer les alias dans les jointures sql
        return $this->userRepository->hydrateUser([
            'user_id' => $row[$prefix . 'id'],
            'first_name' => $row[$prefix . 'first_name'],
            'last_name' => $row[$prefix . 'last_name'],
            'user_name' => $row[$prefix . 'user_name']
        ]);
    }

    /**
     * Récupére un trajet avec le conducteur et les passagers tous hydratés en objets.
     *
     * @param integer $rideId
     * @return Ride|null
     */
    public function findRideWithUsersByRideId(int $rideId): ?Ride
    {
        // Construction du sql
        $sql = "SELECT r.*
                d.user_id AS driver_id, 
                d.first_name AS driver_first_name,
                d.last_name AS driver_last_name,
                d.user_name AS driver_user_name,
                p.user_id AS passenger_id, 
                p.first_name AS passenger_first_name,
                p.last_name AS passenger_last_name,
                p.user_name AS passenger_user_name
                FROM {$this->table} r
                INNER JOIN users d ON r.owner_id = d.user_id
                LEFT JOIN bookings b ON r.ride_id = b.ride_id
                LEFT JOIN users p ON b.passenger_id = p.user_id
                WHERE r.ride_id = :ride_id
                ORDER BY p.user_name
        ";

        // Préparation de la requête
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('ride_id', $rideId, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC); // FetchAll car plusieurs lignes d'utilisateur (conducteur et passager)
        if (!$rows) return null;

        // Hydratation du trajet avec la premiére ligne.
        $ride = $this->hydrateRide($rows[0]);

        // Hydratation du conducteur.
        $driver = $this->mapUser($rows[0], 'driver_');
        $ride->setRideDriver($driver);

        // Ajouter les passagers en les hydratant.
        foreach ($rows as $row) {
            if ($row['passenger_id']) {
                $passenger = $this->mapUser($row, 'passenger_');
                $ride->addRidePassenger($passenger);
            }
        }

        return $ride;
    }

    /**
     * Récupérer tous les trajets avec le conducteur et les passagers tous hydratés en objets.
     *
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllRidesWithUsers(
        string $orderBy = 'ride_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {

        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'ride_id'
        );

        //Construction du sql
        $sql = "SELECT r.*,
            d.user_id AS driver_id, 
            d.first_name AS driver_first_name,
            d.last_name AS driver_last_name,
            d.user_name AS driver_user_name,

            p.user_id AS passenger_id,
            p.first_name AS passenger_first_name,
            p.last_name AS passenger_last_name,
            p.user_name AS passenger_user_name
        FROM {$this->table} r
        INNER JOIN users d ON r.driver_id = d.user_id
        LEFT JOIN bookings b ON r.ride_id = b.ride_id
        LEFT JOIN users p ON b.passenger_id = p.user_id";

        // Tri et limite
        $sql .= " ORDER BY r.$orderBy $orderDirection 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        //Preparation de la requête
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) return [];

        $rides = [];
        foreach ($rows as $row) {
            $rideId = $row['ride_id'];
            if (!isset($rides[$rideId])) {
                $rides[$rideId] = $this->hydrateRide($row);
                $driver = $this->mapUser($row, 'driver_');
                $rides[$rideId]->setRideDriver($driver);
            }

            if ($row['passenger_id']) {
                $passenger = $this->mapUser($row, 'passenger_');
                $rides[$rideId]->addRidePassenger($passenger);
            }
        }

        return array_values($rides);
    }

    /**
     * Récupérer tous les trajets (objets) auxquels un utilisateur à participé (conducteur ou passager).
     *
     * @param integer $userId
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllRidesByUser(
        int $userId,
        string $orderBy = 'ride_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {

        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'ride_id'
        );

        //Construction du sql
        $sql = "SELECT r.*
        FROM {$this->table} r
        LEFT JOIN bookings b ON r.ride_id = b.ride_id
        WHERE r.driver_id = :user_id OR b.passenger_id = :user_id
        ";

        // Tri et limite
        $sql .= " ORDER BY r.$orderBy $orderDirection 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        //Preparation de la requête
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) return [];

        return array_map(fn($row) => $this->hydrateRide((array) $row), $rows);
    }

    /**
     * Récupérer tous les trajets (objets) avec le conducteur (objet) et les passagers (objets) par différents critéres.
     *
     * @param array $criteria
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllRidesWithUsersByFields(
        array $criteria = [],
        string $orderBy = 'ride_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'ride_id'
        );

        // Construction du SQL 
        $sql = "SELECT r.*,
                    d.user_id AS driver_id, 
                    d.first_name AS driver_first_name,
                    d.last_name AS driver_last_name,
                    d.user_name AS driver_user_name,

                    p.user_id AS passenger_id,
                    p.first_name AS passenger_first_name,
                    p.last_name AS passenger_last_name,
                    p.user_name AS passenger_user_name
                FROM {$this->table}
                INNER JOIN users d ON r.driver_id = d.user_id
                LEFT JOIN bookings b ON r.ride_id = b.ride_id
                LEFT JOIN users p ON b.passenger_id = p.user_id
                WHERE 1=1
        ";

        // Ajout des critéres
        foreach ($criteria as $field => $value) {
            if (!$this->isAllowedField($field)) {
                continue;
            }
            if ($value === null) {
                $sql .= " AND r.$field IS NULL";
            } else {
                $sql .= " AND r.$field = :$field";
            }
        }

        // Tri et limite
        $sql .= " ORDER BY r.$orderBy $orderDirection
        LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        // Préparation de la requête
        $stmt = $this->db->prepare($sql);

        foreach ($criteria as $field => $value) {
            if ($value === null || !$this->isAllowedField($field)) {
                continue;
            }

            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(":$field", $value, $type);
        }

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) return [];

        // Hydratations des trajets
        $rides = [];
        foreach ($rows as $row) {
            // en tant que conducteur
            $rideId = $row['ride_id'];
            if (!isset($rides[$rideId])) {
                $rides[$rideId] = $this->hydrateRide($row);
                $rides[$rideId]->setRideDriver($this->mapUser($row, 'driver_'));
            }
            // en tant que passager
            if ($row['passenger_id']) {
                $rides[$rideId]->addRidePassenger($this->mapUser($row, 'passenger_'));
            }
        }

        return array_values($rides);
    }
}
