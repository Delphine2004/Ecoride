<?php

namespace App\Repositories;

use App\Models\BaseModel;
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

    private array $allowedFields = ['ride_id', 'departure_date_time', 'departure_place', 'arrival_date_time', 'arrival_place', 'price', 'available_seats', 'status', 'user_id'];

    public function __construct(PDO $db, UserRepository $userRepository)
    {
        parent::__construct($db);
        $this->userRepository = $userRepository;
    }


    /**
     * Hydrate un tableau BDD en objet Ride
     *
     * @param array $data
     * @return Ride
     */
    private function hydrateRide(array $data): Ride
    {

        $driver = $this->userRepository->findUserById($data['user_id']);

        if (!$driver) {
            throw new InvalidArgumentException("Le conducteur du trajet {$data['ride_id']} est introuvable.");
        }
        return new Ride(
            id: (int)$data['ride_id'],
            driver: $driver,
            departureDateTime: $data['departure_date_time'],
            departurePlace: $data['departure_place'],
            arrivalDateTime: $data['arrival_date_time'],
            arrivalPlace: $data['arrival_place'],
            price: (int)$data['price'],
            availableSeats: (int)$data['available_seats'],
            status: RideStatus::from($data['status']), //RAJOUTER DISPONIBLE PAR DEFAUT
            createdAt: new \DateTimeImmutable($data['created_at']),
            updatedAt: new \DateTimeImmutable($data['updated_at']),

        );
    }

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
     * Récupére tous les trajets.
     *
     * @return array
     */
    public function findAllRides(): array
    {
        $rows = parent::findAll();
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
     * Récupére tous les trajets selon un champ spécifique.
     *
     * @param string $field
     * @param mixed $value
     * @return array
     */
    public function findAllRidesByField(string $field, mixed $value): array
    {
        if (!in_array($field, $this->allowedFields)) {
            throw new InvalidArgumentException("Champ non autorisé ; $field");
        }

        $rows = parent::findAllByField($field, $value);
        return array_map(fn($row) => $this->hydrateRide((array) $row), $rows);
    }


    // ---------------------



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
