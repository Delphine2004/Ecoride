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


    public function isPassenger(int $userId): bool
    {
        return $this->hasRole($userId, 'PASSAGER');
    }


    public function isDriver(int $userId): bool
    {
        return $this->hasRole($userId, 'CONDUCTEUR');
    }


    public function isCustomer(int $userId): bool
    {
        return $this->hasAnyRole($userId, ['PASSAGER', 'CONDUCTEUR']);
    }


    public function isEmployee(int $userId): bool
    {
        return $this->hasRole($userId, 'EMPLOYE');
    }


    public function isAdmin(int $userId): bool
    {
        return $this->hasRole($userId, 'ADMIN');
    }

    public function isStaff(int $userId): bool
    {
        return $this->hasAnyRole($userId, ['EMPLOYE', 'ADMIN']);
    }


    public function isUser(int $userId): bool
    {
        return $this->hasAnyRole($userId, ['PASSAGER', 'CONDUCTEUR', 'EMPLOYE', 'ADMIN']);
    }
}
