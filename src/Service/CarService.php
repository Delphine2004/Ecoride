<?php

namespace App\Service;

use App\Model\Car;
use App\Enum\UserRoles;
use App\DTO\CreateCarDTO;
use App\Repository\CarRepository;
use App\Repository\UserRepository;
use App\Service\RoleService;

use InvalidArgumentException;


class CarService extends BaseService
{
    public function __construct(
        private CarRepository $carRepository,
        private UserRepository $userRepository,
        private RoleService $roleService
    ) {}


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
        $user = $this->userRepository->findUserById($userId);

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
     * @param CreateCarDTO $dto
     * @param integer $userId
     * @return Car|null
     */
    public function createCar(
        CreateCarDTO $dto,
        int $userId
    ): ?Car {
        // Récupération de l'utilisateur.
        $user = $this->userRepository->findUserById($userId);

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
     * Permet à un utilisateur CONDUCTEUR OU EMPLOYE OU ADMIN de supprimer une voiture.
     *
     * @param integer $carId
     * @param integer $userId
     * @return void
     */
    public function removeCar(
        int $carId,
        int $userId
    ): void {
        // Récupération de l'utilisateur.
        $user = $this->userRepository->findUserById($userId);

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
            throw new InvalidArgumentException("Seulement les utilisateurs CONDUCTEUR, EMPLOYE ou ADMIN peuvent effectuer cette action.");
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
     * Permet à un utilisateur CONDUCTEUR OU EMPLOYE OU ADMIN de récupèrer les voitures d'un utilisateur CONDUCTEUR.
     *
     * @param integer $driverId
     * @param integer $userId
     * @return array
     */
    public function listCarsByDriver(
        int $driverId,
        int $userId
    ): array {
        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
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

        // Récupération du conducteur
        $driver = $this->userRepository->findUserById($driverId);

        // Vérification de l'existence du conducteur
        if (!$driver) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification qu'il s'agit bien du conducteur
        if ($this->roleService->isDriver($userId)) {
            if ($userId !== $driverId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->carRepository->findAllCarsByOwner($driverId);
    }
}
