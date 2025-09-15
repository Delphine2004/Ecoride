<?php

namespace App\Enum;

enum BookingStatus: string
{
    case CONFIRMEE = "Confirmée";
    case ENCOURS = "En cours";
    case ANNULEE = "Annulée";
    case PASSEE = "Passée";
}
