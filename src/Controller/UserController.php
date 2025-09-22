<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Security\AuthService;
use App\Model\User;
use App\DTO\CreateUserDTO;
use InvalidArgumentException;

class UserController extends BaseController
{
    public function __construct(
        private AuthService $authService
    ) {
        parent::__construct($this->authService);
    }

    // ------------------------POST--------------------------------

    // Permet à un visiteur de créer un compte
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
            $user = $this->authService->createAccount($dto);

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

    // Autorise un admin à créer un employé
    public function createEmployee(
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

            // Conversion des données en DTO
            $dto = new CreateUserDTO($data);

            // Ajout du trajet
            $user = $this->authService->createEmployeeAccount($dto, $userId);

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

    // ------------------------PUT--------------------------------
    // Autorise un utilisateur à modifier son profil
    public function updateProfile(
        User $user,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
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

    // Autorise un utilisateur à modifier son mot de passe
    public function modifyPassword(
        User $user,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
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

    // Autorise un utilisateur à ajouter le rôle CONDUCTEY
    public function becomeDriver(
        User $user,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
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


    // --------------------------DELETE------------------------------
    // Autorise un utilisateur à supprimer son compte
    public function deleteAccount(
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $userId = $this->getUserIdFromToken($jwtToken);

            // Suppression de l'utilisateur
            $removed = $this->authService->deleteAccount($userId);

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

    // Autorise un admin à supprimer un autre compte
    public function deleteUserByAdmin(
        int $userId,
        string $jwtToken
    ): void {
        try {
            // Récupération de l'id de l'utilisateur
            $adminId = $this->getUserIdFromToken($jwtToken);
            // Suppression de l'utilisateur
            $removed = $this->authService->deleteAccountByAdmin($userId, $adminId);

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

    // --------------------------GET----------------------------------

    /**
     * Récupére un utilisateur
     *
     * @param integer $userId
     * @return void
     */
    public function getUserById(
        int $userId
    ): void {
        try {
            // Récupération des infos
            $user = $this->authService->getUserById($userId);
            // Envoi de la réponse positive
            $this->successResponse($user, 200);
        } catch (InvalidArgumentException $e) {

            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {

            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }
}
