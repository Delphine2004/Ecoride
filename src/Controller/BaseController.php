<?php

namespace App\Controllers;

abstract class BaseController
{

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
}
