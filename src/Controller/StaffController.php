<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Service\StaffService;
use App\Security\AuthService;
use InvalidArgumentException;

class StaffController extends BaseController
{

    public function __construct(

        private StaffService $staffService,
        private AuthService $authService
    ) {
        parent::__construct($this->authService);
    }

    // --------------------TRAJETS-----------------------

    /**
     * Affiche la liste des trajets par date de création
     *
     * @return void
     */
    public function listRidesByCreationDate(): void
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

            $creationDate = $_GET['created_at'] ?? null;

            // Récupération
            $numberOfRides = $this->staffService->listRidesByCreationDate($creationDate, $userId);

            $this->successResponse($numberOfRides, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    /**
     * Affiche le total de trajet pour le jour J.
     *
     * @return void
     */
    public function getNumberOfRidesFromToday(): void
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

            // Récupération
            $numberOfRides = $this->staffService->getNumberOfRidesFromToday($userId);

            $this->successResponse($numberOfRides, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    /**
     * Affiche le total de trajet sur une période.
     *
     * @return void
     */
    public function getNumberOfRidesOverPeriod(): void
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

            $start = $_GET['start'] ?? null;
            $end = $_GET['end'] ?? null;

            // Récupération
            $numberOfRides = $this->staffService->getNumberOfRidesOverPeriod($start, $end, $userId);

            $this->successResponse($numberOfRides, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    /**
     * Affiche le total de commission reçues en tout
     *
     * @return void
     */
    public function getTotalCommmission()
    {        // Récupération du token
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

            // Récupération
            $totalCommission = $this->staffService->getTotalCommission($userId);

            $this->successResponse($totalCommission, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    /**
     * Affiche le total des commissions reçues pour le jour J.
     *
     * @return void
     */
    public function getTotalCommissionFromToday(): void
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

            // Récupération
            $commissionOfTheDay = $this->staffService->getTotalCommissionFromToday($userId);

            $this->successResponse($commissionOfTheDay, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    /**
     * Affiche le total des commissions reçues sur une période.
     *
     * @return void
     */
    public function getTotalCommissionOverPeriod(): void
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

            $start = $_GET['start'] ?? null;
            $end = $_GET['end'] ?? null;

            // Récupération 
            $totalCommission = $this->staffService->getTotalCommissionOverPeriod($start, $end, $userId);

            $this->successResponse($totalCommission, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }


    //--------------RESERVATIONS--------------------


    /**
     * Affiche la liste des réservation par statut
     *
     * @return void
     */
    public function listBookingsByStatus(): void
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

            $bookingStatus = $_GET['booking_status'] ?? null;

            // Récupération
            $bookings = $this->staffService->listBookingsByBookingStatus($bookingStatus, $userId);

            $this->successResponse($bookings, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    /**
     * Affiche la liste des réservation par date de création
     *
     * @return void
     */
    public function listBookingsByCreatedAt(): void
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

            $creationDate = $_GET['created_at'] ?? null;

            // Récupération
            $bookings = $this->staffService->listBookingsByCreatedAt($creationDate, $userId);

            $this->successResponse($bookings, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }
}
