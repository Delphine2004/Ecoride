<?php

namespace App\Repositories;

use App\Models\Ride;
use App\Models\User;
use App\Repositories\RideRepository;
use App\Repositories\UserRepository;
use App\Enum\RideStatus;
use App\Enum\UserRoles;
use InvalidArgumentException;
use PDO;

/**
 * Cette classe gére la correspondance entre un trajet et les utilisateurs.
 */

class RideWithUsersRepository extends RideRepository
{

    protected string $table = 'rides';
    protected string $primaryKey = 'rides_id';

    private UserRepository $userRepository;

    public function __construct(PDO $db, UserRepository $userRepository)
    {
        parent::__construct($db);
        $this->userRepository = $userRepository;
    }


    // Fonction générique de mapping
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


    // Trouver un trajet avec son conducteur et ses passagers.
    public function findOneRideWithUsers(int $rideId): ?Ride
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
                ORDER BY passenger_user_name
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
                $ride->addPassenger($passenger);
            }
        }

        return $ride;
    }

    // Afficher tous les trajets avec conducteur et passagers.
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
        INNER JOIN users d ON r.owner_id = d.user_id
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
                $rides[$rideId]->addPassenger($passenger);
            }
        }

        return array_values($rides);
    }


    // Afficher tous les trajets avec leur conducteur et leurs passagers.
    public function findAllRidesWithUsersByUser(
        int $userId,
        UserRoles $role,
        ?RideStatus $rideStatus = null,
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

        // Construction du SQL selon le rôle
        switch ($role) {
            case UserRoles::CONDUCTEUR:
                $sql = "SELECT r.*, 
                        d.user_id AS driver_id,
                        d.first_name AS driver_first_name,
                        d.last_name AS driver_last_name,
                        d.user_name AS driver_user_name
                        FROM {$this->table} r
                        INNER JOIN users d ON r.owner_id = d.user_id
                        WHERE d.user_id = :userId";
                break;

            case UserRoles::PASSAGER:
                $sql = "SELECT r.*,
                        p.user_id AS passenger_id,
                        p.first_name AS passenger_first_name,
                        p.last_name AS passenger_last_name,
                        p.user_name AS passenger_user_name
                        FROM {$this->table} r
                        INNER JOIN bookings b ON r.ride_id = b.ride_id
                        INNER JOIN users p ON b.passenger_id = p.user_id
                        WHERE p.user_id = :userId";
                break;

            default:
                throw new \InvalidArgumentException("Rôle invalide : {$role->value}");
        }

        // Ajout du statut du trajet
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
            $stmt->bindValue(':rideStatus', $rideStatus->value, PDO::PARAM_STR);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) return [];

        // Hydratations
        $rides = [];
        foreach ($rows as $row) {
            $rideId = $row['ride_id'];
            if (!isset($rides[$rideId])) {
                $rides[$rideId] = $this->hydrateRide($row);
                if ($role === UserRoles::CONDUCTEUR) {
                    $driver = $this->mapUser($row, 'driver_');
                    $rides[$rideId]->setRideDriver($driver);
                }
            }

            if ($role === UserRoles::PASSAGER && isset($row['passenger_id'])) {
                $passenger = $this->mapUser($row, 'passenger_');
                $rides[$rideId]->addPassenger($passenger);
            }
        }

        return array_values($rides);
    }
}
