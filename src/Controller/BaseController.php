<?php

namespace App\Controllers;

use App\Security\AuthService;
use InvalidArgumentException;

abstract class BaseController
{
    public function __construct(
        private AuthService $authService
    ) {}

    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header("Content-Type: application/json");
        echo json_encode($data);
        exit;
    }

    protected function successResponse(mixed $data, int $statusCode = 200): void
    {
        $this->jsonResponse(['success' => true, 'data' => $data], $statusCode);
    }

    protected function errorResponse(string $message, int $statusCode = 400): void
    {
        $this->jsonResponse(['success' => false, 'error' => $message], $statusCode);
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
