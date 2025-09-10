<?php

namespace App\Services;

use App\Repositories\UserRelationsRepository;
use InvalidArgumentException;

class ReviewService extends BaseService
{

    public function __construct(

        private UserRelationsRepository $userRelationsRepository
    ) {}


    // Permet à un utilisateur PASSAGER de laisser un commentaire à un utilisateur CONDUCTEUR.
    public function leaveReview(int $passengerId): void
    {

        // Récupération du passager
        $passenger = $this->userRelationsRepository->findUserById($passengerId);

        // Vérification de l'existence du passeger
        if (!$passenger) {
            throw new InvalidArgumentException("Passager introuvable.");
        }

        // Vérification de la permission
        $this->ensurePassenger($passengerId);
    }

    //----------Actions EMPLOYEE ------------

    //Fonction qui permet à un employé d'approuver un commentaire.
    public function approveReview(int $employeeId, string $review, int $rate): void
    {

        // Récupération de l'employé
        $employee = $this->userRelationsRepository->findUserById($employeeId);

        // Vérification de l'existence de l'employé
        if (!$employee) {
            throw new InvalidArgumentException("Employé introuvable.");
        }

        // Vérification de la permission
        $this->ensureEmployee($employeeId);
    }
}
