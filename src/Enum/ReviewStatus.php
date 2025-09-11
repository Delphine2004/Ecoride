<?php

namespace App\Enum;

enum ReviewStatus: string
{
    case ATTENTE = "En attente";
    case CONFIRME = "Confirmé";
    case REJETE = "Rejeté";
}
