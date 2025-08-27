<?php

namespace App\Repository;

use App\Config\Database;
use InvalidArgumentException;
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
     * Récupére tous les enregistrements avec pagination et tri.
     *
     * @param integer $limit
     * @param integer $offset
     * @param string|null $orderBy
     * @param string $orderDirection
     * @return array
     */
    public function findAll(int $limit = 50, int $offset = 0, ?string $orderBy = null, string $orderDirection = 'DESC'): array
    {
        $sql = "SELECT * FROM `{$this->table}`";

        if ($orderBy !== null && $this->isAllowedField($orderBy)) {
            $orderDirection = strtoupper($orderDirection) === 'ASC' ? 'ASC' : 'DESC';
            $sql .= " ORDER BY $orderBy $orderDirection";
        }
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

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
        $result = $this->findAllByField($field, $value);
        return $result[0] ?? null;
    }

    /**
     * Récupére tous les enregistrements en fonction d'un champ avec pagination et tri.
     *
     * @param string $field
     * @param mixed $value
     * @param integer $limit
     * @param integer $offset
     * @param string|null $orderBy
     * @param string $orderDirection
     * @return array
     */
    public function findAllByField(string $field, mixed $value, int $limit = 50, int $offset = 0, ?string $orderBy = null, string $orderDirection = 'DESC'): array
    {

        if (!$this->isAllowedField($field)) {
            throw new InvalidArgumentException("Champ non autorisé : $field");
        }

        $sql = "SELECT * FROM {$this->table} WHERE $field = :value";

        if ($orderBy !== null && $this->isAllowedField($orderBy)) {
            $orderDirection = strtoupper($orderDirection) === 'ASC' ? 'ASC' : 'DESC';
            $sql .= " ORDER BY $orderBy $orderDirection";
        }
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':value', $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

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
