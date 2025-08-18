<?php

namespace App\Enum;

enum CarPower: string
{
    case DIESEL = "diesel";
    case ESSENCE = "essence";
    case ELECTRIQUE = "electrique";
    case HYBRIDE = "hybride";
    case GPL = "gpl";
}
