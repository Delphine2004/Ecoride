<?php

namespace App\Models;

enum RideStatus: string
{
    case DISPONIBLE = "disponible";
    case COMPLET = "complet";
    case ANNULE = "annulé";
    case PASSE = "passé";
}
