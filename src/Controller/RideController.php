<?php

namespace App\Controller;

use App\Service\RideService;
use App\Security\AuthService;
use App\DTO\CreateRideDTO;
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
    public function createRide(): void
    {
        // Récupération du token
        $headers = getallheaders();
        $jwtToken = $headers['Authorization'] ?? null;


        if ($jwtToken && str_starts_with($jwtToken, 'Bearer ')) {
            $jwtToken = substr($jwtToken, 7);
        }
        if (!$jwtToken) {
            $this->errorResponse("Token manquant", 401);
            return;
        }

        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

            // Conversion des données en DTO
            $dto = new CreateRideDTO($data);

            // Ajout du trajet
            $ride = $this->rideService->createRide($dto, $userId);


            // Définir le header Location
            $rideId = null;
            if (is_object($ride) && method_exists($ride, 'getRideId')) {
                $rideId = $ride->getRideId();
            } elseif (is_array($ride)) {
                $rideId = $ride['id'] ?? $ride['ride_id'] ?? null;
            }

            $this->successResponse($ride, 201, "/users/{$userId}/ride/{$rideId}");
        } catch (InvalidArgumentException $e) {

            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {

            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Autorise un utilisateur à faire une réservation
    public function createBooking(): void
    {

        // Récupération du token
        $headers = getallheaders();
        $jwtToken = $headers['Authorization'] ?? null;

        if ($jwtToken && str_starts_with($jwtToken, 'Bearer ')) {
            $jwtToken = substr($jwtToken, 7);
        }
        if (!$jwtToken) {
            $this->errorResponse("Token manquant", 401);
            return;
        }

        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

            // Récupération des paramétres depuis la requête
            $rideId = $data['ride_id'] ?? null;
            $passengerId = $data['passenger_id'] ?? $userId;


            // Ajout du trajet
            $booking = $this->rideService->createBooking($rideId, $passengerId);

            // Définir le header Location
            $bookingId = null;
            if (is_object($booking) && method_exists($booking, 'getBookingId')) {
                $bookingId = $booking->getBookingId();
            } elseif (is_array($booking)) {
                $bookingId = $booking['id'] ?? $booking['booking_id'] ?? null;
            }

            $this->successResponse($booking, 201, "/users/{$userId}/booking/{$bookingId}");
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }


    // ------------------------PUT--------------------------------
    // Autorise un utilisateur à annuler un trajet.
    public function cancelRide(): void
    {
        // Récupération du token
        $headers = getallheaders();
        $jwtToken = $headers['Authorization'] ?? null;

        if ($jwtToken && str_starts_with($jwtToken, 'Bearer ')) {
            $jwtToken = substr($jwtToken, 7);
        }
        if (!$jwtToken) {
            $this->errorResponse("Token manquant", 401);
            return;
        }

        try {

            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

            // Récupération des paramétres depuis la requête
            $rideId = $data['ride_id'] ?? null;

            // Récupération du trajet annulé
            $canceled = $this->rideService->cancelRide($rideId, $userId);

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
    public function startRide(): void
    {
        // Récupération du token
        $headers = getallheaders();
        $jwtToken = $headers['Authorization'] ?? null;

        if ($jwtToken && str_starts_with($jwtToken, 'Bearer ')) {
            $jwtToken = substr($jwtToken, 7);
        }
        if (!$jwtToken) {
            $this->errorResponse("Token manquant", 401);
            return;
        }

        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

            // Récupération des paramétres depuis la requête
            $rideId = $data['ride_id'] ?? null;

            // Récupération du trajet à démarrer
            $ride = $this->rideService->startRide($rideId, $userId);

            // Vérification de l'existence du trajet
            if ($ride) {
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
    public function stopRide(): void
    {
        // Récupération du token
        $headers = getallheaders();
        $jwtToken = $headers['Authorization'] ?? null;

        if ($jwtToken && str_starts_with($jwtToken, 'Bearer ')) {
            $jwtToken = substr($jwtToken, 7);
        }
        if (!$jwtToken) {
            $this->errorResponse("Token manquant", 401);
            return;
        }

        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

            // Récupération des paramétres depuis la requête
            $rideId = $data['ride_id'] ?? null;

            // Récupération du trajet à démarrer
            $ride = $this->rideService->stopRide($rideId, $userId);

            // Vérification de l'existence du trajet
            if ($ride) {
                $this->successResponse(["message" => "Trajet arrêté"], 200);
            } else {
                $this->errorResponse("Trajet introuvable", 404);
            }
        } catch (InvalidArgumentException $e) {

            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {

            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Autorise un utilisateur à finaliser un trajet.
    public function finalizeRide(): void
    {
        // Récupération du token
        $headers = getallheaders();
        $jwtToken = $headers['Authorization'] ?? null;

        if ($jwtToken && str_starts_with($jwtToken, 'Bearer ')) {
            $jwtToken = substr($jwtToken, 7);
        }
        if (!$jwtToken) {
            $this->errorResponse("Token manquant", 401);
            return;
        }

        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

            // Récupération des paramétres depuis la requête
            $rideId = $data['ride_id'] ?? null;

            // Récupération du trajet à finaliser
            $ride = $this->rideService->finalizeRide($rideId, $userId);

            // Vérification de l'existence du trajet
            if ($ride) {
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
    public function cancelBooking(): void
    {
        // Récupération du token
        $headers = getallheaders();
        $jwtToken = $headers['Authorization'] ?? null;

        if ($jwtToken && str_starts_with($jwtToken, 'Bearer ')) {
            $jwtToken = substr($jwtToken, 7);
        }
        if (!$jwtToken) {
            $this->errorResponse("Token manquant", 401);
            return;
        }

        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

            // Récupération des paramétres depuis la requête
            $bookingId = $data['booking_id'] ?? null;

            // Récupération du trajet annulé
            $canceled = $this->rideService->cancelBooking($bookingId, $userId);

            // Vérification de l'existence du trajet
            if ($canceled) {
                $this->successResponse(["message" => "Réservation annulé"], 200);
            } else {
                $this->errorResponse("Réservation introuvable", 404);
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
    public function getRideWithUsersById(): void
    {
        // Récupération du token
        $headers = getallheaders();
        $jwtToken = $headers['Authorization'] ?? null;

        if ($jwtToken && str_starts_with($jwtToken, 'Bearer ')) {
            $jwtToken = substr($jwtToken, 7);
        }
        if (!$jwtToken) {
            $this->errorResponse("Token manquant", 401);
            return;
        }

        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

            // Récupération des paramétres depuis la requête
            $rideId = $data['ride_id'] ?? null;

            // Récupération des infos
            $ride = $this->rideService->getRideWithUsersById($rideId, $userId);

            $this->successResponse($ride, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche une réservation 
    public function getBookingById(): void
    {
        // Récupération du token
        $headers = getallheaders();
        $jwtToken = $headers['Authorization'] ?? null;

        if ($jwtToken && str_starts_with($jwtToken, 'Bearer ')) {
            $jwtToken = substr($jwtToken, 7);
        }
        if (!$jwtToken) {
            $this->errorResponse("Token manquant", 401);
            return;
        }

        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

            // Récupération des paramétres depuis la requête
            $bookingId = $data['booking_id'] ?? null;

            // Récupération des infos
            $booking = $this->rideService->getbookingById($bookingId, $userId);

            $this->successResponse($booking, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche les trajets disponibles en fonction de la date de départ et les ville de départ et d'arrivée
    public function listRidesByDateAndPlaces(): void
    {
        try {
            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

            // Récupération des paramétres depuis la requête
            $departurePlace = $data['departure_place'] ?? null;
            $arrivalPlace = $data['arrival_place'] ?? null;
            $date = $data['departure_date_time'] ?? null;

            if (!$date || !$departurePlace || !$arrivalPlace) {
                throw new InvalidArgumentException("Tous les paramètres sont requis");
            }

            // Transformation de la date string en date objet
            $dateObj = new DateTimeImmutable($date);

            // Récupération de la liste des trajets avec vérification des droits incluse
            $rides = $this->rideService->listRidesByDateAndPlaces($dateObj, $departurePlace, $arrivalPlace);

            $this->successResponse($rides, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    //-------------Pour les conducteurs------------------

    // Affiche tous les trajets à venir d'un conducteur.
    public function listUpcomingRidesByDriver(): void
    {
        // Récupération du token
        $headers = getallheaders();
        $jwtToken = $headers['Authorization'] ?? null;

        if ($jwtToken && str_starts_with($jwtToken, 'Bearer ')) {
            $jwtToken = substr($jwtToken, 7);
        }
        if (!$jwtToken) {
            $this->errorResponse("Token manquant", 401);
            return;
        }

        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

            // Récupération des paramétres depuis la requête
            $driverId = $data['driver_id'] ?? null;

            // Récupération des infos
            $rides = $this->rideService->listUpcomingRidesByDriver($driverId, $userId);

            $this->successResponse($rides, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche tous les trajets passés d'un conducteur.
    public function listPastRidesByDriver(): void
    {
        // Récupération du token
        $headers = getallheaders();
        $jwtToken = $headers['Authorization'] ?? null;

        if ($jwtToken && str_starts_with($jwtToken, 'Bearer ')) {
            $jwtToken = substr($jwtToken, 7);
        }
        if (!$jwtToken) {
            $this->errorResponse("Token manquant", 401);
            return;
        }

        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

            // Récupération des paramétres depuis la requête
            $driverId = $data['driver_id'] ?? null;

            // Récupération des infos
            $rides = $this->rideService->listPastRidesByDriver($driverId, $userId);

            $this->successResponse($rides, 200);
        } catch (InvalidArgumentException $e) {

            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {

            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }


    //-------------Pour les Passagers------------------


    public function listUpcomingBookingsByPassenger(): void
    {
        // Récupération du token
        $headers = getallheaders();
        $jwtToken = $headers['Authorization'] ?? null;

        if ($jwtToken && str_starts_with($jwtToken, 'Bearer ')) {
            $jwtToken = substr($jwtToken, 7);
        }
        if (!$jwtToken) {
            $this->errorResponse("Token manquant", 401);
            return;
        }

        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

            // Récupération des paramétres depuis la requête
            $passengerId = $data['passenger_id'] ?? null;

            // Récupération des infos
            $bookings = $this->rideService->listUpcomingBookingsByPassenger($passengerId, $userId);

            $this->successResponse($bookings, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    public function listPastBookingsByPassenger(): void
    {
        // Récupération du token
        $headers = getallheaders();
        $jwtToken = $headers['Authorization'] ?? null;

        if ($jwtToken && str_starts_with($jwtToken, 'Bearer ')) {
            $jwtToken = substr($jwtToken, 7);
        }
        if (!$jwtToken) {
            $this->errorResponse("Token manquant", 401);
            return;
        }

        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

            // Récupération des paramétres depuis la requête
            $passengerId = $data['passenger_id'] ?? null;

            // Récupération des infos
            $bookings = $this->rideService->listPastBookingsByPassenger($passengerId, $userId);

            $this->successResponse($bookings, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }
}
