<?php

namespace App\Controllers;

use App\Models\Ride;
use App\DTO\CreateRideDTO;
use App\Services\RideService;
use DateTimeInterface;
use InvalidArgumentException;

class RideController extends BaseController
{

    public function __construct(
        private RideService $rideService
    ) {}


    // ------------------------POST--------------------------------
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
            $ride = $this->rideService->addRide($dto, $userId);

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


    // ------------------------PUT--------------------------------
    // Annule un trajet.
    public function cancelRide(int $rideId, string $jwtToken): void
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

    // Démarre un trajet.
    public function startRide(Ride $ride, string $jwtToken): void
    {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération du trajet à démarrer
            $rideToStart = $this->rideService->startRide($ride, $userId);

            // Vérification de l'existence du trajet
            if ($rideToStart) {
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

    // Finalise un trajet
    public function finalizeRide(Ride $ride, string $jwtToken): void
    {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération du trajet à finaliser
            $rideToStart = $this->rideService->finalizeRide($ride, $userId);

            // Vérification de l'existence du trajet
            if ($rideToStart) {
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

    // Affiche les trajets disponibles en fonction de la date de départ et les ville de départ et d'arrivée
    public function listAllRidesByDateAndPlace(
        DateTimeInterface $date,
        string $departurePlace,
        string $arrivalPlace
    ): void {
        try {
            // Récupération de la liste des trajets avec vérification des droits incluse
            $rides = $this->rideService->SearchRidesByDateAndPlaces($date, $departurePlace, $arrivalPlace);

            // Envoi de la réponse positive
            $this->successResponse($rides);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }


    // Affiche un trajet avec les utilisateurs associés.
    public function getRideWithUsers(
        int $rideId,
        string $jwtToken
    ): void {
        // utilisatation de getRideWithPassengers
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $ride = $this->rideService->getRideWithPassengers($userId, $rideId);

            // Envoi de la réponse positive
            $this->successResponse($ride);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }


    // ----- Historique pour les utilisateurs PASSAGER et CONDUCTEUR--------

    // Affiche tous les trajets d'un conducteur.
    public function listAllRidesByDriver(
        string $jwtToken,
        int $driverId
    ): void {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $rides = $this->rideService->getAllRidesByDriver($userId, $driverId);

            // Envoi de la réponse positive
            $this->successResponse($rides);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche tous les trajets à venir d'un conducteur.
    public function listAllUpcomingRidesByDriver(
        string $jwtToken,
        int $driverId
    ): void {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $rides = $this->rideService->getUpcomingRidesByDriver($userId, $driverId);

            // Envoi de la réponse positive
            $this->successResponse($rides);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche tous les trajets passés d'un conducteur.
    public function listAllPastRidesByDriver(
        string $jwtToken,
        int $driverId
    ): void {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $rides = $this->rideService->getPastRidesByDriver($userId, $driverId);

            // Envoi de la réponse positive
            $this->successResponse($rides);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche tous les trajets d'un passager.
    public function listAllRidesByPassenger(
        string $jwtToken,
        int $passengerId
    ): void {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $rides = $this->rideService->getAllRidesByPassenger($userId, $passengerId);

            // Envoi de la réponse positive
            $this->successResponse($rides);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche tous les trajets à venir d'un passager.
    public function listAllUpcomingRidesByPassenger(
        string $jwtToken,
        int $passengerId
    ): void {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $rides = $this->rideService->getAllRidesByPassenger($userId, $passengerId);

            // Envoi de la réponse positive
            $this->successResponse($rides);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche tous les trajets passé d'un passager.
    public function listAllPastRidesByPassenger(
        string $jwtToken,
        int $passengerId
    ): void {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $rides = $this->rideService->getPastRidesByPassenger($userId, $passengerId);

            // Envoi de la réponse positive
            $this->successResponse($rides);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // --------------------Pour le staff-----------------------

    public function listAllRidesByCreationDate(
        string $jwtToken,
        DateTimeInterface $creationDate
    ): void {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération
            $numberOfRides = $this->rideService->getAllRidesByCreationDate($userId, $creationDate);

            // Envoi de la réponse positive
            $this->successResponse($numberOfRides);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // ------------------Pour les admins-----------------------

    // Affiche le total de trajet pour le jour J.
    public function getNumberOfRidesFromToday(
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération
            $numberOfRides = $this->rideService->getNumberOfRidesFromToday($userId);

            // Envoi de la réponse positive
            $this->successResponse($numberOfRides);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche le total de trajet sur une période.
    public function getNumberOfRidesOverPeriod(
        string $jwtToken,
        DateTimeInterface $start,
        DateTimeInterface $end
    ): void {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération
            $numberOfRides = $this->rideService->getNumberOfRidesOverPeriod($userId, $start, $end);

            // Envoi de la réponse positive
            $this->successResponse($numberOfRides);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche le total des commissions reçues pour le jour J.
    public function getTotalCommissionFromToday(
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération
            $commissionOfTheDay = $this->rideService->getTotalCommissionFromToday($userId);

            // Envoi de la réponse positive
            $this->successResponse($commissionOfTheDay);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche le total des commissions reçues sur une période.
    public function getTotalCommissionOverPeriod(
        string $jwtToken,
        DateTimeInterface $start,
        DateTimeInterface $end
    ): void {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération 
            $totalCommission = $this->rideService->getTotalCommissionOverPeriod($userId, $start, $end);

            // Envoi de la réponse positive
            $this->successResponse($totalCommission);
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }
}
