<?php

namespace App\Enum;

enum BookingStatus: string
{
    case CONFIRMEE = "Confirmée";
    case ANNULEE = "Annulée";
    case PASSEE = "Passée";
}
