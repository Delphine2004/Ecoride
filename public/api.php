<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Config\Config;
use FastRoute\RouteCollector;
use FastRoute\Dispatcher;
use App\Repository\CarRepository;
use App\Repository\RideRepository;
use App\Repository\BookingRepository;
use App\Repository\UserRepository;
use App\Service\CarService;
use App\Service\RideService;
use App\Security\AuthService;
use App\Controller\CarController;
use App\Controller\RideController;
use App\Controller\UserController;
use App\Service\NotificationService;


// Activer le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//----------Configuration PHP --------------
//Démarrer une session ou reprend la session existante
session_start();

// Charger les variables d'environnement
Config::load();

// Instancier les dépendances
$carRepository = new CarRepository();
$rideRepository = new RideRepository();
$userRepository = new UserRepository();
$bookingRepository = new BookingRepository();

$notificationService = new NotificationService();

$authService = new AuthService($userRepository);


$carService = new CarService($carRepository, $authService);
$rideService = new RideService($rideRepository, $bookingRepository, $authService, $carService, $notificationService);


$carController = new CarController($carService, $authService);
$rideController = new RideController($rideService, $authService);



// Création du dispatcher avec la définition des routes
$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) use ($carController, $rideController) {
    // ---------------------CAR------------------------------------
    $r->addRoute('POST', '/cars/{car_id}/created', [$carController, 'createCar']);
    $r->addRoute('DELETE', '/cars/{car_id}', [$carController, 'deleteCar']);
    $r->addRoute('GET', '/users/{car_id}/cars', [$carController, 'listCarsbyDriver']);

    // --------------------RIDE---------------------------------------
    $r->addRoute('POST', '/rides/{ride_id}/created', [$rideController, 'createRide']);
    $r->addRoute('PUT', '/rides/{ride_id}/cancel', [$rideController, 'cancelRide']);

    $r->addRoute('POST', '/rides/{ride_id}/start', [$rideController, 'startRide']);
    $r->addRoute('POST', '/rides/{ride_id}/finalize', [$rideController, 'finalizeRide']);


    $r->addRoute('GET', '/rides/{ride_id}', [$rideController, 'getRideWithPassengers']);
    $r->addRoute('GET', '/api.php/rechercher', [$rideController, 'listRidesByDateAndPlaces']);

    // user

    // user

    // staff
});

// Récupérer les informations de la requête
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];




// Retirer les paramètres de la chaîne de requête (ex: ?foo=bar)
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

// Lancer le dispatcher
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        // Gérer les routes non trouvées (404)
        http_response_code(404);
        echo json_encode(['error' => 'Route non trouvée.']);
        break;
    case Dispatcher::METHOD_NOT_ALLOWED:
        // Gérer les méthodes non autorisées (405)
        $allowedMethods = $routeInfo[1];
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée.']);
        break;
    case Dispatcher::FOUND:
        // Route trouvée, appeler la méthode du contrôleur
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        [$controller, $method] = $handler;

        // Appeler la méthode avec les paramètres de l'URI
        $controller->$method(...array_values($vars));
        break;
}
