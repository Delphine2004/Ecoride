<?php

// src/Core/Router.php
namespace App\Core;


class Router
{
    // Tableau pour stocker les routes des pages web (rendu HTML)
    private static $webRoutes = [];
    // Tableau pour stocker les routes d'API (retour JSON)
    private static $apiRoutes = [];

    /**
     * Ajoute une route pour une page web (rendu HTML).
     *
     * @param string $method La méthode HTTP (GET, POST, etc.)
     * @param string $path Le chemin de l'URL (ex: '/', '/produits')
     * @param string $filePath Le chemin du fichier PHP à inclure
     */
    public static function addWebRoute(string $method, string $path, string $filePath): void
    {
        self::$webRoutes[$method][$path] = $filePath;
    }

    /**
     * Ajoute une route pour une API (retour JSON).
     *
     * @param string $method La méthode HTTP (GET, POST, etc.)
     * @param string $path Le chemin de l'URL (ex: '/api/livres')
     * @param callable $callback La fonction à exécuter pour cette route d'API
     */
    public static function addApiRoute(string $method, string $path, callable $callback): void
    {
        self::$apiRoutes[$method][$path] = $callback;
    }

    /**
     * Gère la requête entrante et trouve la route correspondante.
     *
     * @param string $requestUri L'URI de la requête (ex: '/produits', '/api/livres')
     * @param string $requestMethod La méthode HTTP (GET, POST, etc.)
     */
    public static function handleRequest(string $requestUri, string $requestMethod): void
    {
        // 1. D'abord, essayer de faire correspondre une route API
        if (isset(self::$apiRoutes[$requestMethod])) {
            foreach (self::$apiRoutes[$requestMethod] as $routePath => $callback) {
                // Pour l'instant, un match exact. Plus tard, on pourrait faire des regex pour des paramètres.
                if ($requestUri === $routePath) {
                    header('Content-Type: application/json'); // Indique que la réponse est du JSON
                    echo json_encode(call_user_func($callback)); // Exécute le callback et encode le retour en JSON
                    return; // Arrêter l'exécution après avoir trouvé une route
                }
            }
        }

        // 2. Si pas de route API, essayer de faire correspondre une route Web
        if (isset(self::$webRoutes[$requestMethod])) {
            foreach (self::$webRoutes[$requestMethod] as $routePath => $filePath) {
                if ($requestUri === $routePath) {
                    // Inclure le header
                    require_once LAYOUT_PATH . 'header.php';

                    // Inclure le contenu de la page
                    if (file_exists($filePath)) {
                        require_once $filePath;
                    } else {
                        // Gérer le cas où le fichier n'existe pas malgré la route définie
                        self::showNotFound();
                        return;
                    }

                    // Inclure le footer
                    require_once LAYOUT_PATH . "footer.php";
                    return; // Arrêter l'exécution après avoir trouvé une route
                }
            }
        }

        // 3. Si aucune route ne correspond, afficher une page 404
        self::showNotFound();
    }

    /**
     * Affiche une page 404 (non trouvée).
     */
    private static function showNotFound(): void
    {
        header("HTTP/1.0 404 Not Found");
        require_once LAYOUT_PATH . 'header.php'; // Inclure le header
        // Vous pouvez créer un fichier spécifique pour la 404
        echo "<h1>Erreur 404 - Page non trouvée</h1><p>Désolé, la page que vous recherchez n'existe pas.</p>";
        require_once LAYOUT_PATH . "footer.php"; // Inclure le footer
    }
}
