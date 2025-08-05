<?php

// src/Routes/api.php

use App\Core\Router;

// Exemple d'API pour les livres
Router::addApiRoute('GET', '/api/v1/livres', function () {
    // Ici, vous iriez chercher les livres depuis une base de données
    // Pour l'exemple, nous retournons des données statiques
    return [
        ['id' => 1, 'titre' => 'Le Seigneur des Anneaux', 'auteur' => 'J.R.R. Tolkien'],
        ['id' => 2, 'titre' => '1984', 'auteur' => 'George Orwell']
    ];
});

// Exemple d'API pour ajouter un livre (méthode POST)
Router::addApiRoute('POST', '/api/v1/livres', function () {
    $data = json_decode(file_get_contents('php://input'), true);
    // Ici, vous valideriez les données et les ajouteriez à la BDD
    if (isset($data['titre']) && isset($data['auteur'])) {
        // Simuler l'ajout et retourner un ID
        return ['status' => 'success', 'message' => 'Livre ajouté', 'id' => uniqid(), 'data' => $data];
    }
    return ['status' => 'error', 'message' => 'Données manquantes'];
});

// Exemple d'API pour obtenir un utilisateur par ID (requiert des routes dynamiques plus avancées)
// Pour l'instant, on fait une route statique, mais on devrait chercher un paramètre
Router::addApiRoute('GET', '/api/v1/utilisateur/1', function () {
    return ['id' => 1, 'nom' => 'Alice', 'email' => 'alice@example.com'];
});
