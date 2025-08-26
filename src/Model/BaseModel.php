<?php

namespace App\Models;

use App\Config\Database;
use PDO;

abstract class BaseModel
{

    /**
     * @var PDO l'instance de connexion à la base de données
     */

    protected PDO $db;

    /**
     * 
     * @var string le nom de la table associé au model
     */

    protected string $table;

    protected string $primaryKey = 'id';

    protected string $entityClass;



    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Récupére tous les enregistrements d'une table en objets.
     */
    public function getAll(): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_CLASS, $this->entityClass);
    }

    /**
     * Récupére un enregistrement par son id en objet.
     */
    public function getById(int $id): ?object
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, $this->entityClass); // confirme comment PDO va transformer les resultats
        $result = $stmt->fetch();
        return $result ?: null;
    }


    /**
     * Supprimer un enregistrement par son id
     */

    public function deleteById(int $id): bool
    {
        $sql = "DELETE FROM `{$this->table}` WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }


    /**
     * Modifier un enregistrement par son id
     */

    public function update(int $id, array $data): bool
    {
        // évite l'erreur sql en cas de donnée vide
        if (empty($data)) {
            return false;
        }

        $setClause = implode(", ", array_map(fn($col) => "$col = :$col", array_keys($data)));
        $data['id'] = $id;

        $sql = "UPDATE `{$this->table}` SET $setClause WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }


    /**
     * Inserer un enregistrement dans une table
     */

    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeHolders = array_map(fn($col) => ":$col", $columns);

        $sql = "INSERT INTO `{$this->table}` (" . implode(", ", $columns) . ") 
                VALUES (" . implode(", ", $placeHolders) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }
}
