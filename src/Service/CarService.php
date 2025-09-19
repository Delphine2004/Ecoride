<?php

namespace App\Service;

use App\Repository\CarRepository;
use App\Service\UserService;
use App\Model\Car;
use App\DTO\CreateCarDTO;
use InvalidArgumentException;


class CarService
{
    public function __construct(
        protected CarRepository $carRepository,
        protected UserService $userService
    ) {}

    /**
     * Vérifie si l'utilisateur a des voitures.
     *
     * @param integer $userId
     * @return boolean
     */
    public function userHasCars(
        int $userId
    ): bool {
        $this->userService->checkIfUserExists($userId);
        $this->userService->ensureDriver($userId);
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
    ): Car {
        $this->userService->checkIfUserExists($userId);
        $this->userService->ensureDriver($userId);

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
        $this->userService->checkIfUserExists($userId);

        // Vérification des permissions.
        $this->userService->ensureDriverAndStaff($userId);

        // Vérification si l'utilisateur CONDUCTEUR est le propriétaire.
        if ($this->userService->isDriver($userId) && !$this->carRepository->isOwner($userId, $carId)) {
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

        $this->userService->checkIfUserExists($userId);

        $this->userService->ensureDriverAndStaff($userId);

        $this->userService->checkIfUserExists($driverId);


        // Vérification qu'il s'agit bien du conducteur
        if ($this->userService->isDriver($driverId)) {
            if ($userId !== $driverId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->carRepository->findAllCarsByOwner($driverId);
    }
}
