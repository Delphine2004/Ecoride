<?php

namespace App\Services;

use App\Enum\UserRoles;
use App\Repositories\UserRelationsRepository;
use App\Repositories\CarRepository;
use App\Models\User;
use App\Services\BaseService;


use InvalidArgumentException;

class UserService extends BaseService
{


    public function __construct(
        private UserRelationsRepository $userRelationsRepository,
        private CarRepository $carRepository
    ) {
        parent::__construct();
    }


    //----------Action VISITEUR------------

    // Permet à un visiteur de créer un compte
    public function createAccount(array $data): User
    {
        // Vérifier que l'email n'est pas déjà utilisé
        $existingUser = $this->userRelationsRepository->findUserByEmail($data['email']);

        if ($existingUser) {
            throw new InvalidArgumentException("Cet email est déjà utilisé.");
        }


        //Création de l'objet User vide
        $user = new User(
            $data['last_name'],
            $data['first_name'],
            $data['email'],
            $data['password'],
            false
        );

        $user->setUserLogin($data['login']);
        $user->setUserPhone($data['phone']);
        $user->setUserAddress($data['address']);
        $user->setUserCity($data['city']);
        $user->setUserZipCode($data['zip_code']);
        $user->setUserUriPicture($data['picture']) ?? null;
        $user->setUserCredits(($data['credits'] ?? 0) + 20);
        // Le rôle passager est défini par défaut dans l'entité User

        //Enregistrement dans la BD
        $this->userRelationsRepository->insertUser($user);

        return $user;
    }

    // -----------------Actions PASSAGER ------------------

    public function becomeDriver(array $data, int $passengerId): User
    {
        // Récupération du passager
        $passenger = $this->userRelationsRepository->findUserById($passengerId);

        // Vérification de l'existence du passeger
        if (!$passenger) {
            throw new InvalidArgumentException("Passager introuvable.");
        }

        // Vérification des permissions.
        $this->ensurePassenger($passengerId);

        $user = $this->userRelationsRepository->findUserById($passengerId);

        // Ajout des champs relatifs au CONDUCTEUR
        $user->setUserLicenceNo($data['licence_no']);
        $user->addUserRole(UserRoles::CONDUCTEUR);


        // Enregistrement dans dans BD.
        $this->userRelationsRepository->updateUser($user);

        return $user;
    }

    //----------Actions PASSAGER OU CONDUCTEUR ------------

    // Permet à un utilisateur PASSAGER OU CONDUCTEUR de supprimer son compte.
    public function deleteAccount(int $userId) //: bool
    {
        // Vérification des permissions
        $this->ensurePassenger($userId);

        if ($this->ensureAdmin($userId) || $this->ensureEmployee($userId)) {
            throw new InvalidArgumentException("Un admin ou un employé ne peut pas supprimer son compte.");
        }

        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Enregistrement en Bd
        $this->userRelationsRepository->deleteUser($userId);
    }

    //----------Actions TOUT ROLE------------

    // Permet à tous les utilisateurs de modifier les informations relatives à leur compte.
    public function updateProfile(array $newData, int $userId): ?User
    {

        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (
            !$this->roleService->hasRole($userId, 'PASSAGER') &&
            !$this->roleService->hasRole($userId, 'CONDUCTEUR') &&
            !$this->roleService->hasRole($userId, 'EMPLOYE') &&
            !$this->roleService->hasRole($userId, 'ADMIN')
        ) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à modifier son profil.");
        }

        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);


        // Vérifications des champs modifiés et ajouts des nouvelles valeurs
        if (isset($newData['last_name'])) {
            $user->setUserLastName($newData['last_name']);
        }
        if (isset($newData['first_name'])) {
            $user->setUserFirstName($newData['first_name']);
        }
        if (isset($newData['email'])) {
            $user->setUserEmail($newData['email']);
        }
        if (isset($newData['login'])) {
            $user->setUserLogin($newData['login']);
        }
        if (isset($newData['phone'])) {
            $user->setUserPhone($newData['phone']);
        }
        if (isset($newData['address'])) {
            $user->setUserAddress($newData['address']);
        }
        if (isset($newData['city'])) {
            $user->setUserCity($newData['city']);
        }
        if (isset($newData['zip_code'])) {
            $user->setUserZipCode($newData['zip_code']);
        }
        if (isset($newData['picture'])) {
            $user->setUserUriPicture($newData['picture']);
        }
        if (isset($newData['licence_no'])) {
            $user->setUserLicenceNo($newData['licence_no']);
        }

        $this->userRelationsRepository->updateUser($user);
        return $user;
    }

    // Permet à tous les utilisateurs de modifier le mot de passe
    public function modifyPassword(string $newPassword, string $oldPassword, int $userId) //: bool
    {

        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (
            !$this->roleService->hasRole($userId, 'PASSAGER') &&
            !$this->roleService->hasRole($userId, 'CONDUCTEUR') &&
            !$this->roleService->hasRole($userId, 'EMPLOYE') &&
            !$this->roleService->hasRole($userId, 'ADMIN')
        ) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à modifier son profil.");
        }


        // Vérification du mot de passe
        if ($newPassword = $oldPassword) {
            throw new InvalidArgumentException("Le nouveau mot de passe doit être différent de l'ancien.");
        }

        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        $user->setUserPassword($newPassword);
    }

    //----------Actions ADMIN ------------

    // Permet à un admin de créer un compte employé.
    public function createEmployeeAccount(array $data, int $adminId) // : ?User
    {


        // Récupération de l'admin
        $admin = $this->userRelationsRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        //Verification de la permission
        $this->ensureAdmin($adminId);
    }

    // Permet à un admin de supprimer n'importe quel compte.
    public function deleteAccountByAdmin(int $adminId, int $userId): void
    {



        // Récupération de l'admin
        $admin = $this->userRelationsRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        //Verification de la permission
        $this->ensureAdmin($adminId);

        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Enregistrement en Bd
        $this->userRelationsRepository->deleteUser($userId);
    }





    // --------------------------------------A METTRE DANS REVIEWSERVICE
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
