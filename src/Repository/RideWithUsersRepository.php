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
    private BookingRepository $bookingRepository;

    public function __construct(PDO $db, UserRepository $userRepository, BookingRepository $bookingRepository)
    {
        parent::__construct($db);
        $this->userRepository = $userRepository;
        $this->bookingRepository = $bookingRepository;
    }

    /**
     * Hydrate un Ride avec son conducteur et ses passagers en objet User
     *
     * @param Ride $ride
     * @return Ride
     */
    public function hydrateRideRelation(Ride $ride): Ride
    {
        // Vérification si l'objet Ride est déjà hydraté
        if ($ride->getRideDriver() && $ride->getRidePassengers()) {
            return $ride;
        }

        // Hydrater le conducteur
        $driver = $this->userRepository->findUserById($ride->getRideDriverId());
        $ride->setRideDriver($driver);

        // Hydrater les passagers
        $bookings = $this->bookingRepository->findBookingByRideId($ride->getRideId());
        foreach ($bookings as $booking) {
            $passenger = $this->userRepository->findUserById($booking->getPassengerId());
            if ($passenger) {
                $ride->addRidePassenger($passenger);
            }
        }
        return $ride;
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
     * Base SQL pour récupérer un Ride avec ses utilisateurs.
     *
     * @return string
     */
    private function baseRideUserSelect(): string
    {
        return "SELECT r.*,
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
                LEFT JOIN users p ON b.passenger_id = p.user_id
        ";
    }


    //  ------ Récupérations spécifiques ---------

    /**
     * Récupére la liste des objets Ride avec les conducteurs et les passagers en liste avec ou sans critéres.
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
        $sql = $this->baseRideUserSelect();

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

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC); // FetchAll car plusieurs lignes d'utilisateur (conducteur et passager)
        if (!$rows) return [];

        // Hydratation du trajet
        $rideMap = [];
        foreach ($rows as $row) {
            $rideId = $row['ride_id'];

            // Création du ride seulement 1 fois car plusieurs ligne à cause des utilisateurs
            if (!isset($rideMap[$rideId])) {
                $rideMap[$rideId] = $this->hydrateRide($row);

                // Puis ajout du conduteur en tableau
                $rideMap[$rideId]->setRideDriver([
                    'user_id' => $row['driver_id'],
                    'user_name' => $row['driver_user_name'],
                    'first_name' => $row['driver_first_name'],
                    'last_name' => $row['driver_last_name'],
                ]);
            }

            // Ajouter les passagers si présents
            if (!empty($row['passenger_id'])) {
                $rideMap[$rideId]->addRidePassenger([
                    'user_id' => $row['passenger_id'],
                    'user_name' => $row['passenger_user_name'],
                    'first_name' => $row['passenger_first_name'],
                    'last_name' => $row['passenger_last_name'],
                ]);
            }
        }
        return array_values($rideMap);
    }

    /**
     * Récupére un objet Ride avec le liste l'objet User conducteur et les objets User passagers.
     *
     * @param integer $rideId
     * @return Ride|null
     */
    public function findRideWithUsersByRideId(int $rideId): ?Ride
    {
        // Construction du sql
        $sql = $this->baseRideUserSelect();
        $sql .= " WHERE r.ride_id = :ride_id
                ORDER BY p.user_name";

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
            if (!empty($row['passenger_id'])) {
                $passenger = $this->mapUser($row, 'passenger_');
                $ride->addRidePassenger($passenger);
            }
        }

        return $ride;
    }
}
