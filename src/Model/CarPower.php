<?php

namespace App\Models;

enum CarPower: string
{
    case DIESEL = "diesel";
    case ESSENCE = "essence";
    case ELECTRIQUE = "electrique";
    case HYBRIDE = "hybride";
    case GPL = "gpl";
}
