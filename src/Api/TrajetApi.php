<?php

namespace Src\Api;

use Src\Model\TrajetModel;

class TrajetApi
{
    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'POST') {
            // Récupérer les données JSON envoyées
            $input = json_decode(file_get_contents('php://input'), true);

            // Appeler le modèle
            $model = new TrajetModel();
            $results = $model->search($input);

            header('Content-Type: application/json');
            echo json_encode($results);
        } else {
            http_response_code(405); // méthode non autorisée
            echo json_encode(['error' => 'Méthode non autorisée']);
        }
    }
}
