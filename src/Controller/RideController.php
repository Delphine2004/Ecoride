<?php

namespace App\Controllers;

use App\DTO\CreateRideDTO;
use App\Services\RideService;
use InvalidArgumentException;

class RideController extends BaseController
{

    public function __construct(
        private RideService $rideService
    ) {}


    // POST
    public function createRide(string $jwtToken): void
    {
        // Récupération des données
        $data = $this->getJsonBody();

        // Vérification de la validité des données reçues
        if (!is_array($data) || empty($data)) {
            $this->errorResponse("JSON invalide ou vide.", 400);
            return;
        }

        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Conversion des données en DTO
            $dto = new CreateRideDTO($data);

            // Ajout du trajet
            $ride = $this->rideService->addRide($userId, $dto);

            // Définir le header Location
            $rideId = null;
            if (is_object($ride) && method_exists($ride, 'getRideId')) {
                $rideId = $ride->getRideId();
            } elseif (is_array($ride)) {
                $rideId = $ride['id'] ?? $ride['ride_id'] ?? null;
            }
            // Envoi de la réponse positive
            $this->successResponse($ride, 201, "/users/{$userId}/rides/{$rideId}");
        } catch (InvalidArgumentException $e) {
            // // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // PUT  - manque l'authentification
    public function cancelRide(string $jwtToken, int $rideId): void
    {
        try {

            // Récupération de l'id de l'utilisateur dans le token avec vérification
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
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }


    // DELETE - Pas de suppression car conservation de l'historique


    // GET
    public function listUserRides() {}

    public function listUserUpcomingRides() {}

    public function listUserPastRides() {}
}
