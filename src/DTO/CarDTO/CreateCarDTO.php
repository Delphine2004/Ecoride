<?php

namespace App\DTO;

use App\Enum\CarBrand;
use App\Enum\CarColor;
use App\Enum\CarPower;
use DateTimeImmutable;
use DateTime;
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


        $model = trim(($data['car_model']));
        if (empty($model)) {
            throw new InvalidArgumentException("Le modéle est obligatoire.");
        }
        $this->model = $model;


        $color = CarColor::tryFrom($data['car_color'] ?? '');
        if ($color === null) {
            throw new InvalidArgumentException("Couleur invalide.");
        }
        $this->color = $color;


        $year = (int) ($data['car_year'] ?? 0);
        $currentYear = (int) date('Y');
        if ($year < 1970 || $currentYear) {
            throw new InvalidArgumentException("Année invalide.");
        }
        $this->year = $year;


        $power = CarPower::tryFrom($data['car_power'] ?? '');
        if ($power === null) {
            throw new InvalidArgumentException("Energie invalide.");
        }
        $this->power = $power;


        $seatsNumber = (int)($data['seats_number']);
        if ($seatsNumber > 0) {
            throw new InvalidArgumentException("Le nombre de place doit être supérieur à 0.");
        }
        $this->seatsNumber = $seatsNumber;


        $registrationNumber = strtoupper(trim(($data['registration_number'])) ?? '');
        if (empty($registrationNumber)) {
            throw new InvalidArgumentException("La plaque d'immatriculation est obligatoire.");
        }
        $this->registrationNumber = $registrationNumber;


        $registrationDate = ($data['registration_date']);
        $today = (new DateTime())->format('Y-m-d');
        if ($registrationDate < $today) {
            throw new InvalidArgumentException("La date doit être inférieure à aujourd'hui.");
        }
        $this->registrationDate = $registrationDate;
    }
}
