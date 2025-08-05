<?php
// C'est le point d'entré unique pour toutes les requêtes API

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/Database.php';

// Charger l'autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Récupérer la route (ex: /recherche-covoiturage)
$uri = $_SERVER['REQUEST_URI'];
// Supposons que l'API soit appelée via /ECF/public/api.php/recherche-covoiturage
// On enlève /ECF/public/api.php pour garder /recherche-covoiturage
$route = str_replace('/ECF/public/api.php', '', $uri);
$route = strtok($route, '?'); // enlever la query string

header('Content-Type: application/json');

switch ($route) {
    case '/recherche-covoiturage':
        require_once __DIR__ . '/../src/Api/TrajetApi.php';
        $api = new \Src\Api\TrajetApi();
        $api->handleRequest();
        break;

    case '/connexion':
        require_once __DIR__ . '/../src/Api/TrajetApi.php'; // à changer
        $api = new \Src\Api\TrajetApi(); // à changer
        $api->handleRequest(); // à changer
        break;

    case '/inscription':
        require_once __DIR__ . '/../src/Api/TrajetApi.php'; // à changer
        $api = new \Src\Api\TrajetApi(); // à changer
        $api->handleRequest(); // à changer
        break;

    /************************************************* */
    case '/recherche-covoiturage':
        require_once __DIR__ . '/../src/Api/TrajetApi.php';
        $api = new \Src\Api\TrajetApi();
        $api->handleRequest();
        break;

    case '/recherche-covoiturage':
        require_once __DIR__ . '/../src/Api/TrajetApi.php';
        $api = new \Src\Api\TrajetApi();
        $api->handleRequest();
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Route non trouvée']);
        break;
}
