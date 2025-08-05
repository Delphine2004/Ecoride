<?php

function rechercherCovoiturage()
{
    // Lire le JSON envoyé
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    // Sécurité : vérifier si les champs existent
    $departurePlace = $data['departure_place'] ?? null;
    $arrivalPlace = $data['arrival_place'] ?? null;
    $departureDate = $data['departure_date'] ?? null;
    $availableSeats = $data['available_seats'] ?? null;

    // Connexion à la BDD
    require_once CORE_PATH . 'Database.php';
    $db = (new Database())->getConnection();

    // Préparation de la requête SQL (exemple simple)
    $query = "SELECT * FROM trajets WHERE 1=1";
    $params = [];

    if ($departurePlace) {
        $query .= " AND departure_place LIKE :departure_place";
        $params[':departure_place'] = "%$departurePlace%";
    }
    if ($arrivalPlace) {
        $query .= " AND arrival_place LIKE :arrival_place";
        $params[':arrival_place'] = "%$arrivalPlace%";
    }
    if ($departureDate) {
        $query .= " AND departure_date = :departure_date";
        $params[':departure_date'] = $departureDate;
    }
    if ($availableSeats >= 1) {
        $query .= "WHERE available_seats >= 1";
        $params[':available_seats'] = $availableSeats;
    }


    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Réponse JSON
    header('Content-Type: application/json');
    echo json_encode($trajets);
}
