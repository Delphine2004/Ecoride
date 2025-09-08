<?php

namespace App\Service;

use App\Repositories\UserRelationsRepository;
use App\Models\User;
use App\Services\BaseService;


use InvalidArgumentException;

class UserService extends BaseService
{


    public function __construct(
        private UserRelationsRepository $userRelationsRepository
    ) {
        parent::__construct();
    }


    //----------Action VISITEUR------------

    // Permet à un visiteur de créer un compte
    public function createAccount(array $data)
    {
        //createUserFromForm() -- contient le hashage du mdp
    }


    //----------Actions TOUT ROLE------------

    // Permet à tous les utilisateurs de modifier les informations relatives à leur compte.
    public function updateProfile(array $newData, int $userId): ?User
    {

        // Vérification de la permission
        $this->ensurePassenger($userId);

        // Vérification de l'existance de l'utilisateur
        if (!$userId) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérifications des champs modifiés et ajouts des nouvelles valeurs
        if (isset($newData['last_name'])) {
            $user->setLastName($newData['last_name']);
        }
        if (isset($newData['first_name'])) {
            $user->setFirstName($newData['first_name']);
        }
        if (isset($newData['email'])) {
            $user->setEmail($newData['email']);
        }
        if (isset($newData['user_name'])) {
            $user->setUserName($newData['user_name']);
        }
        if (isset($newData['phone'])) {
            $user->setPhone($newData['phone']);
        }
        if (isset($newData['address'])) {
            $user->setAddress($newData['address']);
        }
        if (isset($newData['city'])) {
            $user->setCity($newData['city']);
        }
        if (isset($newData['zip_code'])) {
            $user->setZipCode($newData['zip_code']);
        }
        if (isset($newData['picture'])) {
            $user->setUriPicture($newData['picture']);
        }
        if (isset($newData['licence_no'])) {
            $user->setLicenceNo($newData['licence_no']);
        }

        $this->userRelationsRepository->updateUser($user);
        return $user;
    }

    // Permet à tous les utilisateurs de modifier le mot de passe
    public function modifyPassword(string $newPassword, string $oldPassword, int $userId) //: bool
    {

        // Vérification de la permission
        $this->ensurePassenger($userId);

        // Vérification de l'existance de l'utilisateur
        if (!$userId) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        /*
        if (!$newPassword->verifyPassword($oldPassword)) {
            throw new InvalidArgumentException("Le mot de passe actuel est incorrect.");
        }
        $this->userRelationsRepository->validatePassword($newPassword);
        $this->userRelationsRepository->setPassword($newPassword);*/
    }


    //----------Actions PASSAGER OU CONDUCTEUR ------------

    // Permet à un utilisateur PASSAGER OU CONDUCTEUR de supprimer son compte.
    public function deleteAccount(int $userId): void
    {
        // Vérification des permissions
        $this->ensurePassenger($userId);

        if ($this->ensureAdmin($userId) || $this->ensureEmployee($userId)) {
            throw new InvalidArgumentException("Un admin ou un employé ne peut pas supprimer son compte.");
        }

        // Vérification de l'existance de l'utilisateur
        if (!$userId) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Enregistrement en Bd
        $this->userRelationsRepository->deleteUser($userId);
    }


    //----------Actions ADMIN ------------

    // Permet à un admin de créer un compte employé.
    public function createEmployeeAccount(array $data, int $adminId) //: int
    {
        //Verification de la permission
        $this->ensureAdmin($adminId);

        // Vérification de l'existance de l'utilisateur
        if (!$adminId) {
            throw new InvalidArgumentException("Admin introuvable.");
        }
    }

    // Permet à un admin de supprimer n'importe quel compte.
    public function deleteAccountByAdmin(int $adminId, int $userId)
    {

        //Verification de la permission
        $this->ensureAdmin($adminId);

        // Vérification de l'existance de l'utilisateur
        if (!$adminId) {
            throw new InvalidArgumentException("Admin introuvable.");
        }

        // Vérification du statut de l'utilisateur
        if (!$userId) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Enregistrement en Bd
        $this->userRelationsRepository->deleteUser($userId);
    }





    // --------------------------------------A METTRE DANS REVIEWSERVICE
    // Permet à un utilisateur PASSAGER de laisser un commentaire à un utilisateur CONDUCTEUR.
    public function leaveReview(int $passengerId): void
    {
        // Vérification de la permission
        $this->ensurePassenger($passengerId);

        // Vérification de l'existance de l'utilisateur
        if (!$passengerId) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }
    }

    //----------Actions EMPLOYEE ------------

    //Fonction qui permet à un employé d'approuver un commentaire.
    public function approveReview(int $employeeId, string $review, int $rate): void
    {
        // Vérification de la permission
        $this->ensureEmployee($employeeId);

        // Vérification de l'existance de l'utilisateur
        if (!$employeeId) {
            throw new InvalidArgumentException("Employé introuvable.");
        }
    }
}
