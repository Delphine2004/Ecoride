<?php

namespace App\Service;

use App\Service\RoleService;
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
     * Vérifie que l'utilisateur a les rôles PASSAGER ou CONDUCTEUR.
     *
     * @param integer $userId
     * @return void
     */
    protected function ensureCustomer(int $userId): void
    {
        if (!$this->roleService->isCustomer($userId)) {
            throw new InvalidArgumentException("Seulement les utilisateurs ayant le role PASSAGER ou CONDUCTEUR peuvent effectuer cette action.");
        }
    }
    /**
     * Vérifie que l'utilisateur a le rôle EMPLOYE.
     *
     * @param integer $userId
     * @return void
     */
    protected function ensureEmployee(int $userId): void
    {
        if (!$this->roleService->isEmployee($userId)) {
            throw new InvalidArgumentException("Seulement les utilisateurs ayant le rôle EMPLOYE peuvent effectuer cette action.");
        }
    }

    /**
     * Vérifie que l'utilisateur a bien le rôle ADMIN.
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
     * Vérifie que l'utilisateur a les rôles EMPLOYE ou ADMIN.
     *
     * @param integer $userId
     * @return void
     */
    protected function ensureStaff(int $userId): void
    {
        if (!$this->roleService->isStaff($userId)) {
            throw new InvalidArgumentException("Seulement les utilisateurs ayant le rôle EMPLOYE ou ADMIN peuvent effectuer cette action.");
        }
    }

    /**
     * Vérifie que l'utilisateur a bien un rôle .
     *
     * @param integer $userId
     * @return void
     */
    protected function ensureUser(int $userId): void
    {
        if (!$this->roleService->isUser($userId)) {
            throw new InvalidArgumentException("Seulement les utilisateurs ayant un rôle peuvent effectuer cette action.");
        }
    }
}
