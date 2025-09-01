<?php

// Finie mais à vérifier

namespace App\Models;

use App\Models\User;
use App\Enum\CarPower;
use InvalidArgumentException;
use DateTimeImmutable;


/** 
 * Cette classe représente une voiture dans la BDD.
 * Elle contient seulement la validation des données.
 */

class Car
{

    // Promotion des propriétés (depuis PHP 8)
    function __construct(
        private ?int $carId = null, // n'a pas de valeur au moment de l'instanciation
        private ?User $owner, // car pas chargé dans hydrateCar de CarRepository
        private string $brand,
        private string $model,
        private string $color,
        private int $year,
        private CarPower $power,
        private int $seatsNumber,
        private string $registrationNumber,
        private \DateTimeImmutable $registrationDate,

        private ?\DateTimeImmutable $createdAt = null // n'a pas de valeur au moment de l'instanciation

    ) {

        // Affectation avec validation
        $this->setBrand($brand)
            ->setCarOwner($owner)
            ->setModel($model)
            ->setColor($color)
            ->setYear($year)
            ->setPower($power)
            ->setSeatsNumber($seatsNumber)
            ->setRegistrationNumber($registrationNumber)
            ->setRegistrationDate($registrationDate);

        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    // ---------Les Getters ---------
    public function getCarId(): ?int
    {
        return $this->carId;
    }

    public function getCarOwner(): ?User
    {
        return $this->owner;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getPower(): CarPower
    {
        return $this->power;
    }

    public function getSeatsNumber(): int
    {
        return $this->seatsNumber;
    }

    public function getRegistrationNumber(): string
    {
        return $this->registrationNumber;
    }

    public function getRegistrationDate(): DateTimeImmutable
    {
        return $this->registrationDate;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }



    // ---------Les Setters ---------

    // Pas de setId et de setCreatedAt car définis automatiquement par la BD

    public function setCarOwner(?User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function setBrand(string $brand): self
    {
        // Vérifier si la valeur n'est pas vide et utiliser trim pour que la valeur ne soit pas considéré comme rempli avec un espace
        if (empty(trim($brand))) {
            throw new InvalidArgumentException("La marque ne peut pas être vide.");
        }
        $this->brand = trim($brand);

        return $this;
    }

    public function setModel(string $model): self
    {
        if (empty(trim($model))) {
            throw new InvalidArgumentException("Le modéle ne peut pas être vide.");
        }
        $this->model = trim($model);

        return $this;
    }

    public function setColor(string $color): self
    {
        if (empty(trim($color))) {
            throw new InvalidArgumentException("La couleur ne peut pas être vide.");
        }
        $this->color = trim($color);

        return $this;
    }

    public function setYear(int $year): self
    {
        $currentYear = (int)date('Y');
        if ($year < 1900 || $year > $currentYear) {
            throw new InvalidArgumentException("Année invalide.");
        }
        $this->year = $year;

        return $this;
    }

    public function setPower(CarPower $power): self
    {
        $this->power = $power;
        return $this;
    }

    public function setSeatsNumber(int $seatsNumber): self
    {
        if ($seatsNumber <= 0 || $seatsNumber > 7) {
            throw new InvalidArgumentException("Le nombre de siége ne peut pas être égale à 0 ou supérieure à 7");
        }
        $this->seatsNumber = $seatsNumber;

        return $this;
    }

    public function setRegistrationNumber(string $registrationNumber): self
    {
        if (empty(trim($registrationNumber))) {
            throw new InvalidArgumentException("La plaque d'immatriculation ne peut pas être vide.");
        }

        $oldFormat = '/^[1-9]\d{0,3}\s?[A-Z]{1,3}\s?(?:0[1-9]|[1-8]\d|9[0-5]|2[AB])$/';
        $newFormat = '/^[A-Z]{2}-\d{3}-[A-Z]{2}$/';

        // La plaque d'immatriculation doit correspondre à l'un ou l'autre format
        if (!preg_match($newFormat, strtoupper($registrationNumber)) && !preg_match($oldFormat, strtoupper($registrationNumber))) {
            throw new InvalidArgumentException("Le format de la plaque d'immatriculation est invalide.");
        }
        $this->registrationNumber = strtoupper(trim($registrationNumber));

        return $this;
    }

    public function setRegistrationDate(DateTimeImmutable $registrationDate): self
    {
        $minDate = new DateTimeImmutable('1970-01-01');
        if ($registrationDate < $minDate) {
            throw new InvalidArgumentException("La date d'immatriculation est trop ancienne.");
        }

        $this->registrationDate = $registrationDate;

        return $this;
    }
}
