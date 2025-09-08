<?php

namespace App\Enum;

enum RideStatus: string
{
    case DISPONIBLE = "disponible";
    case COMPLET = "complet";
    case ANNULE = "annulé";
    case ENCOURS = "en cours";
    case TERMINE = "terminé";
}
