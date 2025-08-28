<?php

// Finie mais à vérifier

namespace App\Models;


use App\Models\User;
use App\Enum\RideStatus;
use InvalidArgumentException;
use DateTimeImmutable;


//Pas besoin de base model dans une entité

/**
 * Cette classe représente un trajet dans la BDD.
 * Elle contient seulement la validation des données.
 */


class Ride
{

    // Promotion des propriétés (depuis PHP 8)
    public function __construct(
        private ?int $rideId = null, // n'a pas de valeur au moment de l'instanciation
        private User $driver,
        private \DateTimeImmutable $departureDateTime,
        private string $departurePlace,
        private \DateTimeImmutable $arrivalDateTime,
        private string $arrivalPlace,
        private int $price,
        private int $availableSeats,
        private RideStatus $status,


        private ?\DateTimeImmutable $createdAt = null, // n'a pas de valeur au moment de l'instanciation
        private ?\DateTimeImmutable $updatedAt = null // n'a pas de valeur au moment de l'instanciation

    ) {
        // Affectation avec validation
        $this->setDepartureDateTime($departureDateTime)
            ->setDeparturePlace($departurePlace)
            ->setArrivalDateTime($arrivalDateTime)
            ->setArrivalPlace($arrivalPlace)
            ->setPrice($price)
            ->setAvailableSeats($availableSeats)
            ->setStatus($status)
            ->setDriver($driver);

        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    // ---------Les Getters ---------
    public function getRideId(): ?int
    {
        return $this->rideId;
    }

    public function getDepartureDateTime(): \DateTimeImmutable
    {
        return $this->departureDateTime;
    }

    public function getDeparturePlace(): string
    {
        return $this->departurePlace;
    }

    public function getArrivalDateTime(): \DateTimeImmutable
    {
        return $this->arrivalDateTime;
    }

    public function getArrivalPlace(): string
    {
        return $this->arrivalPlace;
    }

    public function getDuration(): int
    {
        // Calcule de la différence en seconde
        $interval = $this->arrivalDateTime->getTimestamp() - $this->departureDateTime->getTimestamp();
        // Conversion en minutes
        return (int)($interval / 60);
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

    public function getDriver(): User
    {
        return $this->driver;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }



    // ---------Les Setters ---------

    // Pas de setId, de setCreatedAtcar et de setUpadetedAt car définis automatiquement par la BD
    public function setDepartureDateTime(\DateTimeImmutable $departureDateTime): self
    {
        $today = new \DateTimeImmutable();
        $nextYear = (clone $today)->modify('+1 year');
        if ($departureDateTime <= $today || $departureDateTime >= $nextYear) {
            throw new InvalidArgumentException("La date de départ doit être supérieure à la date du jour et ne pas être supérieure à un an.");
        }

        $this->departureDateTime = $departureDateTime;


        if (isset($this->arrivalDateTime)) {
            $this->updateTimestamp();
            $this->validateDuration();
        }
        return $this;
    }

    public function setDeparturePlace(string $departurePlace): self
    {
        // Vérifier si la valeur n'est pas vide et utiliser trim pour que la valeur ne soit pas considéré comme rempli avec un espace
        if (empty(trim($departurePlace))) {
            throw new InvalidArgumentException("La ville de départ ne peut pas être vide");
        }
        $this->departurePlace = trim($departurePlace);
        $this->updateTimestamp();
        return $this;
    }

    public function setArrivalDateTime(\DateTimeImmutable $arrivalDateTime): self
    {

        if (isset($this->departureDateTime) && $arrivalDateTime <= $this->departureDateTime) {
            throw new InvalidArgumentException("La date d'arrivée doit être supérieure à la date de départ.");
        }
        $this->arrivalDateTime = $arrivalDateTime;
        $this->updateTimestamp();
        $this->validateDuration();
        return $this;
    }

    public function setArrivalPlace(string $arrivalPlace): self
    {
        if (empty(trim($arrivalPlace))) {
            throw new InvalidArgumentException("La ville d'arrivée ne peut pas être vide");
        }
        $this->arrivalPlace = trim($arrivalPlace);
        $this->updateTimestamp();
        return $this;
    }

    public function setPrice(int $price): self
    {
        if ($price <= 0) {
            throw new InvalidArgumentException("Le prix doit être supérieure à 0.");
        }
        $this->price = $price;
        $this->updateTimestamp();
        return $this;
    }

    public function setAvailableSeats(int $availableSeats): self
    {
        if ($availableSeats <= 0) {
            throw new InvalidArgumentException("Le nombre de place disponible doit être supérieure à 0.");
        }
        $this->availableSeats = $availableSeats;
        $this->updateTimestamp();
        return $this;
    }

    public function setStatus(RideStatus $status): self
    {
        $this->status = $status;
        $this->updateTimestamp();
        return $this;
    }

    public function setDriver(User $driver): self
    {
        $this->driver = $driver;
        $this->updateTimestamp();
        return $this;
    }

    // ------ Validation interne de la durée -----
    private function validateDuration(): void
    {
        $duration = $this->getDuration();
        if ($duration <= 0) {
            throw new InvalidArgumentException("La durée du trajet doit être supérieure à 0.");
        }
    }

    // ---- Mise à jour de la date de modification
    private function updateTimestamp(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
