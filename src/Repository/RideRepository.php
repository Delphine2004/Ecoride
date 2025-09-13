<?php

namespace App\Repositories;

use App\Models\Ride;
use App\Enum\RideStatus;
use App\Enum\UserRoles;
use DateTimeInterface;
use PDO;
use InvalidArgumentException;
use DateTime;
use DateTimeImmutable;

/**
 * Cette classe gére la correspondance entre un trajet et la BDD.
 */

class RideRepository extends BaseRepository
{

    protected string $table = 'rides';
    protected string $primaryKey = 'ride_id';
    private array $allowedFields = [
        'ride_id',
        'driver_id',
        'departure_date_time',
        'departure_place',
        'arrival_date_time',
        'arrival_place',
        'price',
        'available_seats',
        'ride_status',
        'commission'
    ];



    /**
     * Remplit un objet Ride avec les données de la table rides.
     *
     * @param array $data
     * @return Ride
     */
    public function hydrateRide(
        array $data
    ): Ride {
        return new Ride(
            rideId: (int)$data['ride_id'],
            driver: null, // car pas encore chargé
            departureDateTime: new DateTimeImmutable($data['departure_date_time']),
            departurePlace: $data['departure_place'],
            arrivalDateTime: new DateTimeImmutable($data['arrival_date_time']),
            arrivalPlace: $data['arrival_place'],
            price: (float)$data['price'],
            availableSeats: (int)$data['available_seats'],
            rideStatus: RideStatus::from($data['ride_status']),
            commission: (int)$data['commission'],
            createdAt: !empty($data['created_at']) ? new DateTimeImmutable($data['created_at']) : null,
            updatedAt: !empty($data['updated_at']) ? new DateTimeImmutable($data['updated_at']) : null
        );
    }

    /**
     * Transforme Ride en tableau pour insert et update.
     *
     * @param Ride $ride
     * @return array
     */
    private function mapRideToArray(
        Ride $ride
    ): array {
        return [
            'driver_id' => $ride->getRideDriver()->getUserId(),
            'departure_date_time' => $ride->getRideDepartureDateTime()->format('Y-m-d H:i:s'),
            'departure_place' => $ride->getRideDeparturePlace(),
            'arrival_date_time' => $ride->getRideArrivalDateTime()->format('Y-m-d H:i:s'),
            'arrival_place' => $ride->getRideArrivalPlace(),
            'price' => $ride->getRidePrice(),
            'available_seats' => $ride->getRideAvailableSeats(),
            'ride_status' => $ride->getRideStatus()->value,
            'commission' => $ride->getRideCommission()
        ];
    }

    /**
     * Surcharge la fonction isAllowedField de BaseRepository
     *
     * @param string $field
     * @return boolean
     */
    protected function isAllowedField(
        string $field
    ): bool {
        return in_array($field, $this->allowedFields, true);
    }

    private function baseQueryRidesByUserRole(
        int $userId,
        UserRoles $role,
        ?RideStatus $rideStatus,
        string $orderBy,
        string $orderDirection,
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
                        INNER JOIN users u ON r.driver_id = u.user_id
                        WHERE u.user_id = :userId
                    ";
                break;
            case UserRoles::PASSAGER:
                $sql = "SELECT r.*
                        FROM {$this->table} r
                        INNER JOIN ride_passengers rp ON r.ride_id = rp.ride_id
                        INNER JOIN users u ON r.driver_id = u.user_id
                        WHERE rp.user_id = :userId
                    ";
                break;
            default:
                throw new InvalidArgumentException("Rôle invalide : {$role->value}");
        }


        // Vérification de l'existence du status du trajet et ajout si existant.
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

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ------ Récupérations ------ 

    /**
     * Récupére un objet Ride par son id.
     *
     * @param integer $rideId
     * @return Ride|null
     */
    public function findRideById(
        int $rideId
    ): ?Ride {
        // Chercher l'élément
        $row = parent::findById($rideId);
        return $row ? $this->hydrateRide((array) $row) : null;
    }

    /**
     * Récupére la liste des objets Ride selon un ou plusieurs champs spécifiques avec tri et pargination.
     *
     * @param array $criteria
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllRidesByFields(
        array $criteria = [],
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        // Vérifie si chaque champ est autorisé.
        foreach ($criteria as $field => $value) {
            if (!$this->isAllowedField($field)) {
                return [];
            }
        }

        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'ride_id'
        );

        // Chercher les éléments.
        $rows = parent::findAllByFields($criteria, $orderBy, $orderDirection, $limit, $offset);
        return array_map(fn($row) => $this->hydrateRide((array) $row), $rows);
    }

    /**
     * Récupére la liste brute des trajets selon un champ spécifique avec tri et pargination.
     *
     * @param array $criteria
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function fetchAllRidesRowsByFields(
        array $criteria = [],
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {

        // Vérifie si chaque champ est autorisé.
        foreach ($criteria as $field => $value) {
            if (!$this->isAllowedField($field)) {
                return [];
            }
        }

        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'ride_id'
        );
        // Chercher les éléments.
        $rows = parent::findAllByFields($criteria, $orderBy, $orderDirection, $limit, $offset);
        return $rows;
    }


    //  ------ Récupérations spécifiques ---------

    /**
     * Récupére la liste des objets Ride selon la date, le lieu de depart et le lieu d'arrivée avec tri et pargination.
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
    public function findAllRidesByDateAndPlace(
        \DateTimeInterface $date,
        string $departurePlace,
        ?string $arrivalPlace = null,
        string $orderBy = 'departure_date_time',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->findAllRidesByFields([
            'departure_date_time' => $date,
            'departure_place' => $departurePlace,
            'arrival_place' => $arrivalPlace,
        ], $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupére la liste brute des trajets selon la date de création avec tri et pargination.
     *
     * @param integer $creationDate
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function fetchAllRidesRowsByCreationDate(
        DateTimeImmutable $creationDate,
        string $orderBy = 'created_at',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->fetchAllRidesRowsByFields(['created_at' => $creationDate], $orderBy, $orderDirection, $limit, $offset);
    }


    //------ Récupérations en fonction du rôle ------
    /**
     * Récupére la liste des objets Ride selon le statut de l'utilisateur avec tri et pargination.
     *
     * @param int $userId
     * @param UserRoles $role
     * @param RideStatus|null $rideStatus
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllRidesByUserRole(
        int $userId,
        UserRoles $role,
        ?RideStatus $rideStatus = null,
        string $orderBy = 'departure_date_time',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        $rows = $this->baseQueryRidesByUserRole($userId, $role, $rideStatus, $orderBy, $orderDirection, $limit, $offset);
        return array_map(fn($row) => $this->hydrateRide((array) $row), $rows);
    }

    /**
     * Récupére la liste brute des trajets selon le rôle de l'utilisateur avec tri et pargination.
     *
     * @param int $userId
     * @param UserRoles $role
     * @param RideStatus|null $rideStatus
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function fetchAllRidesByUserRole(
        int $userId,
        UserRoles $role,
        ?RideStatus $rideStatus = null,
        string $orderBy = 'departure_date_time',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->baseQueryRidesByUserRole($userId, $role, $rideStatus, $orderBy, $orderDirection, $limit, $offset);
    }


    // Pour les conducteurs
    /**
     * Récupére la liste des objets Ride d'un conducteur avec tri et pargination.
     *
     * @param int $driverId
     * @param RideStatus|null $rideStatus
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllRidesByDriver(
        int $driverId,
        ?RideStatus $rideStatus = null,
        string $orderBy = 'departure_date_time',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->findAllRidesByUserRole($driverId, UserRoles::CONDUCTEUR, $rideStatus, $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupére la liste brute des trajets d'un conducteur avec tri et pagination.
     *
     * @param int $driverId
     * @param RideStatus|null $rideStatus
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function fetchAllRidesByDriver(
        int $driverId,
        ?RideStatus $rideStatus = null,
        string $orderBy = 'departure_date_time',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->fetchAllRidesByUserRole($driverId, UserRoles::CONDUCTEUR, $rideStatus, $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupére la liste des objets Ride à venir d'un conducteur avec tri et pargination.
     *
     * @param integer $driverId
     * @return array
     */
    public function findUpcomingRidesByDriver(
        int $driverId
    ): array {
        return $this->findAllRidesByDriver($driverId, RideStatus::DISPONIBLE);
    }

    /**
     * Récupére la liste brute des trajets passés d'un conducteur avec tri et pagination.
     *
     * @param integer $driverId
     * @return array
     */
    public function fetchPastRidesByDriver(
        int $driverId
    ): array {
        return $this->fetchAllRidesByDriver($driverId, RideStatus::TERMINE);
    }


    //Pour les passagers
    /**
     * Récupére la liste des objets Ride d'un passager avec tri et pargination.
     *
     * @param integer $passengerId
     * @param string|null $rideStatus
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllRidesByPassenger(
        int $passengerId,
        ?RideStatus $rideStatus = null,
        string $orderBy = 'departure_date_time',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->findAllRidesByUserRole($passengerId, UserRoles::PASSAGER, $rideStatus, $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupére la liste brute des trajets d'un passager avec tri et pagination.
     *
     * @param int $passengerId
     * @param RideStatus|null $rideStatus
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function fetchAllRidesByPassenger(
        int $passengerId,
        ?RideStatus $rideStatus = null,
        string $orderBy = 'departure_date_time',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->fetchAllRidesByUserRole($passengerId, UserRoles::PASSAGER, $rideStatus, $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupére la liste des objets Ride à venir d'un passager
     *
     * @param integer $passengerId
     * @return array
     */
    public function findUpcomingRidesByPassenger(int $passengerId): array
    {
        return $this->findAllRidesByPassenger($passengerId, RideStatus::DISPONIBLE);
    }

    /**
     * Récupére la liste brute des trajets passés d'un passager avec tri et pagination.
     *
     * @param integer $passengerId
     * @return array
     */
    public function fetchPastRidesByPassenger(int $passengerId): array
    {
        return $this->findAllRidesByPassenger($passengerId, RideStatus::TERMINE);
    }


    // Pour l'admin
    /**
     * Permet de calculer le nombre de trajet par champs.
     *
     * @param array $criteria
     * @return array|null
     */
    public function countRidesByFields(
        array $criteria,
    ): ?array {
        foreach ($criteria as $field => $value) {
            if (!$this->isAllowedField($field)) {
                return [];
            }
        }

        // Construction du sql
        $sql = "SELECT COUNT(ride_id) AS total_ride
                FROM {$this->table} 
                WHERE 1 = 1";

        // Construction dynamique des conditions
        foreach ($criteria as $field => $value) {
            if (!$this->isAllowedField($field)) {
                throw new InvalidArgumentException("Champ non autorisé : $field");
            }
            if ($value === null) {
                $sql .= " AND $field IS NULL";
            } else {
                $sql .= " AND $field = :$field";
            }
        }


        // Préparation de la requête
        $stmt = $this->db->prepare($sql);
        foreach ($criteria as $field => $value) {
            if ($value !== null) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue(":$field", $value, $type);
            }
        }
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Permet de calculer le total des commissions par champs.
     *
     * @param array $criteria
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array|null
     */
    public function countCommissionByFields(
        array $criteria,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): ?array {
        // Vérifie si chaque champ est autorisé.
        foreach ($criteria as $field => $value) {
            if (!$this->isAllowedField($field)) {
                return [];
            }
        }

        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'ride_id'
        );

        // Construction du sql
        $sql = "SELECT SUM(commission) AS total_commission
                FROM {$this->table} 
                WHERE 1 = 1";

        // Construction dynamique des conditions
        foreach ($criteria as $field => $value) {
            if (!$this->isAllowedField($field)) {
                throw new InvalidArgumentException("Champ non autorisé : $field");
            }
            if ($value === null) {
                $sql .= " AND $field IS NULL";
            } else {
                $sql .= " AND $field = :$field";
            }
        }

        // Tri et limite
        $sql .= " ORDER BY $orderBy $orderDirection 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        // Préparation de la requête
        $stmt = $this->db->prepare($sql);
        foreach ($criteria as $field => $value) {
            if ($value !== null) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue(":$field", $value, $type);
            }
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Calcule le nombre de trajet du jour.
     *
     * @return array|null
     */
    public function countRidesByToday(): ?array
    {
        $today = (new DateTime())->format('Y-m-d');
        return $this->countRidesByFields(['DATE(created_at)' => $today]);
    }

    /**
     * Calcule le total des commissions perçues du jour
     *
     * @return array|null
     */
    public function countCommissionByToday(): ?array
    {
        $today = (new DateTime())->format('Y-m-d');
        return $this->countCommissionByFields(['DATE(created_at)' => $today]);
    }

    /**
     * Calcule le nombre de trajet par période.
     *
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     * @return array|null
     */
    public function countRidesByPeriod(
        DateTimeInterface $start,
        DateTimeInterface $end
    ): ?array {
        return $this->countRidesByFields([
            'created_at >= ' => $start->format('Y-m-d H:i:s'),
            'created_at <= ' => $end->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Calcule le total des commissions perçues du jour
     *
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     * @return array|null
     */
    public function countCommissionByPeriod(
        DateTimeInterface $start,
        DateTimeInterface $end
    ): ?array {
        return $this->countCommissionByFields([
            'created_at >= ' => $start->format('Y-m-d H:i:s'),
            'created_at <= ' => $end->format('Y-m-d H:i:s'),
        ]);
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
