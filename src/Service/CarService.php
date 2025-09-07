<?php

namespace App\Services;

use App\Repositories\CarRepository;
use App\Services\RoleService;
use App\Models\Car;
use InvalidArgumentException;

class CarService extends BaseService
{
    public function __construct(
        private CarRepository $carRepository,
    ) {
        parent::__construct();
    }

    // Vérifie si l'utilisateur a encore des voitures.
    public function userHasCars(int $userId): bool
    {
        $this->ensureDriver($userId);
        return count($this->carRepository->findAllCarsByOwner($userId)) > 0;
    }

    // Permet à un utilisateur ayant le rôle conducteur d'ajouter une voiture.
    public function addCar(int $userId, Car $car): int
    {
        $this->ensureDriver($userId);
        return $this->carRepository->insertCar($car);
    }

    // Permet à un utilisateur ayant le rôle conducteur de supprimer une voiture.
    public function removeCar(int $userId, int $carId): void
    {
        $this->ensureDriver($userId);
        if (!$this->carRepository->isOwner($userId, $carId)) {
            throw new InvalidArgumentException("Vous ne pouvez pas supprimer cette voiture.");
        }
        $this->carRepository->deleteCar($carId);
    }

    // Récupére les voitures d'un utilisateur.
    public function getCarsByUser(int $userId): array
    {
        $this->ensureDriver($userId);
        return $this->carRepository->findAllCarsByOwner($userId);
    }
}
