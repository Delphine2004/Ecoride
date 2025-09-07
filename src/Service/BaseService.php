<?php

namespace App\Services;

use App\Services\RoleService;
use InvalidArgumentException;

abstract class BaseService
{


    public function __construct(protected RoleService $roleService) {}


    /**
     * Vérifie que l'utilisateur a le rôle PASSAGER.
     *
     * @param integer $userId
     * @return void
     */
    protected function ensurePassenger(int $userId): void
    {
        if (!$this->roleService->isPassenger($userId)) {
            throw new InvalidArgumentException("Seulement les utilisateurs ayant le rôle PASSAGER peuvent effectuer cette action.");
        }
    }

    /**
     * Vérifie que l'utilisateur a le rôle CONDUCTEUR.
     *
     * @param integer $userId
     * @return void
     */
    protected function ensureDriver(int $userId): void
    {
        if (!$this->roleService->isDriver($userId)) {
            throw new InvalidArgumentException("Seulement les utilisateurs ayant le rôle CONDUCTEUR peuvent effectuer cette action.");
        }
    }

    /**
     * Vérifie que l'utilisateur a le rôle ADMIN.
     *
     * @param integer $userId
     * @return void
     */
    protected function ensureAdmin(int $userId): void
    {
        if (!$this->roleService->isAdmin($userId)) {
            throw new InvalidArgumentException("Seulement les utilisateurs ayant le rôle ADMIN peuvent effectuer cette action.");
        }
    }

    /**
     * Vérifie que l'utilisateur a le rôle EMPLOYEE.
     *
     * @param integer $userId
     * @return void
     */
    protected function ensureEmployee(int $userId): void
    {
        if (!$this->roleService->isEmployee($userId)) {
            throw new InvalidArgumentException("Seulement les utilisateurs ayant le rôle EMPLOYEE peuvent effectuer cette action.");
        }
    }
}
