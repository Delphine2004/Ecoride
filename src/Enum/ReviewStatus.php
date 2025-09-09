<?php

namespace App\Enum;

enum ReviewStatus: string
{
    case ATTENTE = "en attente";
    case CONFIRME = "confirmé";
}
