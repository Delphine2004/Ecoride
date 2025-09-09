<?php

namespace App\Services;

use App\Repositories\CarRepository;
use App\Models\Car;
use InvalidArgumentException;

class CarService extends BaseService
{
    public function __construct(
        private CarRepository $carRepository,
    ) {
        parent::__construct();
    }


    //--------------VERIFICATION-----------------

    // Vérifie si l'utilisateur a encore des voitures.
    public function userHasCars(int $userId): bool
    {
        // Vérification de l'existence de l'utilisateur
        if (!$userId) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        $this->ensureDriver($userId);
        return count($this->carRepository->findAllCarsByOwner($userId)) > 0;
    }


    //-----------------ACTIONS------------------------------

    // Permet à un utilisateur CONDUCTEUR d'ajouter une voiture.
    public function addCar(int $userId, Car $car): int
    {
        // Vérification de l'existence de l'utilisateur
        if (!$userId) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }
        $this->ensureDriver($userId);
        return $this->carRepository->insertCar($car);
    }

    // Permet à un utilisateur CONDUCTEUR de supprimer une voiture.
    public function removeCar(int $userId, int $carId): void
    {
        // Vérification de l'existence de l'utilisateur
        if (!$userId) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        $this->ensureDriver($userId);
        if (!$this->carRepository->isOwner($userId, $carId)) {
            throw new InvalidArgumentException("Vous ne pouvez pas supprimer cette voiture.");
        }
        $this->carRepository->deleteCar($carId);
    }


    //------------------RECUPERATIONS------------------------

    // Récupére les voitures d'un utilisateur CONDUCTEUR.
    public function getCarsByUser(int $userId): array
    {
        // Vérification de l'existence de l'utilisateur
        if (!$userId) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        $this->ensureDriver($userId);
        return $this->carRepository->findAllCarsByOwner($userId);
    }
}
