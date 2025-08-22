<?php

// Finie mais à vérifier

namespace App\Models;


use App\Models\User;
use App\Enum\RideStatus;
use InvalidArgumentException;
use DateTime;

/**
 * Cette classe représente un trajet dans la BDD.
 * Elle contient seulement la validation des données.
 */


class Ride
{

    // déclaration des propriétés façon moderne
    public function __construct(
        private ?int $id = null,
        private \DateTime $departureDateTime,
        private string $departurePlace,
        private \DateTime $arrivalDateTime,
        private string $arrivalPlace,
        private int $duration,
        private int $price,
        private int $availableSeats,
        private RideStatus $status,
        private ?string $createdAt, // elle n'a pas de valeur au moment de l'instanciation
        private ?string $updatedAt, // elle n'a pas de valeur au moment de l'instanciation
        private User $driver
    ) {
        // Affectation avec valisation
        $this->setDepartureDateTime($departureDateTime)
            ->setDeparturePlace($departurePlace)
            ->setArrivalDateTime($arrivalDateTime)
            ->setArrivalPlace($arrivalPlace)
            ->setDuration($duration)
            ->setPrice($price)
            ->setAvailableSeats($availableSeats)
            ->setStatus($status)
            ->setDriver($driver);
    }

    // ---------Les Getters ---------
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepartureDateTime(): \DateTime
    {
        return $this->departureDateTime;
    }

    public function getDeparturePlace(): string
    {
        return $this->departurePlace;
    }

    public function getArrivalDateTime(): \DateTime
    {
        return $this->arrivalDateTime;
    }

    public function getArrivalPlace(): string
    {
        return $this->arrivalPlace;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getAvailableSeats(): int
    {
        return $this->availableSeats;
    }

    public function getStatus(): RideStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function getDriver(): User
    {
        return $this->driver;
    }


    // ---------Les Setters ---------

    // Pas de setId car définit automatiquement par la BD
    public function setDepartureDateTime(DateTime $departureDateTime): self
    {
        $today = new DateTime();
        $nextYear = new DateTime()->modify('+1 year');
        if ($departureDateTime <= $today || $departureDateTime >= $nextYear) {
            throw new InvalidArgumentException("La date de départ doit être supérieure à la date du jour et ne pas être supérieure à un an.");
        }

        $this->departureDateTime = $departureDateTime;

        return $this;
    }

    public function setDeparturePlace(string $departurePlace): self
    {
        if (empty(trim($departurePlace))) {
            throw new InvalidArgumentException("La ville de départ ne peut pas être vide");
        }
        $this->departurePlace = trim($departurePlace);
        return $this;
    }

    public function setArrivalDateTime(DateTime $arrivalDateTime): self
    {
        if ($arrivalDateTime <= $this->departureDateTime) {
            throw new InvalidArgumentException("La date d'arrivée doit être supérieure à la date de départ.");
        }
        $this->arrivalDateTime = $arrivalDateTime;
        return $this;
    }

    public function setArrivalPlace(string $arrivalPlace): self
    {
        if (empty(trim($arrivalPlace))) {
            throw new InvalidArgumentException("La ville d'arrivée ne peut pas être vide");
        }
        $this->arrivalPlace = trim($arrivalPlace);
        return $this;
    }

    public function setDuration(int $duration): self
    {
        if ($duration <= 0) {
            throw new InvalidArgumentException("La durée du trajet doit être supérieure à 0.");
        }
        $this->duration = $duration;

        return $this;
    }

    public function setPrice(int $price): self
    {
        if ($price <= 0) {
            throw new InvalidArgumentException("Le prix doit être supérieure à 0.");
        }
        $this->price = $price;

        return $this;
    }

    public function setAvailableSeats(int $availableSeats): self
    {
        if ($availableSeats <= 0) {
            throw new InvalidArgumentException("Le nombre de place disponible doit être supérieure à 0.");
        }
        $this->availableSeats = $availableSeats;

        return $this;
    }

    public function setStatus(RideStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setDriver(User $driver): self
    {
        $this->driver = $driver;
        return $this;
    }
}
