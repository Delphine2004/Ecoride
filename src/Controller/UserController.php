<?php

namespace App\Controllers;

use App\DTO\CreateUserDTO;
use App\Services\UserService;
use InvalidArgumentException;

class UserController extends BaseController
{
    public function __construct(
        private UserService $userService
    ) {}

    // POST
    public function createUser() {}

    public function createStaff(string $jwtToken) {}

    // PUT

    public function updateProfile(string $jwtToken) {}

    public function modifyPassword(string $jwtToken) {}

    public function becomeDriver(string $jwtToken) {}

    // DELETE
    // Voir comment conserver l'historique
    public function deleteUser(string $jwtToken) {}

    public function deleteUserByAdmin(string $jwtToken) {}

    // GET
    // voir si il faut pas supprimer getCarByUser
    public function getUserWithCars(string $jwtToken) {}

    public function getUserWithRides(string $jwtToken) {}

    public function getUserWithBookings(string $jwtToken) {}
}
