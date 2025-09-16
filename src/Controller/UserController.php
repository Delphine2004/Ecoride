<?php

namespace App\Controller;

use App\Model\User;
use App\DTO\CreateUserDTO;
use App\Service\RideService;
use App\Service\UserService;
use InvalidArgumentException;

class UserController extends BaseController
{
    public function __construct(
        private UserService $userService,
        private RideService $rideService
    ) {}

    // POST
    public function createUser(): void
    {
        // Récupération des données
        $data = $this->getJsonBody();

        // Vérification de la validité des données reçues
        if (!is_array($data) || empty($data)) {
            $this->errorResponse("JSON invalide ou vide.", 400);
            return;
        }

        try {

            // Conversion des données en DTO
            $dto = new CreateUserDTO($data);

            // Ajout du trajet
            $user = $this->userService->createAccount($dto);

            // Définir le header Location
            $userId = null;
            if (is_object($user) && method_exists($user, 'getuserId')) {
                $userId = $user->getuserId();
            } elseif (is_array($user)) {
                $userId = $user['id'] ?? $user['user_id'] ?? null;
            }
            // Envoi de la réponse positive
            $this->successResponse($user, 201, "/users/{$userId}/users/{$userId}");
        } catch (InvalidArgumentException $e) {
            // // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    public function createStaff(string $jwtToken): void
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
            $dto = new CreateUserDTO($data);

            // Ajout du trajet
            $user = $this->userService->createEmployeeAccount($dto, $userId);

            // Définir le header Location
            $userId = null;
            if (is_object($user) && method_exists($user, 'getUserId')) {
                $userId = $user->getUserId();
            } elseif (is_array($user)) {
                $userId = $user['id'] ?? $user['user_id'] ?? null;
            }
            // Envoi de la réponse positive
            $this->successResponse($user, 201, "/users/{$userId}/users/{$userId}");
        } catch (InvalidArgumentException $e) {
            // // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // PUT

    public function updateProfile(User $user, string $jwtToken): void
    {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération de l'utilisateur à modifier
            $userToUpdate = $this->updateProfile($user, $userId);

            // Vérification de l'existence de l'utilisateur à modifier
            if ($userToUpdate) {
                $this->successResponse(["message" => "Profil modifié."], 200);
            } else {
                $this->errorResponse("Utilisateur introuvable", 404);
            }
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    public function modifyPassword(User $user, string $jwtToken): void
    {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération de l'utilisateur à modifier
            $userToUpdate = $this->modifyPassword($user, $userId);

            // Vérification de l'existence de l'utilisateur à modifier
            if ($userToUpdate) {
                $this->successResponse(["message" => "Profil modifié."], 200);
            } else {
                $this->errorResponse("Utilisateur introuvable", 404);
            }
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    public function becomeDriver(User $user, string $jwtToken): void
    {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Récupération de l'utilisateur à modifier
            $userToUpdate = $this->becomeDriver($user, $userId);

            // Vérification de l'existence de l'utilisateur à modifier
            if ($userToUpdate) {
                $this->successResponse(["message" => "Profil modifié."], 200);
            } else {
                $this->errorResponse("Utilisateur introuvable", 404);
            }
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // DELETE
    // Voir comment conserver l'historique des trajets et réservations
    public function deleteAccount(string $jwtToken): void
    {
        try {
            // Récupération de l'id de l'utilisateur dans le token avec vérification
            $userId = $this->getUserIdFromToken($jwtToken);

            // Suppression de l'utilisateur
            $removed = $this->userService->deleteAccount($userId);

            // Vérification de l'existence de l'utilisateur
            if ($removed) {
                $this->successResponse(["message" => "Utilisateur supprimé."]);
            } else {
                $this->errorResponse("Utilisateur introuvable", 404);
            }
        } catch (InvalidArgumentException $e) {
            // Attrappe les erreurs "bad request" (la validation du DTO ou donnée invalide envoyée par le client)
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Attrappe les erreurs "Internal Server Error"
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    public function deleteUserByAdmin(string $jwtToken): void {}

    // GET
    // pas besoin de faire getDriverWithCars car listUserCars dans carController
    // pas besoin de faire getDriverWithRides car listRidesBydriver dans ridesController
    // pas besoin de faire getUserWithBookings car listBookingsbyPassenger dans bookingRepository

}
