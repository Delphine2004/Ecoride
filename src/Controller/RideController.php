<?php

namespace App\Controller;

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
        AuthService $authService
    ) {
        parent::__construct($authService);
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


    // ------------------------PUT--------------------------------
    // Annule un trajet.
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

    // Démarre un trajet.
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

    // Finalise un trajet
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

    // Affiche les trajets disponibles en fonction de la date de départ et les ville de départ et d'arrivée
    public function listRidesByDateAndPlaces(
        string $date,
        string $departurePlace,
        string $arrivalPlace
    ): void {
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


    //-------------Pour les conducteurs------------------


    // Affiche tous les trajets à venir d'un conducteur.
    public function listUpcomingRidesByDriver(
        int $driverId,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $rides = $this->rideService->listUpcomingRidesByDriver($userId, $driverId);

            // Envoi de la réponse positive
            $this->successResponse($rides, 200);
        } catch (InvalidArgumentException $e) {

            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {

            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche tous les trajets passés d'un conducteur.
    public function listPastRidesByDriver(
        int $driverId,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $rides = $this->rideService->listPastRidesByDriver($userId, $driverId);

            // Envoi de la réponse positive
            $this->successResponse($rides, 200);
        } catch (InvalidArgumentException $e) {

            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {

            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }


    //-------------Pour les Passagers------------------


    // Affiche tous les trajets à venir d'un passager.
    public function listUpcomingRidesByPassenger(
        int $passengerId,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $rides = $this->rideService->listUpcomingRidesByPassenger($userId, $passengerId);

            // Envoi de la réponse positive
            $this->successResponse($rides, 200);
        } catch (InvalidArgumentException $e) {

            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {

            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche tous les trajets passé d'un passager.
    public function listPastRidesByPassenger(
        int $passengerId,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des infos
            $rides = $this->rideService->listPastRidesByPassenger($userId, $passengerId);

            // Envoi de la réponse positive
            $this->successResponse($rides, 200);
        } catch (InvalidArgumentException $e) {

            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {

            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }
}
