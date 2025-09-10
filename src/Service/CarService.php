<?php

namespace App\Services;

use App\Repositories\CarRepository;
use App\Repositories\UserRelationsRepository;
use App\Models\Car;
use InvalidArgumentException;

class CarService extends BaseService
{
    public function __construct(
        private CarRepository $carRepository,
        private UserRelationsRepository $userRelationsRepository
    ) {
        parent::__construct();
    }


    //--------------VERIFICATION-----------------

    /**
     * Vérifie si l'utilisateur a des voitures.
     *
     * @param integer $userId
     * @return boolean
     */
    public function userHasCars(int $userId): bool
    {
        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        $this->ensureDriver($userId);
        return count($this->carRepository->findAllCarsByOwner($userId)) > 0;
    }


    //-----------------ACTIONS------------------------------

    /**
     * Permet à un utilisateur CONDUCTEUR d'ajouter une voiture.
     *
     * @param integer $userId
     * @param Car $car
     * @return integer
     */
    public function addCar(int $userId, Car $car): int
    {
        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }
        $this->ensureDriver($userId);
        return $this->carRepository->insertCar($car);
    }

    /**
     * Permet à un utilisateur CONDUCTEUR de supprimer une voiture.
     *
     * @param integer $userId
     * @param integer $carId
     * @return void
     */
    public function removeCar(int $userId, int $carId): void
    {
        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        $this->ensureDriver($userId);
        if (!$this->carRepository->isOwner($userId, $carId)) {
            throw new InvalidArgumentException("Vous ne pouvez pas supprimer cette voiture.");
        }
        $this->carRepository->deleteCar($carId);
    }


    //------------------RECUPERATIONS------------------------

    /**
     * Récupére les voitures d'un utilisateur CONDUCTEUR.
     *
     * @param integer $userId
     * @return array
     */
    public function getCarsByUser(int $userId): array
    {
        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        $this->ensureDriver($userId);
        return $this->carRepository->findAllCarsByOwner($userId);
    }
}
