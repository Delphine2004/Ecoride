<?php

namespace App\Model;

use App\Model\User;
use App\Enum\CarBrand;
use App\Enum\CarColor;
use App\Enum\CarPower;
use App\Utils\RegexPatterns;
use InvalidArgumentException;
use DateTimeImmutable;


/** 
 * Cette classe représente une voiture dans la BDD.
 * Elle contient seulement la validation des données.
 */

class Car
{

    function __construct(
        public ?int $carId = null, // n'a pas de valeur au moment de l'instanciation
        public ?int $ownerId = null, // pour l'hydratation brute 
        public ?User $owner = null, // // pour le mapping

        public ?CarBrand $brand = null,
        public ?string $model = null,
        public ?CarColor $color = null,
        public ?int $year = null,
        public ?CarPower $power = null,
        public ?int $seatsNumber = null,
        public ?string $registrationNumber = null,
        public ?DateTimeImmutable $registrationDate = null,

        public ?DateTimeImmutable $createdAt = null // n'a pas de valeur au moment de l'instanciation


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


        if (!preg_match(RegexPatterns::ONLY_TEXTE_REGEX, $model)) {
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


        // La plaque d'immatriculation doit correspondre à l'un ou l'autre format
        if (!preg_match(RegexPatterns::OLD_REGISTRATION_NUMBER, strtoupper($registrationNumber)) && !preg_match(RegexPatterns::NEW_REGISTRATION_NUMBER, strtoupper($registrationNumber))) {
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
