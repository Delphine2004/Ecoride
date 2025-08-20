<?php

namespace App\Enum;

enum UserRoles: string
{
    case PASSAGER = "passager";
    case CONDUCTEUR = "conducteur";
    case EMPLOYE = "employer";
    case ADMIN = "admin";
}
