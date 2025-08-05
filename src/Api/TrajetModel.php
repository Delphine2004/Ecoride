<?php

namespace Src\Model;

use PDO;

class TrajetModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new \Database())->getConnection();
    }

    public function search(array $criteria): array
    {
        // Exemple simplifié, faire des requêtes préparées en vrai !
        $sql = "SELECT * FROM trajets WHERE ville_depart = :ville_depart AND ville_arrivee = :ville_arrivee";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':ville_depart' => $criteria['ville_Depart'],
            ':ville_arrivee' => $criteria['ville_arrivee']
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
