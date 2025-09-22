<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Security\AuthService;
use App\DTO\CreateUserDTO;
use App\DTO\UpdateUserDTO;
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
        try {
            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

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

            $this->successResponse($user, 201, "/users/{$userId}");
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Autorise un admin à créer un employé
    public function createEmployee(): void
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

            $this->successResponse($user, 201, "/users/{$userId}");
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // ------------------------PUT--------------------------------
    // Autorise un utilisateur à modifier son profil
    public function updateProfile(): void
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
            $dto = new UpdateUserDTO($data);

            // Récupération de l'utilisateur à modifier
            $userToUpdate = $this->authService->updateProfile($dto, $userId);

            // Vérification de l'existence de l'utilisateur à modifier
            if ($userToUpdate) {
                $this->successResponse(["message" => "Profil modifié."], 200);
            } else {
                $this->errorResponse("Utilisateur introuvable", 404);
            }
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Autorise un utilisateur à modifier son mot de passe
    public function modifyPassword(): void
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

            $newPassword = $data['new_password'] ?? null;
            $oldPassword = $data['old_password'] ?? null;

            // Récupération de l'utilisateur à modifier
            $userToUpdate = $this->authService->modifyPassword($newPassword, $oldPassword, $userId);

            // Vérification de l'existence de l'utilisateur à modifier
            if ($userToUpdate) {
                $this->successResponse(["message" => "Profil modifié."], 200);
            } else {
                $this->errorResponse("Utilisateur introuvable", 404);
            }
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Autorise un utilisateur à ajouter le rôle CONDUCTEUR
    public function becomeDriver(): void
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
            $dto = new UpdateUserDTO($data);

            // Récupération de l'utilisateur à modifier
            $userToUpdate = $this->authService->becomeDriver($dto, $userId);

            // Vérification de l'existence de l'utilisateur à modifier
            if ($userToUpdate) {
                $this->successResponse(["message" => "Profil modifié."], 200);
            } else {
                $this->errorResponse("Utilisateur introuvable", 404);
            }
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }


    // --------------------------DELETE------------------------------
    // Autorise un utilisateur à supprimer son compte
    public function deleteAccount(): void
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

            // Suppression de l'utilisateur
            $removed = $this->authService->deleteAccount($userId);

            // Vérification de l'existence de l'utilisateur
            if ($removed) {
                $this->successResponse(["message" => "Utilisateur supprimé."]);
            } else {
                $this->errorResponse("Utilisateur introuvable", 404);
            }
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }

    // Autorise un admin à supprimer un autre compte
    public function deleteUserByAdmin(): void
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
            $adminId = $this->getUserIdFromToken($jwtToken);

            // Récupération des données
            $data = $this->getJsonBody();

            // Vérification de la validité des données reçues
            if (!is_array($data) || empty($data)) {
                $this->errorResponse("JSON invalide ou vide.", 400);
                return;
            }

            // Récupération des paramétres depuis la requête
            $userId = $data['user_id'] ?? null;

            // Suppression de l'utilisateur
            $removed = $this->authService->deleteAccountByAdmin($userId, $adminId);

            // Vérification de l'existence de l'utilisateur
            if ($removed) {
                $this->successResponse(["message" => "Utilisateur supprimé."], 204);
            } else {
                $this->errorResponse("Utilisateur introuvable", 404);
            }
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
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
    public function getUserById(): void
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

            // Récupération des infos
            $user = $this->authService->getUserById($userId);

            $this->successResponse($user, 200);
        } catch (InvalidArgumentException $e) {
            $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            $this->errorResponse("Erreur serveur : " . $e->getMessage(), 500);
        }
    }
}
