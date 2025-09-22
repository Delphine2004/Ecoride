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

    // Affiche le total de trajet pour le jour J.
    public function getNumberOfRidesFromToday(
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération
            $numberOfRides = $this->staffService->getNumberOfRidesFromToday($userId);

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
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération
            $numberOfRides = $this->staffService->getNumberOfRidesOverPeriod($start, $end, $userId);

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
        DateTimeImmutable $start,
        DateTimeImmutable $end
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération 
            $totalCommission = $this->staffService->getTotalCommissionOverPeriod($start, $end, $userId);

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
