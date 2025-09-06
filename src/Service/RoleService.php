<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Enum\UserRoles;

class RoleService
{

    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function hasRole(int $userId, string $roleName): bool
    {
        // Trouver les roles de l'utilisateur
        $roles = $this->userRepository->getUserRoles($userId);

        // Vérifier si le rôle qui lui est associé est correct
        return in_array($roleName, $roles, true);
    }

    // Vérifie si l'utilisateur a le rôle PASSAGER.
    public function isPassenger(int $userId): bool
    {
        // Trouver les roles de l'utilisateur
        $roles = $this->userRepository->getUserRoles($userId);

        // Vérifier si le rôle qui lui est associé est correct
        return in_array(UserRoles::PASSAGER, $roles, true);
    }

    // Vérifie si l'utilisateur a le rôle CONDUCTEUR.
    public function isDriver(int $userId): bool
    {
        // Trouver les roles de l'utilisateur
        $roles = $this->userRepository->getUserRoles($userId);

        // Vérifier si le rôle qui lui est associé est correct
        return in_array(UserRoles::CONDUCTEUR, $roles, true);
    }

    // Vérifie si l'utilisateur a le rôle EMPLOYE
    public function isEmployee(int $userId): bool
    {
        // Trouver les roles de l'utilisateur
        $roles = $this->userRepository->getUserRoles($userId);

        // Vérifier si le rôle qui lui est associé est correct
        return in_array(UserRoles::EMPLOYE, $roles, true);
    }

    // Vérifie si l'utilisateur a le rôle ADMIN
    public function isAdmin(int $userId): bool
    {
        // Trouver les roles de l'utilisateur
        $roles = $this->userRepository->getUserRoles($userId);

        // Vérifier si le rôle qui lui est associé est correct
        return in_array(UserRoles::ADMIN, $roles, true);
    }
}
