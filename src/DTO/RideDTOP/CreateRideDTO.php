<?php

namespace App\DTO;

use App\Enum\RideStatus;
use DateTimeImmutable;
use InvalidArgumentException;

class CreateRideDTO
{
    public DateTimeImmutable $departureDateTime;
    public string $departurePlace;
    public DateTimeImmutable $arrivalDateTime;
    public string $arrivalPlace;
    public int $price;
    public int $availableSeats;
    public RideStatus $rideStatus;


    public function __construct(array $data)
    {
        $this->departureDateTime = new DateTimeImmutable($data['departure_date_time'] ?? '');
        if ($this->departureDateTime <= new DateTimeImmutable()) {
            throw new InvalidArgumentException("La date de départ doit être supérieure à aujourd'hui.");
        }

        $this->departurePlace = trim(($data['departure_place']) ?? '');
        if (empty($this->departurePlace)) {
            throw new InvalidArgumentException("La ville de départ est obligatoire.");
        }



        $this->arrivalDateTime = new DateTimeImmutable($data['arrival_date_time'] ?? '');
        if ($this->arrivalDateTime <= $this->departureDateTime) {
            throw new InvalidArgumentException("La date d'arrivée doit être après la date de départ.");
        }

        $this->arrivalPlace = trim(($data['arrival_place']) ?? '');
        if (empty($this->arrivalPlace)) {
            throw new InvalidArgumentException("La ville d'arrivée est obligatoire.");
        }



        $this->price = (int)($data['price'] ?? 0);
        if ($this->price < 0 && $this->price >= 100) {
            throw new InvalidArgumentException("Le prix doit être supérieure à 0 et inférieure à 100.");
        }

        $this->availableSeats = (int)($data['available_seats'] ?? 0);
        if ($this->availableSeats <= 0 && $this->availableSeats >= 7) {
            throw new InvalidArgumentException("Le nombre de place disponible doit être supérieure à 0 et inférieure à 7.");
        }



        $rideStatus = RideStatus::tryFrom($data['ride_status'] ?? '');
        if ($rideStatus === null) {
            throw new InvalidArgumentException("Statut invalide.");
        }
        $this->rideStatus = $rideStatus;
    }
}
