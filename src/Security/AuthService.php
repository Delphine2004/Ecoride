<?php

namespace App\Security;


use App\Model\User;
use App\Service\UserService;
use Firebase\JWT\JWT; // sert à encoder/décoder
use Firebase\JWT\Key; // sert à vérifier un JWT avec une clé spécifique et un algorithme
use InvalidArgumentException;

class AuthService extends UserService
{

    private string $secretKey = "ma_cle_secrete";
    private int $accessExpiry = 3600; // exipiration 1h



    public function login(string $email, string $password): array
    {
        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserByEmail($email);

        // Vérification de l'existence de l'utilisateur et vérification du mot de passe
        if (!$user || !password_verify($password, $user['password'])) {
            throw new InvalidArgumentException('Email ou mot de passe incorrect.');
        }

        // Récupération des rôles
        $roles = $user->getUserRoles();

        // Génération du access token (JWT)
        $accessToken = $this->generateAccessToken($user, $roles);

        //retourne les rôles pour le front.
        return [
            'access_token' => $accessToken,
            'expires_in' => $this->accessExpiry,
            'role' => $roles
        ];
    }

    public function logout(User $user) {}

    public function generateAccessToken(User $user): string
    {
        // Création du JWT avec les informations nécessaires
        $payload = [
            'user_id' => $user->getUserId(),
            'email' => $user->getUserEmail(),
            'roles' => $user->getUserRoles(),
            'iat' => time(),
            'exp' => time() + $this->accessExpiry
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
        // Sert pour toutes les requêtes sécurisées (dans header Authorization).
    }

    public function verifyToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new key($this->secretKey, 'HS256'));
            return (array)$decoded;
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Token invalide ou expiré.");
        }
    }
}
