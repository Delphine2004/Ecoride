<?php

namespace App\Repositories;

use App\Repository\BaseRepository;
use App\Models\Car;
use App\Enum\CarPower;
use PDO;
use InvalidArgumentException;

/**
 * Cette classe gére la correspondance entre une voiture et la BDD.
 */

class CarRepository extends BaseRepository
{

    /**
     * @var string Le nom de la table en BDD.
     */
    protected string $table = 'cars';

    protected string $primaryKey = 'car_id'; // Utile car utiliser dans BaseRepository

    private UserRepository $userRepository;

    private array $allowedFields = ['car_id', 'car_brand', 'car_model', 'car_color', 'car_year', 'car_power', 'seats_number', 'registration_number', 'user_id'];


    public function __construct(PDO $db, UserRepository $userRepository)
    {
        parent::__construct($db);
        $this->userRepository = $userRepository;
    }


    /**
     * Remplit un objet Car avec les données de la table cars.
     *
     * @param array $data
     * @return Car
     */
    private function hydrateCar(array $data): Car
    {
        // Rechercher le propriétaire de la voiture.
        $owner = $this->userRepository->findUserById($data['user_id']);

        if (!$owner) {
            throw new InvalidArgumentException("Le propriétaire de la voiture {$data['car_id']} est introuvable.");
        }

        return new Car(
            carId: (int)$data['car_id'],
            owner: $owner,
            brand: $data['car_brand'],
            model: $data['car_model'],
            color: $data['car_color'],
            year: (int) $data['car_year'],
            power: CarPower::from($data['car_power']),
            seatsNumber: (int) $data['seats_number'],
            registrationNumber: $data['registration_number'],
            registrationDate: new \DateTimeImmutable($data['registration_date']),
            createdAt: !empty($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : null,

        );
    }

    /**
     * Transforme Car en tableau pour insert et update.
     *
     * @param Car $car
     * @return array
     */
    private function mapCarToArray(Car $car): array
    {
        return [
            'user_id' => $car->getCarOwner()->getUserId(),
            'car_brand' => $car->getBrand(),
            'car_model' => $car->getModel(),
            'car_color' => $car->getColor(),
            'car_year' => $car->getYear(),
            'car_power' => $car->getPower()->value,
            'seats_number' => $car->getSeatsNumber(),
            'registration_number' => $car->getRegistrationNumber(),
            'registration_date' => $car->getRegistrationDate()->format('Y-m-d')
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
     * Récupére une voiture par son id.
     *
     * @param integer $carId
     * @return Car|null
     */
    public function findCarById(int $carId): ?Car
    {
        // Chercher l'élément
        $row = parent::findById($carId);
        return $row ? $this->hydrateCar((array) $row) : null;
    }

    /**
     * Récupére toutes les voitures avec pagination et tri.
     *
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllCars(?string $orderBy = null, string $orderDirection = 'DESC', int $limit = 50, int $offset = 0): array
    {
        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'car_id'
        );

        // Chercher les éléments.
        $rows = parent::findAll($orderBy, $orderDirection, $limit, $offset);
        return array_map(fn($row) => $this->hydrateCar((array) $row), $rows);
    }

    /**
     * Récupére une voiture selon un champ spécifique.
     *
     * @param string $field
     * @param mixed $value
     * @return Car|null
     */
    public function findCarByField(string $field, mixed $value): ?Car
    {
        // Vérifie si le champ est autorisé.
        if (!$this->isAllowedField($field)) {
            return null;
        }

        // Chercher l'élément
        $row = parent::findOneByField($field, $value);
        return $row ? $this->hydrateCar((array) $row) : null;
    }


    /**
     * Récupére toutes les voitures selon un champ spécifique avec pagination et tri.
     *
     * @param string $field
     * @param mixed $value
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllCarsByField(string $field, mixed $value, ?string $orderBy = null, string $orderDirection = 'DESC', int $limit = 50, int $offset = 0): array
    {
        // Vérifie si le champ est autorisé.
        if (!$this->isAllowedField($field)) {
            return [];
        }

        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'car_id'
        );

        // Chercher les éléments.
        $rows = parent::findAllByField($field, $value, $orderBy, $orderDirection, $limit, $offset);
        return array_map(fn($row) => $this->hydrateCar((array) $row), $rows);
    }


    //  ------ Récupérations spécifiques ---------

    /**
     * Récupére toutes les voitures selon l'energie utilisée.
     *
     * @param string $power
     * @return array
     */
    public function findCarsByPower(string $power, ?string $orderBy = null, string $orderDirection = 'DESC', int $limit = 50, int $offset = 0): array
    {
        return $this->findAllCarsByField('car_power', $power, $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupére toutes les voitures d'un utilisateur conducteur avec tri et pagination.
     *
     * @param integer $ownerId
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findCarsByOwner(int $ownerId, string $orderBy = 'car_id', string $orderDirection = 'DESC', int $limit = 20, int $offset = 0): array
    {
        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'car_id'
        );

        // Construction du SQL
        $sql = "SELECT c.*
        FROM {$this->table} c
        
        WHERE c.user_id = :ownerId";

        //INNER JOIN users u ON c.user_id = u.user_id

        $sql .= " ORDER BY c.$orderBy $orderDirection 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':ownerId', $ownerId, PDO::PARAM_INT);

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->hydrateCar((array) $row), $rows);
    }

    // Afficher toutes les voitures avec leur propriétaire
    /* public function findAllCarsWithOwners(){
$sql = "SELECT c.*, u.user_id, u.name
FROM cars c
JOIN users u ON c.owner_id = u.user_id;
";
}
 */


    //------------------------------------------


    // ------ Mise à jour ------ 
    /**
     * Met à jour les données concernant une voiture.
     *
     * @param Car $car
     * @return boolean
     */
    public function updateCar(Car $car): bool
    {
        return $this->updateById($car->getCarId(), $this->mapCarToArray($car));
    }

    // ------ Insertion ------ 
    /**
     * Insert une voiture dans la BD.
     *
     * @param Car $car
     * @return integer
     */
    public function insertCar(Car $car): int
    {
        return $this->insert($this->mapCarToArray($car));
    }

    // ------ Suppression ------ 
    /**
     * Supprime une voiture de la BD.
     *
     * @param integer $id
     * @return boolean
     */
    public function deleteCar(int $id): bool
    {
        return $this->deleteById($id);
    }
}
