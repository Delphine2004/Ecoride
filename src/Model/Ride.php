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

        private ?int $driverId = null, // pour l'hydratation brute dans RideRepository
        private ?User $driver = null, // pour le mappingdans RideRepository

        private \DateTimeImmutable $departureDateTime,
        private string $departurePlace,
        private \DateTimeImmutable $arrivalDateTime,
        private string $arrivalPlace,
        private int $price,
        private int $availableSeats,
        private RideStatus $rideStatus = RideStatus::DISPONIBLE, // Statut par défaut
        private ?array $passengers = null, // Pour charger plusieurs passagers

        private ?\DateTimeImmutable $createdAt = null, // n'a pas de valeur au moment de l'instanciation
        private ?\DateTimeImmutable $updatedAt = null // n'a pas de valeur au moment de l'instanciation

    ) {
        // Affectation avec validation
        $this
            ->setRideDriver($driver)
            ->setRideDepartureDateTime($departureDateTime)
            ->setRideDeparturePlace($departurePlace)
            ->setRideArrivalDateTime($arrivalDateTime)
            ->setRideArrivalPlace($arrivalPlace)
            ->setRidePrice($price)
            ->setRideAvailableSeats($availableSeats)
            ->setRideStatus($rideStatus);

        $this->passengers = $passengers ?? [];
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    // ---------Les Getters ---------
    //Basiques
    public function getRideId(): ?int
    {
        return $this->rideId;
    }

    public function getRideDriverId(): ?int
    {
        return $this->driverId;
    }

    public function getRideDepartureDateTime(): \DateTimeImmutable
    {
        return $this->departureDateTime;
    }

    public function getRideDeparturePlace(): string
    {
        return $this->departurePlace;
    }

    public function getRideArrivalDateTime(): \DateTimeImmutable
    {
        return $this->arrivalDateTime;
    }

    public function getRideArrivalPlace(): string
    {
        return $this->arrivalPlace;
    }

    public function getRideDuration(): int
    {
        // Calcule de la différence en seconde
        $interval = $this->arrivalDateTime->getTimestamp() - $this->departureDateTime->getTimestamp();
        // Conversion en minutes
        return (int)($interval / 60);
    }

    public function getRidePrice(): int
    {
        return $this->price;
    }

    public function getRideAvailableSeats(): int
    {
        return $this->availableSeats;
    }

    public function getRideStatus(): RideStatus
    {
        return $this->rideStatus;
    }

    public function getRidePassengers(): ?array
    {
        return $this->passengers;
    }

    public function getRideCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRideUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Objets liés
    public function getRideDriver(): ?User
    {
        return $this->driver;
    }

    // ---------Les Setters ---------

    // Pas de setId, de setCreatedAtcar et de setUpadetedAt car définis automatiquement par la BD
    public function setRideDriver(User | array $driver): self
    {
        $this->driver = $driver;
        if ($driver instanceof User) {
            $this->driverId = $driver->getUserId(); // pour setter les ids si l'objet est null
        } elseif (is_array($driver) && isset($driver['user_id'])) {
            $this->driverId = $driver['user_id'];
        }

        $this->updateTimestamp();
        return $this;
    }

    public function setRideDepartureDateTime(\DateTimeImmutable $departureDateTime): self
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

    public function setRideDeparturePlace(string $departurePlace): self
    {
        // Vérifier si la valeur n'est pas vide et utiliser trim pour que la valeur ne soit pas considéré comme rempli avec un espace
        if (empty(trim($departurePlace))) {
            throw new InvalidArgumentException("La ville de départ ne peut pas être vide");
        }
        $this->departurePlace = trim($departurePlace);
        $this->updateTimestamp();
        return $this;
    }

    public function setRideArrivalDateTime(\DateTimeImmutable $arrivalDateTime): self
    {

        if (isset($this->departureDateTime) && $arrivalDateTime <= $this->departureDateTime) {
            throw new InvalidArgumentException("La date d'arrivée doit être supérieure à la date de départ.");
        }
        $this->arrivalDateTime = $arrivalDateTime;
        $this->updateTimestamp();
        $this->validateDuration();
        return $this;
    }

    public function setRideArrivalPlace(string $arrivalPlace): self
    {
        if (empty(trim($arrivalPlace))) {
            throw new InvalidArgumentException("La ville d'arrivée ne peut pas être vide");
        }
        $this->arrivalPlace = trim($arrivalPlace);
        $this->updateTimestamp();
        return $this;
    }

    public function setRidePrice(int $price): self
    {
        if ($price <= 0) {
            throw new InvalidArgumentException("Le prix doit être supérieure à 0.");
        }
        $this->price = $price;
        $this->updateTimestamp();
        return $this;
    }

    public function setRideAvailableSeats(int $availableSeats): self
    {
        if ($availableSeats <= 0) {
            throw new InvalidArgumentException("Le nombre de place disponible doit être supérieure à 0.");
        }
        $this->availableSeats = $availableSeats;
        $this->updateTimestamp();
        return $this;
    }

    public function decrementAvailableSeats(): void
    {
        if ($this->availableSeats <= 0) {
            throw new InvalidArgumentException("Il n'y a plus de place disponible.");
        }
        $this->availableSeats--;
    }

    public function setRideStatus(RideStatus $rideStatus): self
    {
        $this->rideStatus = $rideStatus;
        $this->updateTimestamp();
        return $this;
    }

    public function setRidePassengers(array $passengers): self
    {
        $this->passengers = $passengers;
        return $this;
    }

    public function addRidePassenger(User | array $passenger): self
    {
        if ($passenger instanceof User) {
            $this->passengers[] = $passenger;
        } elseif (is_array($passenger)) {
            $this->passengers[] = $passenger;
        }
        $this->updateTimestamp();
        return $this;
    }

    // ------ Validation interne de la durée -----
    private function validateDuration(): void
    {
        $duration = $this->getRideDuration();
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
