<?php

namespace App\Models;


use App\Models\Ride;
use App\Models\User;
use App\Enum\BookingStatus;
use InvalidArgumentException;
use DateTimeImmutable;

class Booking
{
    // Promotion des propriétés (depuis PHP 8)
    public function __construct(
        private ?int $bookingId = null, // n'a pas de valeur au moment de l'instanciation
        private Ride $ride,
        private User $passenger,
        private User $driver,
        private BookingStatus $status,

        private ?\DateTimeImmutable $createdAt = null, // n'a pas de valeur au moment de l'instanciation
        private ?\DateTimeImmutable $updatedAt = null // n'a pas de valeur au moment de l'instanciation

    ) {

        if ($passenger->getUserId() === $driver->getUserId()) {
            throw new InvalidArgumentException("Le chauffeur ne peut pas être passager de son propre trajet.");
        }

        if ($ride->getDriver()->getUserId() === $passenger->getUserId()) {
            throw new InvalidArgumentException("Un passager ne peut pas réserver son propre trajet.");
        }

        // Affectation avec validation
        $this->setRide($ride)
            ->setPassenger($passenger)
            ->setDriver($driver)
            ->setStatus($status);

        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    // ---------Les Getters ---------
    public function getId(): ?int
    {
        return $this->bookingId;
    }

    public function getRide(): Ride
    {
        return $this->ride;
    }

    public function getPassenger(): User
    {
        return $this->passenger;
    }

    public function getDriver(): User
    {
        return $this->driver;
    }

    public function getStatus(): BookingStatus
    {
        return $this->status;
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

    public function setRide(Ride $ride): self
    {
        if ($this->passenger->getUserId() === $ride->getDriver()->getUserId()) {
            throw new InvalidArgumentException("Un passager ne peut pas réserver son propre trajet.");
        }
        $this->ride = $ride;
        $this->updateTimestamp();
        return $this;
    }

    public function setPassenger(User $passenger): self
    {
        if ($this->driver !== null && $passenger->getUserId() === $this->driver->getUserId()) {
            throw new InvalidArgumentException("Le chauffeur ne peut pas être passager de son propre trajet.");
        }

        if ($this->ride !== null && $this->ride->getDriver()->getUserId() === $passenger->getUserId()) {
            throw new InvalidArgumentException("Un passager ne peut pas réserver son propre trajet.");
        }
        $this->passenger = $passenger;
        $this->updateTimestamp();
        return $this;
    }

    public function setDriver(User $driver): self
    {
        if ($this->passenger !== null && $driver->getUserId() === $this->passenger->getUserId()) {
            throw new InvalidArgumentException("Le chauffeur ne peut pas être passager de son propre trajet.");
        }

        $this->driver = $driver;
        $this->updateTimestamp();
        return $this;
    }

    public function setStatus(BookingStatus $status): self
    {
        $this->status = $status;
        $this->updateTimestamp();
        return $this;
    }



    // ---- Mise à jour de la date de modification
    private function updateTimestamp(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
