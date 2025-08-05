<?php

// Fichier non fini!!!!!

class Database
{
    private string $host = 'localhost';
    private string $db_name = 'ecoride_db';
    private string $username = 'root'; // utilisateur par defaut wamp
    private string $password = ""; // mdp par defaut wamp
    public ?PDO $conn = null;

    public function getConnection(): ?PDO
    {
        // le if évite les connexions inutiles
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8";
                $this->conn = new PDO($dsn,  $this->username, $this->password);
                // Configuration du mode d'erreur de pdo sur exeption pour mieux gérer les erreurs
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $exception) {

                // supprimer le echo et logger les erreurs dans un fichier car risque de fuite de données sensibles
                echo "Erreur de connexion : " . $exception->getMessage();
                // à activer en prod - créer le dossier aussi :
                // error_log($exception->getMessage(), 3, __DIR__ . '/../logs/db_error.log');
            }
        }
        return $this->conn;
    }
}
