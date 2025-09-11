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

        private ?int $rideId = null, // pour l'hydratation brute dans bookingRepository
        private ?int $passengerId = null, // pour l'hydratation brute dans bookingRepository
        private ?int $driverId = null, // pour l'hydratation brute dans bookingRepository

        private ?Ride $ride = null, // Pour le mapping dans bookingRelationsRepository
        private ?User $passenger = null, // Pour le mapping dans bookingRelationsRepository
        private ?User $driver = null, // Pour mapping bookingRelationsRepository

        private ?BookingStatus $bookingStatus = null,
        private array $passengers = [], // Pour charger plusieurs passagers

        private ?\DateTimeImmutable $createdAt = null, // n'a pas de valeur au moment de l'instanciation
        private ?\DateTimeImmutable $updatedAt = null // n'a pas de valeur au moment de l'instanciation

    ) {


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

    public function getBookingRideId(): ?int
    {
        return $this->rideId;
    }

    public function getBookingRide(): ?Ride
    {
        return $this->ride;
    }

    public function getBookingPassengerId(): ?int
    {
        return $this->passengerId;
    }

    public function getBookingPassenger(): ?User
    {
        return $this->passenger;
    }

    public function getBookingDriverId(): ?int
    {
        return $this->driverId;
    }

    public function getBookingDriver(): ?User
    {
        return $this->driver;
    }

    public function getBookingStatus(): ?BookingStatus
    {
        return $this->bookingStatus;
    }

    public function getBookingPassengers(): array
    {
        return $this->passengers;
    }

    public function getBookingCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getBookingUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }



    // ---------Les Setters ---------


    public function setBookingRide(?Ride $ride): self
    {
        if ($ride !== null && $this->passenger !== null) {
            if ($this->passenger->getUserId() === $ride->getRideDriver()->getUserId()) {
                throw new InvalidArgumentException("Un passager ne peut pas réserver son propre trajet.");
            }
        }

        $this->ride = $ride;
        $this->rideId = $ride?->getRideId(); // pour setter les ids si l'objet est null
        $this->updateTimestamp();
        return $this;
    }

    public function setBookingPassenger(?User $passenger): self
    {
        if ($passenger !== null) {
            if ($this->driver !== null && $passenger->getUserId() === $this->driver->getUserId()) {
                throw new InvalidArgumentException("Le chauffeur ne peut pas être passager de son propre trajet.");
            }
            if ($this->ride !== null && $this->ride->getRideDriver()->getUserId() === $passenger->getUserId()) {
                throw new InvalidArgumentException("Un passager ne peut pas réserver son propre trajet.");
            }
        }

        $this->passenger = $passenger;
        $this->passengerId = $passenger?->getUserId(); // pour setter les ids si l'objet est null
        $this->updateTimestamp();
        return $this;
    }

    public function setBookingDriver(?User $driver): self
    {
        if ($driver !== null && $this->passenger !== null) {
            if ($driver->getUserId() === $this->passenger->getUserId()) {
                throw new InvalidArgumentException("Le chauffeur ne peut pas être passager de son propre trajet.");
            }
        }

        $this->driver = $driver;
        $this->driverId = $driver?->getUserId(); // pour setter les ids si l'objet est null
        $this->updateTimestamp();
        return $this;
    }

    public function setBookingStatus(?BookingStatus $bookingStatus): self
    {
        $this->bookingStatus = $bookingStatus;
        $this->updateTimestamp();
        return $this;
    }

    public function setBookingPassengers(array $passengers): self
    {
        $this->passengers = $passengers;
        $this->updateTimestamp();
        return $this;
    }

    public function addBookingPassenger(User $passenger): self
    {
        $this->passengers[] = $passenger;
        $this->updateTimestamp();
        return $this;
    }

    // ---- Mise à jour de la date de modification
    private function updateTimestamp(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
