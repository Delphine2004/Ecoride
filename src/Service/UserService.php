<?php

namespace App\Service;

use App\Repositories\UserRepository;
use App\Enum\UserRoles;
use InvalidArgumentException;

class UserService
{

    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    // Inscription 

    // création d'un compte employé

    //createUserFromForm() -- contient le hashage du mdp

    // Modification d'un champ

    // Modification du mot de passe

    // Ajout du rôle conducteur

    // Supprimer son compte

    // Supprimer un compte


}
