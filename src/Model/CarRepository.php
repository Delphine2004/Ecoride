<?php

namespace App\Repositories;

use App\Models\BaseModel;
use App\Models\Car;
use App\Models\User;
use App\Enum\CarPower;
use PDO;
use InvalidArgumentException;

/**
 * Cette classe gére la correspondance entre une voiture et la BDD.
 */

class CarRepository extends BaseModel
{

    /**
     * @var string Le nom de la table en BDD.
     */

    protected string $table = 'cars';
    protected string $primaryKey = 'car_id';
    protected string $entityClass = Car::class;

    private UserRepository $userRepository;
    private array $allowedFields = ['car_id', 'car_brand', 'car_model', 'car_color', 'car_year', 'car_power', 'seats_number', 'registration_number', 'user_id'];


    public function __construct(PDO $db, UserRepository $userRepository)
    {
        parent::__construct($db);
        $this->userRepository = $userRepository;
    }


    /**
     * Fonction qui remplit un objet Car avec les données de la table Car lors de l'instanciation.
     *
     * @param array $data
     * @return Car
     */
    private function hydrateCar(array $data): Car
    {

        $owner = $this->userRepository->getUserById($data['user_id']);

        if (!$owner) {
            throw new InvalidArgumentException("Le propriétaire de la voiture {$data['car_id']} est introuvable.");
        }

        return new Car(
            id: $data['car_id'],
            owner: $owner,
            brand: $data['car_brand'],
            model: $data['car_model'],
            color: $data['car_color'],
            year: (int) $data['car_year'],
            power: CarPower::from($data['car_power']),
            seatsNumber: (int) $data['seats_number'],
            registrationNumber: $data['registration_number'],
            registrationDate: new \DateTimeImmutable($data['registration_date']),
            createdAt: new \DateTimeImmutable($data['created_at']),

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


    // ------ Récupération ------ 
    /**
     * Fonction qui permet de rechercher une voiture par son id.
     *
     * @param integer $id
     * @return Car|null
     */
    public function findCarById(int $id): ?Car
    {
        $row = parent::findById($id);
        return $row ? $this->hydrateCar((array) $row) : null;
    }

    /**
     * Récupére toutes les voitures.
     *
     * @return array
     */
    public function findAllCars(): array
    {
        $rows = parent::findAll();
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
        if (!in_array($field, $this->allowedFields)) {
            throw new InvalidArgumentException("Champ non autorisé ; $field");
        }

        $row = parent::findOneByField($field, $value);
        return $row ? $this->hydrateCar((array) $row) : null;
    }

    /**
     * Fonction qui permet de rechercher des voitures par un champs spécifique.
     *
     * @param string $field
     * @param mixed $value
     * @return array
     */
    public function findAllCarsByField(string $field, mixed $value): array
    {
        if (!in_array($field, $this->allowedFields)) {
            throw new InvalidArgumentException("Champ non autorisé ; $field");
        }

        $rows = parent::findAllByField($field, $value);
        return array_map(fn($row) => $this->hydrateCar((array) $row), $rows);
    }


    // ----------------------------

    /**
     * Fonction qui permet de retourner toutes les voitures d'un propriétaire.
     *
     * @param integer $ownerId
     * @return array
     */
    public function findCarsByOwner(int $ownerId): array
    {
        return $this->findAllCarsByField('user_id', $ownerId);
    }

    /**
     * Fonction qui recherche toutes les voitures selon l'energie utilisée.
     *
     * @param string $power
     * @return array
     */
    public function findCarsByPower(string $power): array
    {
        return $this->findAllCarsByField('car_power', $power);
    }


    // ------ Mise à jour ------ 
    /**
     * Met à jour les données concernant une voiture.
     *
     * @param Car $car
     * @return boolean
     */
    public function updateCar(Car $car): bool
    {
        return $this->updateById($car->getId(), $this->mapCarToArray($car));
    }

    // ------ Insertion ------ 
    /**
     * Insert une voiture dans la BD.
     *
     * @param array $data
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
