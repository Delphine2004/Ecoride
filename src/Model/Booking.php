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
        private BookingStatus $bookingStatus,

        private ?\DateTimeImmutable $createdAt = null, // n'a pas de valeur au moment de l'instanciation
        private ?\DateTimeImmutable $updatedAt = null // n'a pas de valeur au moment de l'instanciation

    ) {

        if ($passenger->getUserId() === $driver->getUserId()) {
            throw new InvalidArgumentException("Le chauffeur ne peut pas être passager de son propre trajet.");
        }

        if ($ride->getRideDriver()->getUserId() === $passenger->getUserId()) {
            throw new InvalidArgumentException("Un passager ne peut pas réserver son propre trajet.");
        }

        // Affectation avec validation
        $this->setBookingRide($ride)
            ->setBookingPassenger($passenger)
            ->setBookingDriver($driver)
            ->setBookingStatus($bookingStatus);

        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    // ---------Les Getters ---------
    public function getBookingId(): ?int
    {
        return $this->bookingId;
    }

    public function getBookingRide(): Ride
    {
        return $this->ride;
    }

    public function getBookingPassenger(): User
    {
        return $this->passenger;
    }

    public function getBookingDriver(): User
    {
        return $this->driver;
    }

    public function getBookingStatus(): BookingStatus
    {
        return $this->bookingStatus;
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

    public function setBookingRide(Ride $ride): self
    {
        if ($this->passenger->getUserId() === $ride->getRideDriver()->getUserId()) {
            throw new InvalidArgumentException("Un passager ne peut pas réserver son propre trajet.");
        }
        $this->ride = $ride;
        $this->updateTimestamp();
        return $this;
    }

    public function setBookingPassenger(User $passenger): self
    {
        if ($this->driver !== null && $passenger->getUserId() === $this->driver->getUserId()) {
            throw new InvalidArgumentException("Le chauffeur ne peut pas être passager de son propre trajet.");
        }

        if ($this->ride !== null && $this->ride->getRideDriver()->getUserId() === $passenger->getUserId()) {
            throw new InvalidArgumentException("Un passager ne peut pas réserver son propre trajet.");
        }
        $this->passenger = $passenger;
        $this->updateTimestamp();
        return $this;
    }

    public function setBookingDriver(User $driver): self
    {
        if ($this->passenger !== null && $driver->getUserId() === $this->passenger->getUserId()) {
            throw new InvalidArgumentException("Le chauffeur ne peut pas être passager de son propre trajet.");
        }

        $this->driver = $driver;
        $this->updateTimestamp();
        return $this;
    }

    public function setBookingStatus(BookingStatus $bookingStatus): self
    {
        $this->bookingStatus = $bookingStatus;
        $this->updateTimestamp();
        return $this;
    }



    // ---- Mise à jour de la date de modification
    private function updateTimestamp(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
