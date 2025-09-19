<?php

namespace App\Repository;

use App\Model\Car;
use App\Enum\CarBrand;
use App\Enum\CarColor;
use App\Enum\CarPower;
use PDO;

/**
 * Cette classe gère la correspondance entre une voiture et la BDD.
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
        'registration_number',
        'registration_date',
        'created_at'
    ];

    public function __construct(
        ?PDO $db = null
    ) {
        parent::__construct(\App\Model\Car::class, $db);
    }


    /**
     * Transforme Car en tableau pour insert et update.
     *
     * @param Car $car
     * @return array
     */
    private function mapCarToArray(
        Car $car
    ): array {
        return [
            'user_id' => $car->getCarOwner() ? $car->getCarOwner()->getUserId() : null, // à surveiller
            'car_brand' => $car->getCarBrand()->value,
            'car_model' => $car->getCarModel(),
            'car_color' => $car->getCarColor()->value,
            'car_year' => $car->getCarYear(),
            'car_power' => $car->getCarPower()->value,
            'seats_number' => $car->getCarSeatsNumber(),
            'registration_number' => $car->getCarRegistrationNumber(),
            'registration_date' => $car->getCarRegistrationDate()->format('Y-m-d')
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

    /**
     * Permet de savoir si le conducteur est le propriétaire de la voiture.
     *
     * @param integer $userId
     * @param integer $carId
     * @return boolean
     */
    public function isOwner(
        int $userId,
        int $carId
    ): bool {

        // Construction du sql
        $sql = "SELECT COUNT(*)
        FROM {$this->table}
        WHERE car_id = :carId AND user_id = :ownerId";

        // Préparation de la requête
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':carId', $carId, PDO::PARAM_INT);
        $stmt->bindValue(':ownerId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() > 0;
    }

    // ------ Récupérations ------ 

    /**
     * Récupère un objet Car par son id.
     *
     * @param integer $carId
     * @return Car|null
     */
    public function findCarById(
        int $carId
    ): ?Car {
        return parent::findById($carId);
    }

    /**
     * Récupère la liste des objets Car selon un ou plusieurs champs spécifiques avec tri et pagination.
     *
     * @param array $criteria
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return Car[]
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

        return parent::findAllByFields($criteria, $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupère une liste brute des voitures selon un champ spécifique avec tri et pagination.
     *
     * @param array $criteria
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function fetchAllCarsRowsByFields(
        array $criteria = [],
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {

        // Pas nécessaire de vérifier les champs car table pivot.
        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'car_id'
        );

        return parent::findAllByFields($criteria, $orderBy, $orderDirection, $limit, $offset);
    }


    // ------ Récupérations spécifiques de liste d'objet ---------

    /**
     * Récupère la liste des objets Car selon l'énergie utilisée avec tri et pagination.
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
     * Récupère la liste des objets Car selon l'id du propriétaire avec tri et pagination.
     *
     * @param int $userId
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllCarsByOwner(
        int $userId,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->findAllCarsByFields(['user_id' => $userId],  $orderBy, $orderDirection, $limit, $offset);
    }

    // ------ Récupérations spécifiques de liste brute ---------
    /**
     * Récupère la liste brute des voitures par l'id du conducteur avec tri et pagination.
     *
     * @param int $ownerId
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function fetchAllCarsByOwner(
        int $ownerId,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->fetchAllCarsRowsByFields(['user_id' => $ownerId], $orderBy, $orderDirection, $limit, $offset);
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
     * Insère une voiture dans la BD.
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
