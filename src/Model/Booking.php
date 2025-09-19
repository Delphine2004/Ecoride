<?php

namespace App\Model;

use App\Model\Ride;
use App\Model\User;
use App\Enum\BookingStatus;
use InvalidArgumentException;
use DateTimeImmutable;

/**
 * Cette classe représente une réservation de trajet dans la BD.
 */

class Booking
{

    public function __construct(
        public ?int $bookingId = null, // n'a pas de valeur au moment de l'instanciation

        public ?int $rideId = null, // pour l'hydratation brute dans bookingRepository
        public ?int $passengerId = null, // pour l'hydratation brute dans bookingRepository
        public ?int $driverId = null, // pour l'hydratation brute dans bookingRepository

        public ?Ride $ride = null, // Pour le mapping dans bookingRepository
        public ?User $passenger = null, // Pour le mapping dans bookingRepository
        public ?User $driver = null, // Pour mapping bookingRepository

        public ?BookingStatus $bookingStatus = null,
        public array $passengers = [], // Pour charger plusieurs passagers

        public ?\DateTimeImmutable $createdAt = null, // n'a pas de valeur au moment de l'instanciation
        public ?\DateTimeImmutable $updatedAt = null // n'a pas de valeur au moment de l'instanciation

    ) {


        $this->setBookingRide($ride)
            ->setBookingPassenger($passenger)
            ->setBookingDriver($driver)
            ->setBookingStatus($bookingStatus);

        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    public static function fromDatabaseRow(array $row): self
    {
        $bookingId = $row['booking_id'] ?? null;
        $rideId = $row['rideid'] ?? null;
        $passengerId = $row['passenger_id'] ?? null;
        $driverId = $row['driver_id'] ?? null;
        $bookingStatus = $row['booking_status'] ? \App\Enum\BookingStatus::from($row['booking_status']) : null;
        $createdAt = $row['created_at'] ? new DateTimeImmutable($row['created_at']) : null;
        $updatedAt = $row['updated_at'] ? new DateTimeImmutable($row['updated_at']) : null;

        return new self(
            bookingId: $bookingId,
            rideId: $rideId,
            passengerId: $passengerId,
            driverId: $driverId,
            bookingStatus: $bookingStatus,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
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
