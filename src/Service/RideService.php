<?php

namespace App\Service;

use App\Repositories\RideRepository;
use App\Enum\RideStatus;
use InvalidArgumentException;

class RideService
{

    private RideRepository $rideRepository;

    public function __construct(RideRepository $rideRepository)
    {
        $this->rideRepository = $rideRepository;
    }

    // créer un nouveau trajet

    // Modifier un trajet

    // Annuler un trajet 




}
