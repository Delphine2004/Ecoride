<?php

// Inclusion du fichier de config
require_once __DIR__ . '/../src/Config/config.php';

// Inclusion du fichier du routeur
require_once CORE_PATH . 'Router.php';

// Importation de la classe Router
use App\Core\Router;

// Inclusion des fichiers de définition de routes
require_once ROUTES_PATH . 'web.php';
require_once ROUTES_PATH . 'api.php';



// Récupérer l'URI demandée par l'utilisateur (le chemin, ex: /produits, /api/livres)
// On retire la partie du répertoire de base si l'application n'est pas à la racine du domaine
// Exemple: si l'app est dans /monapp/, on veut enlever /monapp du début de l'URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = dirname($_SERVER['SCRIPT_NAME']);

if ($scriptName !== '/' && strpos($requestUri, $scriptName) === 0) {
  $requestUri = substr($requestUri, strlen($scriptName));
}

// Nettoyer l'URI (enlever les slashes en début et fin, sauf si c'est la racine '/')
$requestUri = trim($requestUri, '/');
if (empty($requestUri)) {
  $requestUri = '/'; // Représente la racine
} else {
  $requestUri = '/' . $requestUri; // Ajouter un slash de début pour la cohérence
}


// Récupérer la méthode HTTP (GET, POST, PUT, DELETE, etc.)
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Démarrer le processus de routage
// Nous allons passer l'URI et la méthode au routeur
Router::handleRequest($requestUri, $requestMethod);
