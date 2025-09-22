<?php

namespace App\Config;

use Dotenv\Dotenv;

class Config
{
    /** 
     * @param string $path le chemin vers le dossier contenant le fichier .env
     */

    public static function load(?string $path = null): void
    {
        if ($path === null) {
            $path = realpath(__DIR__ . '/../../') . '/';
        }
        //on verifie si le fichier .env existe avant de tenter de le charger
        if (file_exists($path . '.env')) {
            $dotenv = Dotenv::createImmutable($path);
            $dotenv->load();
        }
    }

    /**
     * @param string $key le nom de la variable 
     * @param mixed $default une valeur par défaut à retourner si la variable n'existe pas
     * @return mixed la valeur de la variable ou la valeur par defaut
     */

    public static function get(string $key, $default = null)
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Indique si une variable d'environnement est définie
     * 
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset($_ENV[$key]) || getenv($key) !== false;
    }
}
