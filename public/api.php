<?php
// C'est le point d'entré unique pour toutes les requêtes API

//Inclure l'autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Config;
use App\Utils\Response;
use Src\Api\TrajetApi;


//Démarrer une session ou reprend la session existante
session_start();

//Charger les variables d'environnement
Config::load();

// ----------------------------------------


//récuperer la methode HTTP (GET, POST, PUT, PATCH) et L'URI(/login, /car/1)
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$basePath = 'api.php';
$route = substr($uri, strlen($basePath));
$route = $route ?: '/';
$route = strtok($route, '?'); // enlever la query string

$response = new Response();


// définition des routes
$routes = [
    'GET' => [
        'trajet' => function () {
            $api = new TrajetApi();
            //$api->list(); // fonction à définir qui liste les trajets
        },
        'trajet' => function () { // à modifier
            $api = new TrajetApi(); // à modifier
            //$api->list(); // à modifier
        },
        'trajet' => function () { // à modifier
            $api = new TrajetApi(); // à modifier
            //$api->list(); // à modifier
        }

    ],
    'POST' => [
        'trajet' => function () {
            $api = new TrajetApi();
            //$api->list(); // fonction à définir qui liste les trajets
        },
        'trajet' => function () { // à modifier
            $api = new TrajetApi(); // à modifier
            //$api->list(); // à modifier
        },
        'trajet' => function () { // à modifier
            $api = new TrajetApi(); // à modifier
            //$api->list(); // à modifier
        }

    ],
    'PUT' => [
        'trajet' => function () {
            $api = new TrajetApi();
            //$api->list(); // fonction à définir qui liste les trajets
        },
        'trajet' => function () { // à modifier
            $api = new TrajetApi(); // à modifier
            //$api->list(); // à modifier
        },
        'trajet' => function () { // à modifier
            $api = new TrajetApi(); // à modifier
            //$api->list(); // à modifier
        }

    ],
    'DELETE' => [
        'trajet' => function () {
            $api = new TrajetApi();
            //$api->list(); // fonction à définir qui liste les trajets
        },
        'trajet' => function () { // à modifier
            $api = new TrajetApi(); // à modifier
            //$api->list(); // à modifier
        },
        'trajet' => function () { // à modifier
            $api = new TrajetApi(); // à modifier
            //$api->list(); // à modifier
        }

    ],
];

if (isset($routes[$httpMethod][$route])) {
    $routes[$httpMethod][$route]();
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Route non trouvé']);
}
