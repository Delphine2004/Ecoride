<?php

namespace App\Repositories;

use App\Models\Car;
use App\Models\User;
use App\Repositories\CarRepository;
use App\Repositories\UserRepository;
use PDO;


/**
 * Cette classe gére la correspondance entre une voiture et son propriétaire.
 */

class CarWithOwnerRepository extends CarRepository
{

    protected string $table = 'cars';
    protected string $primaryKey = 'car_id';

    private UserRepository $userRepository;

    public function __construct(PDO $db, UserRepository $userRepository)
    {
        parent::__construct($db);
        $this->userRepository = $userRepository;
    }

    /**
     * Mutualisation du mapping de User.
     *
     * @param array $row
     * @return User
     */
    private function mapOwner(array $row): User
    {
        return $this->userRepository->hydrateUser([
            'user_id' => $row['owner_id'],
            'first_name' => $row['owner_first_name'],
            'last_name' => $row['owner_last_name'],
            'user_name' => $row['owner_user_name']
        ]);
    }


    /**
     * Trouver une voiture avec son propriétaire.
     *
     * @param integer $carId
     * @return Car|null
     */
    public function findCarWithOwner(int $carId): ?Car
    {
        // Construction du sql
        $sql = "SELECT c.*,
               u.user_id AS owner_id,
               u.first_name AS owner_first_name,
               u.last_name AS owner_last_name,
               u.user_name AS owner_user_name 
               FROM {$this->table} c
               INNER JOIN users u ON c.user_id = u.user_id
               WHERE c.car_id = :car_id
        ";

        // Préparation de la requête
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('car_id', $carId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        // Hydratation de Car
        $car = $this->hydrateCar($row);

        // Hydratation de User
        $owner = $this->mapOwner($row);

        // Association du propriétaire à la voiture.
        $car->setCarOwner($owner);

        return $car;
    }


    /**
     * Touver toutes les voitures avec leur propriétaire.
     *
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllCarsWithOwner(
        string $orderBy = 'user_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'user_id'
        );

        //Construction du sql
        $sql = "SELECT c.*,
               u.user_id AS owner_id,
               u.first_name AS owner_first_name,
               u.last_name AS owner_last_name,
               u.user_name AS owner_user_name 
               FROM {$this->table} c
               INNER JOIN users u ON c.user_id = u.user_id
        ";

        // Tri et limite
        $sql .= " ORDER BY r.$orderBy $orderDirection 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        //Preparation de la requête
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) return [];


        // Hydratations
        $cars = [];
        foreach ($rows as $row) {
            // Hydratation de Car
            $car = $this->hydrateCar($row);

            //Hydratation du propriétaire
            $owner = $this->mapOwner($row);

            // Association propriétaire - voiture
            $car->setCarOwner($owner);
            $cars[] = $car;
        }
        return $cars;
    }
}
