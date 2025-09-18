<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Model\User;
use App\DTO\CreateUserDTO;
use App\DTO\UpdateUserDTO;
use App\Enum\UserRoles;
use InvalidArgumentException;

class UserService
{

    public function __construct(
        protected UserRepository $userRepository
    ) {}

    // Vérification de l'existence de l'utilisateur
    public function checkIfUserExists(int $userId): void
    {
        $user = $this->userRepository->findUserById($userId);

        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }
    }

    //------------------------Vérification des rôles-------------------
    /**
     * Vérifie un rôle en particulier.
     *
     * @param integer $userId
     * @param string $roleName
     * @return boolean
     */
    public function hasRole(int $userId, UserRoles $role): bool
    {
        // Trouver les roles de l'utilisateur
        $roles = $this->userRepository->getUserRoles($userId);

        // Vérifier si le rôle qui lui est associé est correct
        return in_array($role, $roles, true);
    }

    public function hasAnyRole(int $userId, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($userId, $role)) {
                return true;
            }
        }
        return false;
    }

    public function isPassenger(int $userId): bool
    {
        return $this->hasRole($userId, UserRoles::PASSAGER);
    }

    public function isDriver(int $userId): bool
    {
        return $this->hasRole($userId, UserRoles::CONDUCTEUR);
    }

    public function isCustomer(int $userId): bool
    {
        return $this->hasAnyRole($userId, [UserRoles::PASSAGER, UserRoles::CONDUCTEUR]);
    }

    public function isEmployee(int $userId): bool
    {
        return $this->hasRole($userId, UserRoles::EMPLOYE);
    }

    public function isAdmin(int $userId): bool
    {
        return $this->hasRole($userId, UserRoles::ADMIN);
    }

    public function isStaff(int $userId): bool
    {
        return $this->hasAnyRole($userId, [UserRoles::EMPLOYE, UserRoles::ADMIN]);
    }

    public function isUser(int $userId): bool
    {
        return $this->hasAnyRole($userId, [UserRoles::PASSAGER, UserRoles::CONDUCTEUR, UserRoles::EMPLOYE, UserRoles::ADMIN]);
    }


    public function ensurePassenger(int $userId)
    {
        if (!$this->isPassenger($userId)) {
            throw new InvalidArgumentException("L'utilisateur n'a pas le rôle PASSAGER.");
        }
    }

    public function ensureDriver(int $userId)
    {
        if (!$this->isDriver($userId)) {
            throw new InvalidArgumentException("L'utilisateur n'a pas le rôle CONDUCTEUR.");
        }
    }

    public function ensureCustomer(int $userId)
    {
        if (!$this->isCustomer($userId)) {
            throw new InvalidArgumentException("L'utilisateur n'a pas le rôle PASSAGER ou CONDUCTEUR.");
        }
    }

    public function ensureEmployee(int $userId)
    {
        if (!$this->isEmployee($userId)) {
            throw new InvalidArgumentException("L'utilisateur n'a pas le rôle EMPLOYE.");
        }
    }

    public function ensureAdmin(int $userId)
    {
        if (!$this->isAdmin($userId)) {
            throw new InvalidArgumentException("L'utilisateur n'a pas le rôle ADMIN.");
        }
    }

    public function ensureStaff(int $userId)
    {
        if (!$this->isStaff($userId)) {
            throw new InvalidArgumentException("L'utilisateur n'a pas le rôle EMPLOYE ou ADMIN.");
        }
    }

    public function ensurePassengerAndStaff(int $userId)
    {
        if (!$this->isPassenger($userId) && !$this->isStaff($userId)) {
            throw new InvalidArgumentException("L'utilisateur n'a pas le rôle PASSAGER ou EMPLOYE ou ADMIN.");
        }
    }

    public function ensureDriverAndStaff(int $userId)
    {
        if (!$this->isDriver($userId) && !$this->isStaff($userId)) {
            throw new InvalidArgumentException("L'utilisateur n'a pas le rôle CONDUCTEUR ou EMPLOYE ou ADMIN.");
        }
    }

    public function ensureUser(int $userId)
    {
        if (!$this->isUser($userId)) {
            throw new InvalidArgumentException("L'utilisateur n'a pas de rôle défini.");
        }
    }

    //---------------------Récupération simple---------------

    public function getUserById(int $userId): User
    {
        $this->checkIfUserExists($userId);
        return $this->userRepository->findUserById($userId);
    }
    //-------------------Action VISITEUR----------------------

    /**
     * Permet à un visiteur de créer un compte.
     *
     * @param CreateUserDTO $dto
     * @return User|null
     */
    public function createAccount(
        CreateUserDTO $dto
    ): User {
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
        if (!empty($dto->uriPicture)) {
            $user->setUserUriPicture($dto->uriPicture);
        }
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
    ): User {

        $this->checkIfUserExists($passengerId);
        $this->ensurePassenger($passengerId);

        // Récupération de l'objet User
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
        $this->checkIfUserExists($userId);
        $this->ensureCustomer($userId);

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
    ): User {

        $this->checkIfUserExists($userId);
        $this->ensureUser($userId);

        // Récupération de l'objet User
        $user = $this->userRepository->findUserById($userId);

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
        if (!empty($dto->zipCode)) {
            $user->setUserZipCode($dto->zipCode);
        }
        if (!empty($dto->uripicture)) {
            $user->setUserUriPicture($dto->uriPicture);
        }
        if (!empty($dto->licenceNo) && $this->isDriver($userId)) {
            $user->setUserLicenceNo($dto->licenceNo);
        }
        if (!empty($dto->credits)) {
            $user->setUserCredits($dto->credits);
        }
        if (!empty($dto->preferences)) {
            $user->setUserPreference($dto->preferences);
        }


        // Enregistrement dans la BD.
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

        $this->checkIfUserExists($userId);
        $this->ensureUser($userId);

        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

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
    ): User {
        $this->checkIfUserExists($adminId);
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
        int $userId,
        int $adminId
    ): bool {

        $this->checkIfUserExists($adminId);
        $this->ensureAdmin($adminId);

        $this->checkIfUserExists($userId);
        $this->ensureUser($userId);

        // Enregistrement en Bd
        $this->userRepository->deleteUser($userId);

        return true;
    }
}
