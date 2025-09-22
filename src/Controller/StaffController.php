<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Service\StaffService;
use App\Security\AuthService;
use App\Enum\BookingStatus;
use InvalidArgumentException;
use DateTimeImmutable;

class StaffController extends BaseController
{

    public function __construct(

        private StaffService $staffService,
        private AuthService $authService
    ) {
        parent::__construct($this->authService);
    }

    // --------------------TRAJETS-----------------------

    // Affiche la liste des trajets par date de création
    public function listRidesByCreationDate(
        DateTimeImmutable $creationDate,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération
            $numberOfRides = $this->staffService->listRidesByCreationDate($creationDate, $userId);

            $this->successResponse($numberOfRides, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche le total de trajet pour le jour J.
    public function getNumberOfRidesFromToday(
        string $jwtToken
    ): void {
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

    // Affiche le total de trajet sur une période.
    public function getNumberOfRidesOverPeriod(
        string $jwtToken,
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération
            $numberOfRides = $this->staffService->getNumberOfRidesOverPeriod($start, $end, $userId);

            $this->successResponse($numberOfRides, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche le total de commission reçues en tout
    public function getTotalCommmission() {}

    // Affiche le total des commissions reçues pour le jour J.
    public function getTotalCommissionFromToday(
        string $jwtToken
    ): void {
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

    // Affiche le total des commissions reçues sur une période.
    public function getTotalCommissionOverPeriod(
        string $jwtToken,
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

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


    // Affiche la liste des réservation par statut
    public function listBookingsByStatus(
        BookingStatus $bookingStatus,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération
            $bookings = $this->staffService->listBookingsByBookingStatus($bookingStatus, $userId);

            $this->successResponse($bookings, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Affiche la liste des réservation par date de création
    public function listBookingsByCreatedAt(
        DateTimeImmutable $createdAtDate,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération
            $bookings = $this->staffService->listBookingsByCreatedAt($createdAtDate, $userId);

            $this->successResponse($bookings, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }
}
