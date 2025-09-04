<?php

use App\Repositories\CarRepository;
use App\Repositories\UserRepository;

class CarService
{
    private CarRepository $carRepository;
    private UserRepository $userRepository;

    public function __construct(CarRepository $carRepository, UserRepository $userRepository)
    {
        $this->carRepository = $carRepository;
        $this->userRepository = $userRepository;
    }

    // Trouver toutes les voitures avec leur propriétaire - à vérifier car existe déjà dans carWithOwner
    public function findAllCarsWithOwner(): array
    {
        $cars = $this->carRepository->findAllCars();

        foreach ($cars as $car) {
            $owner = $this->userRepository->findUserById($car->getCarOwner()->getUserId());
            $car->setOwner($owner);
        }
        return $cars;
    }

    // Ajouter une voiture

    // Supprimer une voiture
}
