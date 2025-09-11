<?php

namespace App\Services;


use App\Repositories\UserRelationsRepository;
use App\Repositories\CarRepository;
use App\Models\User;
use App\Enum\UserRoles;
use InvalidArgumentException;

class UserService extends BaseService
{


    public function __construct(
        private UserRelationsRepository $userRelationsRepository,
        private CarRepository $carRepository
    ) {
        parent::__construct();
    }


    //----------Action VISITEUR----------------------------

    /**
     * Permet à un visiteur de créer un compte.
     *
     * @param array $data
     * @return User|null
     */
    public function createAccount(
        array $data
    ): ?User {
        // Vérifier que l'email n'est pas déjà utilisé
        $existingUser = $this->userRelationsRepository->findUserByEmail($data['email']);

        if ($existingUser) {
            throw new InvalidArgumentException("Cet email est déjà utilisé.");
        }


        //Création de l'objet User
        $user = new User();

        $user->setUserLogin($data['login']);
        $user->setUserPhone($data['phone']);
        $user->setUserAddress($data['address']);
        $user->setUserCity($data['city']);
        $user->setUserZipCode($data['zip_code']);
        $user->setUserUriPicture($data['picture']) ?? null;
        $user->setUserCredits(($data['credits'] ?? 0) + 20);
        $user->setUserRoles([UserRoles::PASSAGER]);

        // Enregistrement dans la BD
        $this->userRelationsRepository->insertUser($user);

        return $user;
    }

    // ----------Actions PASSAGER --------------------------

    /**
     * Permet à un utilisateur de devenir CONDUCTEUR.
     *
     * @param array $data
     * @param integer $passengerId
     * @return User|null
     */
    public function becomeDriver(
        array $data,
        int $passengerId
    ): ?User {
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

    /**
     * Permet à un utilisateur PASSAGER OU CONDUCTEUR de supprimer son compte.
     *
     * @param integer $userId
     * @return boolean
     */
    public function deleteAccount(
        int $userId
    ): bool {
        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification que l'utilisateur à supprimer n'a pas le rôle ADMIN ou EMPLOYE
        if ($this->ensureStaff($userId)) {
            throw new InvalidArgumentException("Les admins ou les employés ne peuvent pas supprimer leur compte.");
        }


        // Enregistrement en Bd
        $this->userRelationsRepository->deleteUser($userId);
        return true;
    }

    //----------Actions TOUT ROLE------------

    /**
     * Permet à tous les utilisateurs de modifier les informations relatives à leur compte.
     *
     * @param array $newData
     * @param integer $userId
     * @return User|null
     */
    public function updateProfile(
        array $newData,
        int $userId
    ): ?User {

        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (!$this->ensureUser($userId)) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à modifier son profil.");
        }


        // Vérifications des champs modifiés et ajouts des nouvelles valeurs
        if (!empty($newData['last_name'])) {
            $user->setUserLastName($newData['last_name']);
        }
        if (!empty($newData['first_name'])) {
            $user->setUserFirstName($newData['first_name']);
        }
        if (!empty($newData['email'])) {

            $existingUser = $this->userRelationsRepository->findUserByEmail($newData['email']);

            if ($existingUser && $newData['email'] !== $user->getUserEmail()) {
                throw new InvalidArgumentException("L'email est déjà utilisé.");
            }

            $user->setUserEmail($newData['email']);
        }
        if (!empty($newData['login'])) {
            $existingUser = $this->userRelationsRepository->findUserByLogin($newData['login']);

            if ($existingUser && $newData['login'] !== $user->getUserLogin()) {
                throw new InvalidArgumentException("Le login est déjà utilisé.");
            }
            $user->setUserLogin($newData['login']);
        }
        if (!empty($newData['phone'])) {
            $user->setUserPhone($newData['phone']);
        }
        if (!empty($newData['address'])) {
            $user->setUserAddress($newData['address']);
        }
        if (!empty($newData['city'])) {
            $user->setUserCity($newData['city']);
        }
        if (!empty($newData['zip_code'])) {
            $user->setUserZipCode($newData['zip_code']);
        }
        if (!empty($newData['picture'])) {
            $user->setUserUriPicture($newData['picture']);
        }
        if (!empty($newData['licence_no']) && $this->roleService->hasRole($userId, 'CONDUCTEUR')) {
            $user->setUserLicenceNo($newData['licence_no']);
        }

        $this->userRelationsRepository->updateUser($user);
        return $user;
    }

    /**
     * Permet à tous les utilisateurs de modifier le mot de passe
     *
     * @param string $newPassword
     * @param string $oldPassword
     * @param integer $userId
     * @return boolean
     */
    public function modifyPassword(
        string $newPassword,
        string $oldPassword,
        int $userId
    ): bool {

        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (!$this->ensureUser($userId)) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à modifier son profil.");
        }

        // Vérification du hashage de l'ancien mot de passe
        if (!password_verify($oldPassword, $user->getUserPassword())) {
            throw new InvalidArgumentException("L'ancien mot de passe est incorrect.");
        }

        // Vérification du mot de passe
        if ($newPassword === $oldPassword) {
            throw new InvalidArgumentException("Le nouveau mot de passe doit être différent de l'ancien.");
        }

        // Mise à jour - hashage inclus dans le setter
        $user->setUserPassword($newPassword);

        // Enregistrement dans la BD
        $this->userRelationsRepository->updateUser($user);

        return true;
    }

    //----------Actions ADMIN ------------

    /**
     * Permet à un admin de créer un compte employé.
     *
     * @param array $data
     * @param integer $adminId
     * @return User|null
     */
    public function createEmployeeAccount(
        array $data,
        int $adminId
    ): ?User {
        // Récupération de l'admin
        $admin = $this->userRelationsRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        //Verification de la permission
        $this->ensureAdmin($adminId);


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

        // Définition du rôle EMPLOYE
        $user->setUserRoles([UserRoles::EMPLOYE]);

        // Enregistrement dans la BD
        $this->userRelationsRepository->insertUser($user);

        return $user;
    }

    /**
     * Permet à un admin de supprimer n'importe quel compte.
     *
     * @param integer $adminId
     * @param integer $userId
     * @return boolean
     */
    public function deleteAccountByAdmin(
        int $adminId,
        int $userId
    ): bool {
        // Récupération de l'utilisateur
        $user = $this->userRelationsRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }


        // Récupération de l'admin
        $admin = $this->userRelationsRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        //Verification de la permission
        $this->ensureAdmin($adminId);


        // Enregistrement en Bd
        $this->userRelationsRepository->deleteUser($userId);

        return true;
    }
}
