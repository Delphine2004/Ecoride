<?php

namespace App\Model;

use App\Model\User;
use App\Enum\RideStatus;
use App\Utils\RegexPatterns;
use InvalidArgumentException;
use DateTimeImmutable;


/**
 * Cette classe représente un trajet dans la BDD.
 * Elle contient seulement la validation des données.
 */


class Ride
{

    public function __construct(
        public ?int $rideId = null, // n'a pas de valeur au moment de l'instanciation

        public ?int $driverId = null, // pour l'hydratation brute
        public ?User $driver = null, // pour le mapping

        public ?DateTimeImmutable $departureDateTime = null,
        public ?string $departurePlace = null,
        public ?DateTimeImmutable $arrivalDateTime = null,
        public ?string $arrivalPlace = null,
        public ?float $price = null,
        public ?int $availableSeats = null,
        public ?RideStatus $rideStatus = null,
        public ?int $commission = 2,

        public ?array $passengers = [], // Pour charger plusieurs passagers


        public ?DateTimeImmutable $createdAt = null, // n'a pas de valeur au moment de l'instanciation
        public ?DateTimeImmutable $updatedAt = null // n'a pas de valeur au moment de l'instanciation

    ) {
        // Affectation avec validation
        $this
            ->setRideDriverId($driverId)
            ->setRideDepartureDateTime($departureDateTime)
            ->setRideDeparturePlace($departurePlace)
            ->setRideArrivalDateTime($arrivalDateTime)
            ->setRideArrivalPlace($arrivalPlace)
            ->setRidePrice($price)
            ->setRideAvailableSeats($availableSeats)
            ->setRideStatus($rideStatus)
            ->setRideCommission($commission);

        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    public static function fromDatabaseRow(array $row): self
    {
        $rideId = $row['ride_id'] ?? null;
        $driverId = $row['driver_id'] ?? null;
        $departureDateTime = $row['departure_date_time'] ? new DateTimeImmutable($row['departure_date_time']) : null;
        $departurePlace = $row['departure_place'] ?? null;
        $arrivalDateTime = null;
        if (!empty($row['arrival_date_time']) && $row['arrival_date_time'] !== '0000-00-00 00:00:00') {
            $arrivalDateTime = new DateTimeImmutable($row['arrival_date_time']);
        }
        $arrivalPlace = $row['arrival_place'] ?? null;
        $price = (float) ($row['price'] ?? 0);
        $availableSeats = $row['available_seats'] ?? null;
        $rideStatus = $row['ride_status'] ? \App\Enum\RideStatus::from($row['ride_status']) : null;
        $commission = $row['commission'] ?? null;
        $createdAt = $row['created_at'] ? new DateTimeImmutable($row['created_at']) : null;
        $updatedAt = $row['updated_at'] ? new DateTimeImmutable($row['updated_at']) : null;

        return new self(
            rideId: $rideId,
            driverId: $driverId,
            departureDateTime: $departureDateTime,
            departurePlace: $departurePlace,
            arrivalDateTime: $arrivalDateTime,
            arrivalPlace: $arrivalPlace,
            price: $price,
            availableSeats: $availableSeats,
            rideStatus: $rideStatus,
            commission: $commission,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }


    // ---------Les Getters ---------

    public function getRideId(): ?int
    {
        return $this->rideId;
    }

    public function getRideDriverId(): ?int
    {
        return $this->driverId;
    }

    public function getRideDriver(): ?User
    {
        return $this->driver;
    }

    public function getRideDepartureDateTime(): ?DateTimeImmutable
    {
        return $this->departureDateTime;
    }

    public function getRideDeparturePlace(): ?string
    {
        return $this->departurePlace;
    }

    public function getRideArrivalDateTime(): ?DateTimeImmutable
    {
        return $this->arrivalDateTime;
    }

    public function getRideArrivalPlace(): ?string
    {
        return $this->arrivalPlace;
    }

    public function getRideDuration(): ?int
    {
        // Calcule de la différence en seconde
        $interval = $this->arrivalDateTime->getTimestamp() - $this->departureDateTime->getTimestamp();
        // Conversion en minutes
        return (int)($interval / 60);
    }

    public function getRidePrice(): ?float
    {
        return $this->price;
    }

    public function getRideAvailableSeats(): ?int
    {
        return $this->availableSeats;
    }

    public function getRideStatus(): ?RideStatus
    {
        return $this->rideStatus;
    }

    public function getRideCommission(): ?int
    {
        return $this->commission;
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


    // ---------Les Setters ---------

    public function setRideDriverId(?int $driverId): self
    {
        $this->driverId = $driverId;
        $this->updateTimestamp();
        return $this;
    }

    public function setRideDriver(?User $driver): self
    {
        $this->driver = $driver;
        $this->updateTimestamp();
        return $this;
    }


    public function setRideDepartureDateTime(?DateTimeImmutable $departureDateTime): self
    {
        $today = new DateTimeImmutable();
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

    public function setRideDeparturePlace(?string $departurePlace): self
    {
        $departurePlace = trim($departurePlace);

        if (empty($departurePlace)) {
            throw new InvalidArgumentException("La ville de départ est obligatoire.");
        }


        if (!preg_match(RegexPatterns::ONLY_TEXTE_REGEX, $departurePlace)) {
            throw new InvalidArgumentException("Le nom de la ville doit être compris entre 4 et 20 caractères autorisés.");
        }

        $this->departurePlace = strtoupper($departurePlace);
        $this->updateTimestamp();
        return $this;
    }

    public function setRideArrivalDateTime(?DateTimeImmutable $arrivalDateTime): self
    {

        if ($arrivalDateTime !== null && isset($this->departureDateTime) && $arrivalDateTime <= $this->departureDateTime) {
            throw new InvalidArgumentException("La date d'arrivée doit être supérieure à la date de départ.");
        }

        $this->arrivalDateTime = $arrivalDateTime;
        $this->updateTimestamp();


        if ($arrivalDateTime !== null) {
            $this->validateDuration();
        }

        $this->updateTimestamp();
        $this->validateDuration();
        return $this;
    }

    public function setRideArrivalPlace(?string $arrivalPlace): self
    {
        $arrivalPlace = trim($arrivalPlace);

        if (empty(trim($arrivalPlace))) {
            throw new InvalidArgumentException("La ville d'arrivée est obligatoire.");
        }


        if (!preg_match(RegexPatterns::ONLY_TEXTE_REGEX, $arrivalPlace)) {
            throw new InvalidArgumentException("Le nom de la ville doit être compris entre 4 et 20 caractères autorisés.");
        }

        $this->arrivalPlace = strtoupper($arrivalPlace);
        $this->updateTimestamp();
        return $this;
    }

    public function setRidePrice(?float $price): self
    {
        if ($price < 0 || $price >= 100) {
            throw new InvalidArgumentException("Le prix doit être supérieure à 0 et inférieure à 100.");
        }
        $this->price = $price;
        $this->updateTimestamp();
        return $this;
    }

    public function setRideAvailableSeats(?int $availableSeats): self
    {
        if ($availableSeats < 0 || $availableSeats >= 7) {
            throw new InvalidArgumentException("Le nombre de place disponible doit être supérieure à 0 et inférieure à 7.");
        }
        $this->availableSeats = $availableSeats;
        $this->updateTimestamp();
        return $this;
    }

    public function setRideStatus(?RideStatus $rideStatus): self
    {
        $this->rideStatus = $rideStatus;
        $this->updateTimestamp();
        return $this;
    }

    public function setRideCommission(?int $commission): self
    {
        $this->commission = $commission;
        $this->updateTimestamp();
        return $this;
    }

    public function setRidePassengers(?array $passengers): self
    {
        $this->passengers = $passengers;
        $this->updateTimestamp();
        return $this;
    }


    public function addRidePassenger(User $passenger): void
    {
        $this->passengers[] = $passenger;
    }

    //-------Mise à jour du nombre de place ------

    // Vérifie que le trajet a encore des places disponibles.
    public function hasAvailableSeat(): bool
    {
        return $this->availableSeats > 0;
    }


    public function decrementAvailableSeats(): void
    {
        if ($this->availableSeats <= 0) {
            throw new InvalidArgumentException("Il n'y a plus de place disponible.");
        }
        $this->availableSeats--;
    }

    public function incrementAvailableSeats(): void
    {
        $this->availableSeats++;
    }


    // ------ Validation interne de la durée -----
    private function validateDuration(): void
    {
        $arrival = $this->getRideArrivalDateTime();
        $departure = $this->getRideDepartureDateTime();

        if ($arrival !== null && $arrival <= $departure) {
            throw new InvalidArgumentException("La durée du trajet doit être supérieure à 0.");
        }
    }

    // ---- Mise à jour de la date de modification
    private function updateTimestamp(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
