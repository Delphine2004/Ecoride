<?php

namespace App\Enum;

enum CarPower: string
{
    case DIESEL = "Diesel";
    case ESSENCE = "Essence";
    case ELECTRIQUE = "Electrique";
    case HYBRIDE = "Hybride";
    case GPL = "Gpl";
}
