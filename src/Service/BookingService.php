<?php

namespace App\Service;

use App\Models\Booking;
use App\Repositories\BookingRepository;
use App\Repositories\RideRepository;
use App\Repositories\UserRepository;
use App\Enum\BookingStatus;
use InvalidArgumentException;

// Pas besoin de mise à jour de la date car fait dans Booking

class BookingService
{
    // Promotion des propriétés (depuis PHP 8)
    public function __construct(
        private BookingRepository $bookingRepository,
        private RideRepository $rideRepository,
        private UserRepository $userRepository,

    ) {
        $this->bookingRepository = $bookingRepository;
        $this->rideRepository = $rideRepository;
        $this->userRepository = $userRepository;
    }

    // Création d'une réservation
    public function createBooking(int $rideId, int $driverId, int $passengerId): Booking
    {
        // récupération des données
        $ride = $this->rideRepository->findRideById($rideId);
        $driver = $this->userRepository->findUserById($driverId);
        $passenger = $this->userRepository->findUserById($passengerId);


        // Vérifications
        if (!$ride  || !$driver || !$passenger) {
            throw new InvalidArgumentException("Trajet, conducteur ou passager introuvable.");
        }

        if ($ride->getRideAvailableSeats() <= 0) {
            throw new InvalidArgumentException("Il n'y a plus de place disponible pour ce trajet.");
        }

        // Création de l'entité
        $booking = new Booking(
            ride: $ride,
            passenger: $passenger,
            driver: $driver
        );

        //Enregistrement en BD
        $this->bookingRepository->insertBooking($booking);

        // Mise à jour du nombre de place
        $ride->setRideAvailableSeats($ride->getRideAvailableSeats() - 1); // à modifier pour que cela prenne le nombre de place réservée
        $this->rideRepository->updateRide($ride);

        return $booking;
    }

    // Mise à jour d'une réservation
    public function modifyBooking(int $bookingId, int $rideId, int $driverId, int $passengerId): Booking
    {
        // Récupération de la réservation
        $booking = $this->bookingRepository->findBookingById($bookingId);

        // Vérification de l'existence de la réservation
        if (!$booking) {
            throw new InvalidArgumentException("Réservation introuvable.");
        }


        // Récupération des entités
        $ride = $this->rideRepository->findRideById($rideId);
        $driver = $this->userRepository->findUserById($driverId);
        $passenger = $this->userRepository->findUserById($passengerId);

        // Vérification des entités
        if (!$ride || !$passenger || !$driver) {
            throw new InvalidArgumentException("Trajet, conducteur ou passager vide.");
        }


        // Gestion du nombre de place
        $oldRide = $booking->getbookingRide();
        if ($oldRide->getRideId() !== $ride->getRideId()) {
            $oldRide->setRideAvailableSeats($oldRide->getRideAvailableSeats() + 1);
            $this->rideRepository->updateRide($oldRide);

            if ($ride->getRideAvailableSeats() <= 0) {
                throw new InvalidArgumentException("Il n'y a plus de place dipsonible sur le trajet.");
            }
            $ride->setRideAvailableSeats($ride->getRideAvailableSeats() - 1);
            $this->rideRepository->updateRide($ride);
        }


        // Modification de l'entité
        $booking->setBookingRide($ride);
        $booking->setBookingDriver($driver);
        $booking->setBookingPassenger($passenger);

        // Enregistrement en BD
        $this->bookingRepository->updateBooking($booking);

        return $booking;
    }


    // Annulation d'une réservation
    public function cancelBooking(int $bookingId): Booking
    {
        // Récupération de la réservation
        $booking = $this->bookingRepository->findBookingById($bookingId);

        // Vérification de l'existence de la réservation
        if (!$booking) {
            throw new InvalidArgumentException("Réservation introuvable.");
        }

        // Vérification que la réservation n'est pas déjà annulée.
        if ($booking->getBookingStatus() === BookingStatus::ANNULEE) {
            throw new InvalidArgumentException("La réservation est déjà annulée.");
        }

        // Mise à jour du status
        $booking->setBookingStatus(BookingStatus::ANNULEE);

        // Mise à jour des places disponibles
        $ride = $booking->getBookingRide();
        $ride->setRideAvailableSeats($ride->getRideAvailableSeats() + 1);
        $this->rideRepository->updateRide($ride); // permet de conserver l'historique


        // Enregistrement en BD
        $this->bookingRepository->updateBooking($booking);

        return $booking;
    }

    // Lister les utilisateurs de la réservation
    public function listUsersBooking(int $bookingId): array
    {
        // Récupération de la réservation
        $booking = $this->bookingRepository->findBookingById($bookingId);

        // Vérification de l'existence de la réservation
        if (!$booking) {
            throw new InvalidArgumentException("Réservation introuvable.");
        }

        return [
            'driver' => $booking->getBookingDriver(),
            'passenger' => $booking->getBookingPassenger(),
        ];
    }
}
