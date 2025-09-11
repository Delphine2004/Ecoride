<?php

namespace App\DTO;

use App\Enum\CarBrand;
use App\Enum\CarColor;
use App\Enum\CarPower;
use DateTimeImmutable;
use InvalidArgumentException;

class CreateCarDTO
{
    public CarBrand $brand;
    public string $model;
    public CarColor $color;
    public int $year;
    public CarPower $power;
    public int $seatsNumber;
    public string $registrationNumber;
    public DateTimeImmutable $registrationDate;


    public function __construct(array $data)
    {
        $brand = CarBrand::tryFrom($data['car_brand'] ?? '');
        if ($brand === null) {
            throw new InvalidArgumentException("Marque invalide.");
        }
        $this->brand = $brand;


        $this->model = trim(($data['car_model']));
        if (empty($model)) {
            throw new InvalidArgumentException("Le modéle est obligatoire.");
        }


        $color = CarColor::tryFrom($data['car_color'] ?? '');
        if ($color === null) {
            throw new InvalidArgumentException("Couleur invalide.");
        }
        $this->color = $color;


        $this->year = (int) ($data['car_year'] ?? 0);
        $currentYear = (int) date('Y');
        if ($this->year < 1970 || $currentYear) {
            throw new InvalidArgumentException("Année invalide.");
        }


        $power = CarPower::tryFrom($data['car_power'] ?? '');
        if ($power === null) {
            throw new InvalidArgumentException("Energie invalide.");
        }
        $this->power = $power;


        $this->seatsNumber = (int)($data['seats_number'] ?? 0);
        if ($this->seatsNumber <= 0 && $this->seatsNumber >= 7) {
            throw new InvalidArgumentException("Le nombre de place doit être supérieure à 0 et inférieure à 7.");
        }


        $this->registrationNumber = strtoupper(trim(($data['registration_number'])) ?? '');
        if (empty($this->registrationNumber)) {
            throw new InvalidArgumentException("La plaque d'immatriculation est obligatoire.");
        }


        $this->registrationDate = new DateTimeImmutable($data['registration_date'] ?? '');
        $today = new DateTimeImmutable('today');
        if ($this->registrationDate < $today) {
            throw new InvalidArgumentException("La date doit être inférieure à aujourd'hui.");
        }
    }
}
