<?php

namespace App\Services;

use App\Models\User;
use App\Models\Ride;
use App\Models\Booking;


class NotificationService
{

    // Envoi une confirmation de création de trajet par email.
    public function sendRideConfirmation(User $passenger, Ride $ride)
    {
        // Ici tu pourrais envoyer un email, un SMS, ou juste logger
        echo sprintf(
            "Confirmation envoyée à %s pour le trajet %d (départ %s)\n",
            $passenger->getUserName(),
            $ride->getRideId(),
            $ride->getRideDepartureDateTime()
        );
    }

    // Envoi une confirmation d'annulation de trajet par email.
    public function sendRideCancelation(int $userId, int $rideId) {}

    // Envoi une confirmation de création de réservation par email.
    public function sendBookingConfirmation(int $userId, int $rideId) {}

    // Envoi une confirmation d'annulation de réservation par email.
    public function sendBookingCancelation(int $userId, int $rideId) {}
}
