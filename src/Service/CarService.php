<?php

namespace App\Service;

use App\Repository\CarRepository;
use App\Security\AuthService;
use App\Model\Car;
use App\DTO\CreateCarDTO;
use InvalidArgumentException;


class CarService
{
    public function __construct(
        protected CarRepository $carRepository,
        protected AuthService $authService,
    ) {}

    /**
     * Vérifie que la voiture existe.
     *
     * @param integer $carId
     * @return void
     */
    public function checkIfCarExists(int $carId): void
    {
        $car = $this->carRepository->findCarById($carId);

        if (!$car) {
            throw new InvalidArgumentException("Voiture introuvable.");
        }
    }

    /**
     * Vérifie si l'utilisateur a des voitures.
     *
     * @param integer $userId
     * @return boolean
     */
    public function userHasCars(
        int $userId
    ): bool {
        $this->authService->checkIfUserExists($userId);
        $this->authService->ensureDriver($userId);
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
        $this->authService->checkIfUserExists($userId);
        $this->authService->ensureDriver($userId);

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
        $this->authService->checkIfUserExists($userId);

        // Vérification des permissions.
        $this->authService->ensureDriverAndStaff($userId);

        // Vérification si l'utilisateur CONDUCTEUR est le propriétaire.
        if ($this->authService->isDriver($userId) && !$this->carRepository->isOwner($userId, $carId)) {
            throw new InvalidArgumentException("Vous ne pouvez pas supprimer cette voiture.");
        }

        // Enregistrement dans la BD.
        $this->carRepository->deleteCar($carId);
    }


    //------------------RECUPERATIONS------------------------

    /**
     * Récupére un Objet Car par son id.
     *
     * @param integer $carId
     * @return Car
     */
    public function getCarById(
        int $carId
    ): Car {
        $this->checkIfCarExists($carId);
        return $this->carRepository->findCarById($carId);
    }

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

        $this->authService->checkIfUserExists($userId);

        $this->authService->ensureDriverAndStaff($userId);

        $this->authService->checkIfUserExists($driverId);


        // Vérification qu'il s'agit bien du conducteur
        if ($this->authService->isDriver($driverId)) {
            if ($userId !== $driverId) {
                throw new InvalidArgumentException("Accés interdit.");
            }
        }

        return $this->carRepository->findAllCarsByOwner($driverId);
    }
}
