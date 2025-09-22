<?php

namespace App\Service;

use App\Model\Ride;
use App\Model\User;
use App\Model\Booking;


class NotificationService
{
    //--------------------Trajet----------------------------

    // Envoi une confirmation de création de trajet par email.
    // Confirmations
    public function sendRideConfirmationToDriver(User $user, Ride $ride)
    {
        // Ici tu pourrais envoyer un email, un SMS, ou juste logger
        echo sprintf(
            "Confirmation envoyée à %s pour le trajet %d (départ %s)\n",
            $user->getUserLogin(),
            $ride->getRideId(),
            $ride->getRideDepartureDateTime()
        );
    }

    public function sendRideConfirmationToPassenger(User $user, Ride $ride) {}

    // Annulation
    // Envoi une confirmation d'annulation de trajet par email au passager.
    public function sendRideCancelationToPassenger(User $passenger, Ride $ride) {}

    // Envoi une confirmation d'annulation de trajet par email au conducteur.
    public function sendRideCancelationToDriver(User $driver, Ride $ride) {}

    // Actions
    // envoi une confirmation de démarrage du trajet
    public function sendRideStartToDriver(User $driver, Ride $ride) {}
    public function sendRideStartToPassenger(User $passenger, Ride $ride) {}

    public function sendRideConfirmationStopToDriver(User $driver, Ride $ride) {}

    public function sendRideFinalizationRequestToPassenger(User $passenger, Ride $ride) {}

    // Envoi une confirmation de finalisation de trajet par email au passager. - Doit demander au participant de valider 
    //- voir comment faire pour non validé
    public function sendRideFinalizationToPassenger(User $passenger, Ride $ride) {}

    // Envoi une confirmation de finalisation de trajet par email au conducteur.
    public function sendRideFinalizationToDriver(User $driver, Ride $ride) {}


    //-----------------Réservation--------------------------

    // Envoi une confirmation de création de réservation par email.
    public function sendBookingConfirmationToPassenger(User $passenger, Booking $booking) {}

    // Envoi une confirmation de création de réservation par email.
    public function sendBookingConfirmationToDriver(User $driver, Booking $booking) {}


    // Envoi une confirmation d'annulation de réservation sans frais par email au passager.
    public function sendBookingCancelationToPassenger(User $passenger, Booking $booking) {}

    // Envoi une confirmation d'annulation de réservation sans frais par email au conducteur.
    public function sendBookingCancelationToDriver(User $driver, Booking $booking) {}


    // Envoi une confirmation d'annulation de réservation tardive par email au passager.
    public function sendBookingLateCancelationToPassenger(User $passenger, Booking $booking) {}

    // Envoi une confirmation d'annulation de réservation tardive par email au conducteur.
    public function sendBookingLateCancelationToDriver(User $driver, Booking $booking) {}
}
