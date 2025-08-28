<?php

namespace App\Enum;

enum BookingStatus: string
{
    case CONFIRMEE = "confirmée";
    case ANNULEE = "annulée";
    case PASSEE = "passée";
}
