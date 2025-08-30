<?php

namespace App\Repositories;

use App\Repository\BaseRepository;
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

    /**
     * @var string Le nom de la table en BDD
     */

    protected string $table = 'rides';
    protected string $primaryKey = 'ride_id';

    private UserRepository $userRepository;

    private array $allowedFields = ['ride_id', 'departure_date_time', 'departure_place', 'arrival_date_time', 'arrival_place', 'price', 'available_seats', 'ride_status', 'owner_id'];


    public function __construct(PDO $db, UserRepository $userRepository)
    {
        parent::__construct($db);
        $this->userRepository = $userRepository;
    }


    /**
     * Remplit un objet Ride avec les données de la table rides.
     *
     * @param array $data
     * @return Ride
     */
    private function hydrateRide(array $data): Ride
    {
        // Rechercher le conducteur du trajet.
        $driver = $this->userRepository->findUserById($data['owner_id']);

        //Vérifier que le conducteur existe.
        if (!$driver) {
            throw new InvalidArgumentException("Le conducteur du trajet {$data['ride_id']} est introuvable.");
        }

        return new Ride(
            rideId: (int)$data['ride_id'],
            driver: $driver,
            departureDateTime: new \DateTimeImmutable($data['departure_date_time']),
            departurePlace: $data['departure_place'],
            arrivalDateTime: new \DateTimeImmutable($data['arrival_date_time']),
            arrivalPlace: $data['arrival_place'],
            price: (float)$data['price'],
            availableSeats: (int)$data['available_seats'],
            rideStatus: RideStatus::from($data['ride_status']),
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


    // ------ Récupération ------ 

    /**
     * Récupére un trajet par son id.
     *
     * @param integer $rideId
     * @return Ride|null
     */
    public function findRideById(int $rideId): ?Ride
    {
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
    public function findAllRides(?string $orderBy = null, string $orderDirection = 'DESC', int $limit = 50, int $offset = 0): array
    {
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
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllRidesByField(string $field, mixed $value, ?string $orderBy = null, string $orderDirection = 'DESC', int $limit = 50, int $offset = 0): array
    {
        if (!in_array($field, $this->allowedFields)) {
            throw new InvalidArgumentException("Champ non autorisé ; $field");
        }

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
    public function findRideByDateAndPlace(\DateTimeInterface $date, string $departurePlace, ?string $arrivalPlace = null, string $orderBy = 'departure_date_time', string $orderDirection = 'DESC', int $limit = 20, int $offset = 0): array
    {
        // Sécurisation du champ ORDER BY
        if (!in_array($orderBy, $this->allowedFields, true)) {
            $orderBy = 'departure_date_time';
        }

        // Sécurisation du champ direction
        $orderDirection = strtoupper($orderDirection);
        if (!in_array($orderDirection, ['ASC', 'DESC'], true)) {
            $orderDirection = 'DESC';
        }

        // Construction du SQL
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

        $sql .= " ORDER BY r.$orderBy $orderDirection LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->hydrateRide((array) $row), $rows);
        // utilisation de la fonction : $this->findRideByDateAndPlace($date, 'Paris', 'Lyon', 'arrival_place', 'ASC', 50);
    }

    /**
     * Récupérer les trajets selon un utilisateur.
     *
     * @param integer $userId
     * @param UserRoles $role
     * @param string|null $status
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findRideByUser(int $userId, UserRoles $role, ?string $status = null, string $orderBy = 'departure_date_time', string $orderDirection = 'DESC', int $limit = 20, int $offset = 0): array
    {
        // Sécurisation du champ ORDER BY
        if (!in_array($orderBy, $this->allowedFields, true)) {
            $orderBy = 'departure_date_time';
        }

        // Sécurisation du champ direction
        $orderDirection = strtoupper($orderDirection);
        if (!in_array($orderDirection, ['ASC', 'DESC'], true)) {
            $orderDirection = 'DESC';
        }

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


        // Ajout du statut du trajet
        if ($status !== null) {
            $sql .= " AND r.ride_status = :status";
        }

        // Tri et limite
        $sql .= " ORDER BY r.$orderBy $orderDirection LIMIT :limit OFFSET :offset";


        // Préparation de la requête
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        if ($status !== null) {
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->hydrateRide((array) $row), $rows);
    }

    // Pour les conducteurs
    /**
     * Trouver tous les trajets d'un conducteur
     *
     * @param integer $driverId
     * @param string|null $status
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findRidesByDriver(int $driverId, ?string $status = null, string $orderBy = 'departure_date_time', string $orderDirection = 'DESC', int $limit = 20, int $offset = 0): array
    {
        return $this->findRideByUser($driverId, UserRoles::CONDUCTEUR, $status, $orderBy, $orderDirection, $limit, $offset);
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
     * @param integer $passengerId
     * @param string|null $status
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findRidesByPassenger(int $passengerId, ?string $status = null, string $orderBy = 'departure_date_time', string $orderDirection = 'DESC', int $limit = 20, int $offset = 0): array
    {
        return $this->findRideByUser($passengerId, UserRoles::PASSAGER, $status, $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Trouver tous les trajets à venir d'un passager
     *
     * @param integer $passengerId
     * @return array
     */
    public function findUpcomingRidesByPassenger(int $passengerId): array
    {
        return $this->findRidesByPassenger($passengerId, RideStatus::DISPONIBLE->value);
    }

    /**
     * Trouver tous les trajets passés d'un passager
     *
     * @param integer $passengerId
     * @return array
     */
    public function findPastRidesByPassenger(int $passengerId): array
    {
        return $this->findRidesByPassenger($passengerId, RideStatus::PASSE->value);
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
