<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Enum\UserRoles;

class RoleService
{

    public function __construct(
        private UserRepository $userRepository,
    ) {}

    /**
     * Vérifie un rôle en particulier.
     *
     * @param integer $userId
     * @param string $roleName
     * @return boolean
     */
    public function hasRole(int $userId, string $roleName): bool
    {
        // Trouver les roles de l'utilisateur
        $roles = $this->userRepository->getUserRoles($userId);

        // Vérifier si le rôle qui lui est associé est correct
        return in_array($roleName, $roles, true);
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

    /**
     * Vérifie si l'utilisateur a le rôle PASSAGER.
     *
     * @param integer $userId
     * @return boolean
     */
    public function isPassenger(int $userId): bool
    {
        // Trouver les roles de l'utilisateur
        $roles = $this->userRepository->getUserRoles($userId);

        // Vérifier si le rôle qui lui est associé est correct
        return in_array(UserRoles::PASSAGER, $roles, true);
    }

    /**
     * Vérifie si l'utilisateur a le rôle CONDUCTEUR.
     *
     * @param integer $userId
     * @return boolean
     */
    public function isDriver(int $userId): bool
    {
        // Trouver les roles de l'utilisateur
        $roles = $this->userRepository->getUserRoles($userId);

        // Vérifier si le rôle qui lui est associé est correct
        return in_array(UserRoles::CONDUCTEUR, $roles, true);
    }

    /**
     * Vérifie si l'utilisateur a le rôle EMPLOYE.
     *
     * @param integer $userId
     * @return boolean
     */
    public function isEmployee(int $userId): bool
    {
        // Trouver les roles de l'utilisateur
        $roles = $this->userRepository->getUserRoles($userId);

        // Vérifier si le rôle qui lui est associé est correct
        return in_array(UserRoles::EMPLOYE, $roles, true);
    }

    /**
     * Vérifie si l'utilisateur a le rôle ADMIN.
     *
     * @param integer $userId
     * @return boolean
     */
    public function isAdmin(int $userId): bool
    {
        // Trouver les roles de l'utilisateur
        $roles = $this->userRepository->getUserRoles($userId);

        // Vérifier si le rôle qui lui est associé est correct
        return in_array(UserRoles::ADMIN, $roles, true);
    }
}
