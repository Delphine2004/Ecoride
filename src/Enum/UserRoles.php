<?php

namespace App\Enum;

enum UserRoles: string
{
    case PASSAGER = "Passager";
    case CONDUCTEUR = "Conducteur";
    case EMPLOYE = "Employer";
    case ADMIN = "Admin";
}
