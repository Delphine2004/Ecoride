<?php

namespace App\Controller;

use App\Model\Ride;
use App\Model\User;
use App\Service\BookingService;
use InvalidArgumentException;

class BookingController extends BaseController
{
    public function __construct(
        private BookingService $bookingService
    ) {
        parent::__construct();
    }


    // ------------------------POST--------------------------------

    public function createBooking(
        Ride $ride,
        User $driver,
        User $passenger,
        string $jwtToken
    ): void {
        // Récupération des données
        $data = $this->getJsonBody();

        // Vérification de la validité des données reçues
        if (!is_array($data) || empty($data)) {
            $this->errorResponse("JSON invalide ou vide.", 400);
            return;
        }

        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);


            // Ajout du trajet
            $booking = $this->bookingService->createBooking($ride, $driver, $passenger, $userId);

            // Définir le header Location
            $bookingId = null;
            if (is_object($booking) && method_exists($booking, 'getBookingId')) {
                $bookingId = $booking->getBookingId();
            } elseif (is_array($booking)) {
                $bookingId = $booking['id'] ?? $booking['booking_id'] ?? null;
            }

            // Envoi de la réponse positive
            $this->successResponse($booking, 201, "/users/{$userId}/booking/{$bookingId}");
        } catch (InvalidArgumentException $e) {
            // // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }


    // ------------------------PUT--------------------------------
    public function cancelBooking(
        int $bookingId,
        string $jwtToken
    ): void {
        try {

            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération du trajet annulé
            $canceled = $this->bookingService->cancelBooking($userId, $bookingId);

            // Vérification de l'existence du trajet
            if ($canceled) {
                $this->successResponse(["message" => "Trajet annulé"], 200);
            } else {
                $this->errorResponse("Trajet introuvable", 404);
            }
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }


    // --------------------------DELETE------------------------------
    // Pas de suppression car conservation de l'historique


    // --------------------------GET----------------------------------
    public function getBooking(
        int $bookingId,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $booking = $this->bookingService->getbooking($bookingId, $userId);

            // Envoi de la réponse positive
            $this->successResponse($booking);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    public function listUpcomingBookingsByPassenger(
        int $passengerId,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $bookings = $this->bookingService->listUpcomingBookingsByPassenger($passengerId, $userId);

            // Envoi de la réponse positive
            $this->successResponse($bookings);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    public function listPastBookingsByPassenger(
        int $passengerId,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $bookings = $this->bookingService->listPastBookingsByPassenger($passengerId, $userId);

            // Envoi de la réponse positive
            $this->successResponse($bookings);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }
}
