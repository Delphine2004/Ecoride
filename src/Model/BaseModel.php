<?php

namespace App\Models;

use App\Config\Database;
use InvalidArgumentException;
use PDO;
use PhpParser\Node\Expr\Cast\Object_;

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
     * Récupére un enregistrement par son id.
     *
     * @param integer $id
     * @return object|null
     */
    public function findById(int $id): ?object
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, $this->entityClass); // confirme comment PDO va transformer les resultats
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Récupére tous les enregistrements.
     *
     * @return array
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_CLASS, $this->entityClass);
    }

    /**
     * Récupere un seul enregistrement en fonction d'un champ
     *
     * @param string $field
     * @param mixed $value
     * @return Object|null
     */
    public function findOneByField(string $field, mixed $value): ?Object
    {
        if (!$this->isAllowedField($field)) {
            throw new InvalidArgumentException("Champ non autorisé : $field");
        }
        $sql = "SELECT * FROM {$this->table} WHERE $field = :value LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['value' => $value]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, $this->entityClass);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Récupére tous les enregistrements en fonction d'un champ
     *
     * @param string $field
     * @param mixed $value
     * @return array
     */
    public function findAllByField(string $field, mixed $value): array
    {

        if (!$this->isAllowedField($field)) {
            throw new InvalidArgumentException("Champ non autorisé : $field");
        }

        $sql = "SELECT * FROM {$this->table} WHERE $field = :value";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['value' => $value]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, $this->entityClass);
    }




    /**
     * Modifier un enregistrement par son id
     *
     * @param integer $id
     * @param array $data
     * @return boolean
     */
    public function updateById(int $id, array $data): bool
    {
        // évite l'erreur sql en cas de donnée vide
        if (empty($data)) {
            return false;
        }

        $setClause = implode(", ", array_map(fn($col) => "$col = :$col", array_keys($data)));
        $data['id'] = $id;

        $sql = "UPDATE `{$this->table}` SET $setClause WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }


    /**
     * Inserer un enregistrement dans une table
     *
     * @param array $data
     * @return integer
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


    /**
     * Supprimer un enregistrement par son id
     *
     * @param integer $id
     * @return boolean
     */
    public function deleteById(int $id): bool
    {
        $sql = "DELETE FROM `{$this->table}` WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }


    /**
     * Vérifie si le champ est autorisé pour éviter les injections SQL.
     */

    protected function isAllowedField(string $field): bool
    {
        return true;
        // Par défaut, autorise tous les champs
        // Dans les repositories spécifiques, tu peux faire :
        // return in_array($field, ['nom_colonne1','nom_colonne2']);
    }
}
