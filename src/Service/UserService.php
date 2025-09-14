<?php

namespace App\Services;


use App\Repositories\UserRepository;
use App\Repositories\CarRepository;
use App\Models\User;
use App\DTO\CreateUserDTO;
use App\DTO\UpdateUserDTO;
use App\Enum\UserRoles;
use InvalidArgumentException;

class UserService extends BaseService
{


    public function __construct(
        private UserRepository $userRepository,
        private CarRepository $carRepository
    ) {}


    //----------Action VISITEUR----------------------------

    /**
     * Permet à un visiteur de créer un compte.
     *
     * @param CreateUserDTO $dto
     * @return User|null
     */
    public function createAccount(
        CreateUserDTO $dto
    ): ?User {
        // Vérifier que l'email n'est pas déjà utilisé
        $existingUser = $this->userRepository->findUserByEmail($dto->email);

        if ($existingUser) {
            throw new InvalidArgumentException("Cet email est déjà utilisé.");
        }


        //Création de l'objet User
        $user = new User();

        $user->setUserLogin($dto->login);
        $user->setUserPhone($dto->phone);
        $user->setUserAddress($dto->address);
        $user->setUserCity($dto->city);
        $user->setUserZipCode($dto->zipCode);
        $user->setUserUriPicture($dto->uriPicture) ?? null;
        $user->setUserCredits(20);
        $user->setUserRoles([UserRoles::PASSAGER]);

        // Enregistrement dans la BD
        $this->userRepository->insertUser($user);

        return $user;
    }

    // ----------Actions PASSAGER --------------------------

    /**
     * Permet à un utilisateur de devenir CONDUCTEUR.
     *
     * @param UpdateUserDTO $dto
     * @param integer $passengerId
     * @return User|null
     */
    public function becomeDriver(
        UpdateUserDTO $dto,
        int $passengerId
    ): ?User {
        // Récupération du passager
        $passenger = $this->userRepository->findUserById($passengerId);

        // Vérification de l'existence du passeger
        if (!$passenger) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        $this->ensurePassenger($passengerId);

        $user = $this->userRepository->findUserById($passengerId);

        // Ajout des champs relatifs au CONDUCTEUR
        $user->setUserLicenceNo($dto->licenceNo);
        $user->addUserRole(UserRoles::CONDUCTEUR);


        // Enregistrement dans dans BD.
        $this->userRepository->updateUser($user);

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
        $user = $this->userRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification que l'utilisateur à supprimer n'a pas le rôle ADMIN ou EMPLOYE
        if ($this->ensureStaff($userId)) {
            throw new InvalidArgumentException("Les admins ou les employés ne peuvent pas supprimer leur compte.");
        }


        // Enregistrement en Bd
        $this->userRepository->deleteUser($userId);
        return true;
    }

    //----------Actions TOUT ROLE------------

    /**
     * Permet à tous les utilisateurs de modifier les informations relatives à leur compte.
     *
     * @param UpdateUserDTO $dto
     * @param integer $userId
     * @return User|null
     */
    public function updateProfile(
        UpdateUserDTO $dto,
        int $userId
    ): ?User {

        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        // Vérification des permissions.
        if (!$this->ensureUser($userId)) {
            throw new InvalidArgumentException("L'utilisateur n'est pas autorisé à modifier son profil.");
        }


        // Vérifications des champs modifiés et ajouts des nouvelles valeurs
        if (!empty($dto->lastName)) {
            $user->setUserLastName($dto->lastName);
        }
        if (!empty($dto->firstName)) {
            $user->setUserFirstName($dto->firstName);
        }
        if (!empty($dto->email)) {

            $existingUser = $this->userRepository->findUserByEmail($dto->email);

            if ($existingUser && $dto->email !== $user->getUserEmail()) {
                throw new InvalidArgumentException("L'email est déjà utilisé.");
            }

            $user->setUserEmail($dto->email);
        }
        if (!empty($dto->login)) {
            $existingUser = $this->userRepository->findUserByLogin($dto->login);

            if ($existingUser && $dto->login !== $user->getUserLogin()) {
                throw new InvalidArgumentException("Le login est déjà utilisé.");
            }
            $user->setUserLogin($dto->login);
        }
        if (!empty($dto->phone)) {
            $user->setUserPhone($dto->phone);
        }
        if (!empty($dto->address)) {
            $user->setUserAddress($dto->address);
        }
        if (!empty($dto->city)) {
            $user->setUserCity($dto->city);
        }
        if (!empty($dto->zip_code)) {
            $user->setUserZipCode($dto->zipCode);
        }
        if (!empty($dto->picture)) {
            $user->setUserUriPicture($dto->uriPicture);
        }
        if (!empty($dto->licenceNo) && $this->ensureDriver($userId)) {
            $user->setUserLicenceNo($dto->licenceNo);
        }

        $this->userRepository->updateUser($user);
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
        $user = $this->userRepository->findUserById($userId);

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
        $this->userRepository->updateUser($user);

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
        CreateUserDTO $dto,
        int $adminId
    ): ?User {
        // Récupération de l'admin
        $admin = $this->userRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        //Verification de la permission
        $this->ensureAdmin($adminId);


        // Vérifier que l'email n'est pas déjà utilisé
        $existingUser = $this->userRepository->findUserByEmail($dto->email);

        if ($existingUser) {
            throw new InvalidArgumentException("Cet email est déjà utilisé.");
        }


        //Création de l'objet User vide
        $user = new User(
            $dto->lastName,
            $dto->firstName,
            $dto->email,
            $dto->password,
            false
        );

        // Définition du rôle EMPLOYE
        $user->setUserRoles([UserRoles::EMPLOYE]);

        // Enregistrement dans la BD
        $this->userRepository->insertUser($user);

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
        $user = $this->userRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }


        // Récupération de l'admin
        $admin = $this->userRepository->findUserById($adminId);

        // Vérification de l'existence de l'admin
        if (!$admin) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        //Verification de la permission
        $this->ensureAdmin($adminId);


        // Enregistrement en Bd
        $this->userRepository->deleteUser($userId);

        return true;
    }
}
