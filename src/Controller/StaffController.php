<?php

use App\Enum\BookingStatus;
use App\Controllers\BaseController;
use App\Services\BookingService;
use App\Services\RideService;

class StaffController extends BaseController
{

    public function __construct(
        private RideService $rideService,
        private BookingService $bookingService
    ) {}

    // --------------------TRAJETS-----------------------

    public function listRidesByCreationDate(
        DateTimeInterface $creationDate,
        string $jwtToken

    ): void {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération
            $numberOfRides = $this->rideService->listRidesByCreationDate($creationDate, $userId);

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
            $numberOfRides = $this->rideService->getNumberOfRidesOverPeriod($start, $end, $userId);

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
            $totalCommission = $this->rideService->getTotalCommissionOverPeriod($start, $end, $userId);

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


    //-------------------BOOKING--------------------------
    public function listBookingsByDepartureDate(DateTimeImmutable $departureDate, string $jwtToken): void
    {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération
            $bookings = $this->bookingService->listBookingsByDepartureDate($departureDate, $userId);

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

    public function listBookingsByStatus(BookingStatus $bookingStatus, string $jwtToken): void
    {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération
            $bookings = $this->bookingService->listBookingsByStatus($bookingStatus, $userId);

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

    public function listBookingsByCreatedAt(DateTimeImmutable $createdAtDate, string $jwtToken): void
    {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération
            $bookings = $this->bookingService->listBookingsByCreatedAt($createdAtDate, $userId);

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
