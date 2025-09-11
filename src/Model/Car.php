<?php

namespace App\Models;

use App\Models\User;
use App\Enum\CarBrand;
use App\Enum\CarColor;
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
        private ?int $ownerId = null, // pour l'hydratation brute 
        private ?User $owner = null, // // pour le mapping

        private ?CarBrand $brand = null,
        private ?string $model = null,
        private ?CarColor $color = null,
        private ?int $year = null,
        private ?CarPower $power = null,
        private ?int $seatsNumber = null,
        private ?string $registrationNumber = null,
        private ?DateTimeImmutable $registrationDate = null,

        private ?DateTimeImmutable $createdAt = null // n'a pas de valeur au moment de l'instanciation


    ) {

        // Affectation avec validation
        $this
            ->setCarOwnerId($ownerId)
            ->setCarBrand($brand)
            ->setCarModel($model)
            ->setCarColor($color)
            ->setCarYear($year)
            ->setCarPower($power)
            ->setCarSeatsNumber($seatsNumber)
            ->setCarRegistrationNumber($registrationNumber)
            ->setCarRegistrationDate($registrationDate);

        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    // ---------Les Getters ---------
    public function getCarId(): ?int
    {
        return $this->carId;
    }

    public function getCarOwnerId(): ?int
    {
        return $this->ownerId;
    }

    public function getCarOwner(): ?User
    {
        return $this->owner;
    }

    public function getCarBrand(): ?CarBrand
    {
        return $this->brand;
    }

    public function getCarModel(): ?string
    {
        return $this->model;
    }

    public function getCarColor(): ?CarColor
    {
        return $this->color;
    }

    public function getCarYear(): ?int
    {
        return $this->year;
    }

    public function getCarPower(): ?CarPower
    {
        return $this->power;
    }

    public function getCarSeatsNumber(): ?int
    {
        return $this->seatsNumber;
    }

    public function getCarRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function getCarRegistrationDate(): ?DateTimeImmutable
    {
        return $this->registrationDate;
    }

    public function getCarCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }



    // ---------Les Setters ---------

    public function setCarOwnerId(?int $ownerId): self
    {
        $this->ownerId = $ownerId;
        return $this;
    }


    public function setCarOwner(?User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function setCarBrand(?CarBrand $brand): self
    {
        $this->brand = $brand;
        return $this;
    }

    public function setCarModel(?string $model): self
    {
        $model = trim($model);

        if (empty($model)) {
            throw new InvalidArgumentException("Le modéle est obligatoire.");
        }

        $regexTextOnly = '/^[a-zA-ZÀ-ÿ\s\'-]{4,20}$/u';
        if (!preg_match($regexTextOnly, $model)) {
            throw new InvalidArgumentException("Le modéle doit être compris entre 4 et 20 caractères autorisés.");
        }

        $this->model = $model;
        return $this;
    }

    public function setCarColor(?CarColor $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function setCarYear(int $year): self
    {
        $currentYear = (int)date('Y');
        if ($year < 1900 || $year > $currentYear) {
            throw new InvalidArgumentException("Année invalide.");
        }

        $this->year = $year;
        return $this;
    }

    public function setCarPower(?CarPower $power): self
    {
        $this->power = $power;
        return $this;
    }

    public function setCarSeatsNumber(?int $seatsNumber): self
    {
        if ($seatsNumber < 0 || $seatsNumber > 7) {
            throw new InvalidArgumentException("Le nombre de siége doit compris entre 1 et 6.");
        }

        $this->seatsNumber = $seatsNumber;
        return $this;
    }

    public function setCarRegistrationNumber(?string $registrationNumber): self
    {
        $registrationNumber = trim($registrationNumber);

        if (empty($registrationNumber)) {
            throw new InvalidArgumentException("La plaque d'immatriculation est obligatoire.");
        }

        $oldFormat = '/^[1-9]\d{0,3}\s?[A-Z]{1,3}\s?(?:0[1-9]|[1-8]\d|9[0-5]|2[AB])$/';
        $newFormat = '/^[A-Z]{2}-\d{3}-[A-Z]{2}$/';

        // La plaque d'immatriculation doit correspondre à l'un ou l'autre format
        if (!preg_match($newFormat, strtoupper($registrationNumber)) && !preg_match($oldFormat, strtoupper($registrationNumber))) {
            throw new InvalidArgumentException("Le format de la plaque d'immatriculation est invalide.");
        }

        $this->registrationNumber = strtoupper($registrationNumber);
        return $this;
    }

    public function setCarRegistrationDate(?DateTimeImmutable $registrationDate): self
    {
        $minDate = new DateTimeImmutable('1970-01-01');
        if ($registrationDate < $minDate) {
            throw new InvalidArgumentException("La date d'immatriculation est trop ancienne.");
        }

        $this->registrationDate = $registrationDate;
        return $this;
    }
}
