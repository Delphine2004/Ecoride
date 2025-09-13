<?php

namespace App\Enum;

enum RideStatus: string
{
    case DISPONIBLE = "Disponible";
    case COMPLET = "Complet";
    case ANNULE = "Annulé";
    case ENCOURS = "En cours";
    case ENATTENTE = "En attente";
    case TERMINE = "Terminé";
}
