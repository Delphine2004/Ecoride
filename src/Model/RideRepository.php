<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\Ride;
use App\Config\Database;
use PDO;
use PDOException;

class RideRepository extends Ride
{

    /**
     * @var string Le nom de la table en BDD
     */

    protected const TABLE = 'rides';

    // à Modifier 
    public function search(array $criteria)
    {
        // Exemple simple avec 2 critères
        $sql = "SELECT * FROM trajets WHERE ville_depart = :ville_depart AND ville_arrivee = :ville_arrivee";
        //$stmt = $this->db->prepare($sql);

        //$stmt->execute([            ':ville_depart' => $criteria['ville_Depart'] ?? '',             ':ville_arrivee' => $criteria['ville_arrivee'] ?? '', ]);

        //return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // inclure la requête dans la classe ride
    function searchRide()
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
        //$db = (new Database())->getInstance();

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


        //$stmt = $db->prepare($query);
        //$stmt->execute($params);
        //$trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Réponse JSON
        //header('Content-Type: application/json');
        //echo json_encode($trajets);
    }



    function addRide() {}
}
