<?php

namespace App\Controller;

use App\Security\AuthService;
use InvalidArgumentException;

abstract class BaseController
{
    public function __construct(
        private AuthService $authService
    ) {}

    protected function jsonResponse(array $data, int $statusCode = 200, array $headers = []): void
    {
        http_response_code($statusCode);

        // Header par défaut
        header("Content-Type: application/json");

        // Headers additionnels
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        exit;
    }

    protected function successResponse(mixed $data, int $statusCode = 200, ?string $location = null): void
    {
        $headers = [];
        if ($location !== null) {
            $headers['Location'] = $location;
        }

        $this->jsonResponse(
            [
                'success' => true,
                'data' => $data
            ],
            $statusCode
        );
    }

    protected function errorResponse(string $message, int $statusCode = 400, array $context = []): void
    {

        $this->jsonResponse(
            [
                'success' => false,
                'error' => $message,
                'context' => $context
            ],
            $statusCode
        );
    }

    protected function getJsonBody(): array
    {
        $input = file_get_contents("php://input");
        return $input ? json_decode($input, true) ?? [] : [];
    }

    protected function getUserIdFromToken(string $jwtToken): int
    {
        $userdata = $this->authService->verifyToken($jwtToken);

        if (!isset($userdata['user_id'])) {
            throw new InvalidArgumentException("user_id manquant.");
        }

        return (int) $userdata['user_id'];
    }
}
