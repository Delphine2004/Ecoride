<?php

namespace App\Services;

use App\Repositories\CarRepository;
use App\Repositories\UserRelationsRepository;
use App\Models\Car;
use App\Enum\UserRoles;
use App\DTO\CreateCarDTO;
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
     *  Permet à un utilisateur CONDUCTEUR d'ajouter une voiture.
     *
     * @param integer $userId
     * @param CreateCarDTO $dto
     * @return Car|null
     */
    public function addCar(
        int $userId,
        CreateCarDTO $dto
    ): ?Car {
        // Récupération de l'utilisateur.
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur.
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        $this->ensureDriver($userId);


        // Création de l'objet Car
        $car = new Car();

        // Remplissage de l'objet
        $car->setCarOwnerId($userId);
        $car->setCarBrand($dto->brand);
        $car->setCarModel($dto->model);
        $car->setCarColor($dto->color);
        $car->setCarYear($dto->year);
        $car->setCarPower($dto->power);
        $car->setCarSeatsNumber($dto->seatsNumber);
        $car->setCarRegistrationNumber($dto->registrationNumber);
        $car->setCarRegistrationDate($dto->registrationDate);

        // Enregistrement dans la BD.
        $this->carRepository->insertCar($car);

        return $car;
    }

    /**
     * Permet à un utilisateur CONDUCTEUR de supprimer une voiture.
     *
     * @param integer $userId
     * @param integer $carId
     * @return void
     */
    public function removeCar(
        int $userId,
        int $carId
    ): void {
        // Récupération de l'utilisateur.
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur.
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (!$this->roleService->hasAnyRole($userId, [
            UserRoles::CONDUCTEUR,
            UserRoles::EMPLOYE,
            UserRoles::ADMIN
        ])) {
            throw new InvalidArgumentException("Seulement les utilisateurs CONDUCTEUR, EMPLOYEE ou ADMIN peuvent effectuer cette action.");
        }

        // Vérification si l'utilisateur CONDUCTEUR est le propriétaire.
        if ($this->roleService->isDriver($userId) && !$this->carRepository->isOwner($userId, $carId)) {
            throw new InvalidArgumentException("Vous ne pouvez pas supprimer cette voiture.");
        }

        // Enregistrement dans la BD.
        $this->carRepository->deleteCar($carId);
    }


    //------------------RECUPERATIONS------------------------

    /**
     * Récupére les voitures d'un utilisateur CONDUCTEUR.
     *
     * @param integer $userId
     * @return array
     */
    public function getCarsByUser(
        int $userId
    ): array {
        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        $this->ensureDriver($userId);

        return $this->carRepository->findAllCarsByOwner($userId);
    }
}
