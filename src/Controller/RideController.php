<?php

namespace App\Controller;

use App\Model\User;
use App\Model\Ride;
use App\DTO\CreateRideDTO;
use App\Service\RideService;
use App\Security\AuthService;
use DateTimeImmutable;
use InvalidArgumentException;

class RideController extends BaseController
{

    public function __construct(
        private RideService $rideService,
        private AuthService $authService
    ) {
        parent::__construct($this->authService);
    }


    // ------------------------POST--------------------------------
    // Autorise un utilisateur à créer un trajet
    public function createRide(
        CreateRideDTO $dto,
        string $jwtToken
    ): void {

        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Ajout du trajet
            $ride = $this->rideService->createRide($dto, $userId);

            // Envoi de la réponse positive
            $this->successResponse($ride, 201);
        } catch (InvalidArgumentException $e) {

            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {

            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Autorise un utilisateur à faire une réservation
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
            $booking = $this->rideService->createBooking($ride, $driver, $passenger, $userId);

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
    // Autorise un utilisateur à annuler un trajet.
    public function cancelRide(
        int $rideId,
        string $jwtToken
    ): void {
        try {

            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération du trajet annulé
            $canceled = $this->rideService->cancelRide($userId, $rideId);

            // Vérification de l'existence du trajet
            if ($canceled) {
                $this->successResponse(["message" => "Trajet annulé"], 200);
            } else {
                $this->errorResponse("Trajet introuvable", 404);
            }
        } catch (InvalidArgumentException $e) {

            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {

            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Autorise un utilisateur à démarrer un trajet.
    public function startRide(
        int $rideId,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération du trajet à démarrer
            $rideToStart = $this->rideService->startRide($rideId, $userId);

            // Vérification de l'existence du trajet
            if ($rideToStart) {
                $this->successResponse(["message" => "Trajet démarré"], 200);
            } else {
                $this->errorResponse("Trajet introuvable", 404);
            }
        } catch (InvalidArgumentException $e) {

            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {

            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Autorise un utilisateur à arrêter un trajet.
    public function stopRide() {}

    // Autorise un utilisateur à finaliser un trajet.
    public function finalizeRide(
        int $rideId,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération du trajet à finaliser
            $rideToStart = $this->rideService->finalizeRide($rideId, $userId);

            // Vérification de l'existence du trajet
            if ($rideToStart) {
                $this->successResponse(["message" => "Trajet finalisé"], 200);
            } else {
                $this->errorResponse("Trajet introuvable", 404);
            }
        } catch (InvalidArgumentException $e) {

            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {

            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    //  Autorise un utilisateur à annuler une réservation
    public function cancelBooking(
        int $bookingId,
        string $jwtToken
    ): void {
        try {

            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération du trajet annulé
            $canceled = $this->rideService->cancelBooking($userId, $bookingId);

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

    // Affiche un trajet avec les utilisateurs associés.
    public function getRideWithPassengers(
        int $rideId,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $ride = $this->rideService->getRideWithPassengers($userId, $rideId);

            // Envoi de la réponse positive
            $this->successResponse($ride, 200);
        } catch (InvalidArgumentException $e) {

            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {

            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche une réservation 
    public function getBooking(
        int $bookingId,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $booking = $this->rideService->getbooking($bookingId, $userId);

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

    // Affiche les trajets disponibles en fonction de la date de départ et les ville de départ et d'arrivée
    public function listRidesByDateAndPlaces(): void
    {

        $departurePlace = $_GET['departure_place'] ?? null;
        $arrivalPlace = $_GET['arrival_place'] ?? null;
        $date = $_GET['departure_date_time'] ?? null;

        if (!$date || !$departurePlace || !$arrivalPlace) {
            throw new InvalidArgumentException("Tous les paramètres sont requis");
        }
        try {
            // Transformation de la date string en date objet
            $dateObj = new DateTimeImmutable($date);

            // Récupération de la liste des trajets avec vérification des droits incluse
            $rides = $this->rideService->listRidesByDateAndPlaces($dateObj, $departurePlace, $arrivalPlace);

            // Envoi de la réponse positive
            $this->successResponse($rides, 200);
        } catch (InvalidArgumentException $e) {

            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {

            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }
}
