<?php
// src/Model/TrajetModel.php

namespace Src\Model;

require_once __DIR__ . '/../../src/Database.php';

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
        // Exemple simple avec 2 critères
        $sql = "SELECT * FROM trajets WHERE ville_depart = :ville_depart AND ville_arrivee = :ville_arrivee";
        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':ville_depart' => $criteria['ville_Depart'] ?? '',
            ':ville_arrivee' => $criteria['ville_arrivee'] ?? '',
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
