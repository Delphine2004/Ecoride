<?php

namespace App\Enum;

enum UserStatus: string
{
    case PASSAGER = "passager";
    case CONDUCTEUR = "conducteur";
    case EMPLOYE = "employer";
    case ADMIN = "admin";
}
