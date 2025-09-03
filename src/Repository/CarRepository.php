<?php

namespace App\Repositories;

use App\Repositories\BaseRepository;
use App\Models\Car;
use App\Enum\CarPower;
use PDO;

/**
 * Cette classe gére la correspondance entre une voiture et la BDD.
 */

class CarRepository extends BaseRepository
{

    protected string $table = 'cars';
    protected string $primaryKey = 'car_id';
    private array $allowedFields = [
        'car_id',
        'user_id',
        'car_brand',
        'car_model',
        'car_color',
        'car_year',
        'car_power',
        'seats_number',
        'registration_number'
    ];


    public function __construct(PDO $db)
    {
        parent::__construct($db);
    }


    /**
     * Remplit un objet Car avec les données de la table cars.
     *
     * @param array $data
     * @return Car
     */
    public function hydrateCar(array $data): Car
    {
        return new Car(
            carId: (int)$data['car_id'],
            owner: null, // car pas encore chargé
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

    // ------ Récupérations ------ 

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
    public function findAllCars(
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
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
     * Récupére une voiture selon un ou plusieurs champs spécifiques.
     *
     * @param string $field
     * @param mixed $value
     * @return Car|null
     */
    public function findCarByFields(
        array $criteria
    ): ?Car {
        // Vérifie si chaque champ est autorisé.
        foreach ($criteria as $field => $value) {
            if (!$this->isAllowedField($field)) {
                return null;
            }
        }

        // Chercher l'élément
        $row = parent::findOneByFields($criteria);
        return $row ? $this->hydrateCar((array) $row) : null;
    }

    /**
     * Récupére toutes les voitures selon un ou plusieurs champs spécifiques avec pagination et tri.
     *
     * @param array $criteria
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllCarsByFields(
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
            'car_id'
        );

        // Chercher les éléments.
        $rows = parent::findAllByFields($criteria, $orderBy, $orderDirection, $limit, $offset);
        return array_map(fn($row) => $this->hydrateCar((array) $row), $rows);
    }


    //  ------ Récupérations spécifiques ---------

    /**
     * Récupére toutes les voitures selon l'energie utilisée.
     *
     * @param string $power
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllCarsByPower(
        string $power,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->findAllCarsByFields(['car_power' => $power],  $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupére toutes les voitures d'un conducteur par son id avec tri et pagination.
     *
     * @param integer $ownerId
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllCarsByOwner(
        int $ownerId,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->findAllCarsByFields(['user_id' => $ownerId], $orderBy, $orderDirection, $limit, $offset);
    }


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
