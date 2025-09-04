<?php

namespace App\Controllers;

use App\Security\Validator;
use App\Utils\Response;
use App\Config\Database;
use PDO;


/*
function handleRequest()
{
    $method = $_SERVER['REQUEST_METHOD'];

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
}
        //header('Content-Type: application/json');
        //echo json_encode($results);
    } else {        http_response_code(405); // méthode non autorisée        echo json_encode(['error' => 'Méthode non autorisée']);    }

*/