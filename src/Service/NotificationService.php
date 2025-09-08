<?php

namespace App\Services;

use App\Models\User;
use App\Models\Ride;
use App\Models\Booking;


class NotificationService
{

    // Envoi une confirmation de création de trajet par email.
    public function sendRideConfirmation(User $user, Ride $ride)
    {
        // Ici tu pourrais envoyer un email, un SMS, ou juste logger
        echo sprintf(
            "Confirmation envoyée à %s pour le trajet %d (départ %s)\n",
            $user->getUserName(),
            $ride->getRideId(),
            $ride->getRideDepartureDateTime()
        );
    }

    // Envoi une confirmation d'annulation de trajet par email.
    public function sendRideCancelation(User $user, Ride $ride) {}

    public function sendRideCancelationToDriver(User $user, Ride $ride) {}

    // Envoi une confirmation de finalisation de trajet par email
    public function sendRideFinalization(User $user, Ride $ride) {}


    // Envoi une confirmation de création de réservation par email.
    public function sendBookingConfirmation(User $user, Booking $booking) {}

    // Envoi une confirmation d'annulation de réservation par email.
    public function sendBookingCancelation(User $user, Booking $booking) {}
}
