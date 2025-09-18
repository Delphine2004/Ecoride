<?php

namespace App\Repository;

use App\Config\Database;
use InvalidArgumentException;
use PDO;


abstract class BaseRepository
{

    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected string $entityClass;


    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }



    /**
     * Récupère un enregistrement par son id.
     *
     * @param integer $id
     * @return object|null
     */
    public function findById(
        int $id
    ): ?object {
        $results = $this->findAllByFields([$this->primaryKey => $id], limit: 1);
        return $results[0] ?? null;
    }


    /**
     * Récupère tous les enregistrements avec 1 ou plusieurs champs avec tri et pagination.
     *
     * @param array $criteria
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllByFields(
        array $criteria = [],
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        // Construction dy SQL
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];


        // Construction dynamique des conditions
        foreach ($criteria as $field => $value) {
            if (!$this->isAllowedField($field)) {
                throw new InvalidArgumentException("Champ non autorisé : $field");
            }

            // Vérifie si between est dans le tableau de critére
            if (is_array($value) && isset($value['between']) && count($value['between']) === 2) {
                [$start, $end] = $value['between'];

                if ($start instanceof \DateTimeImmutable) {
                    $start = $start->format('Y-m-d H:i:s');
                }
                if ($end instanceof \DateTimeImmutable) {
                    $end = $end->format('Y-m-d H:i:s');
                }

                $sql .= " AND $field BETWEEN :{$field}_start AND :{$field}_end";
                $params[":{$field}_start"] = $start;
                $params[":{$field}_end"]   = $end;
            }
            // Vérifie si le champ est vide
            elseif ($value === null) {
                $sql .= " AND $field IS NULL";
            }
            // Vérifie si il y a d'autre champs
            else {
                $sql .= " AND $field = :$field";
                $params[":$field"] = $value instanceof \DateTimeInterface
                    ? $value->format('Y-m-d H:i:s')
                    : $value;
            }
        }

        // Tri
        if ($orderBy !== null && $this->isAllowedField($orderBy)) {
            $orderDirection = strtoupper($orderDirection) === 'ASC' ? 'ASC' : 'DESC';
            $sql .= " ORDER BY $orderBy $orderDirection";
        }
        // Limite
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;


        // Préparation de la requête
        $stmt = $this->db->prepare($sql);

        foreach ($params as $field => $value) {
            if (is_int($value)) {
                $stmt->bindValue($field, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($field, $value, PDO::PARAM_STR);
            }
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_CLASS, $this->entityClass);
    }


    //----------------------------------------------

    /**
     * Modifie un enregistrement par son id
     *
     * @param integer $id
     * @param array $data
     * @return boolean
     */
    public function updateById(
        int $id,
        array $data
    ): bool {
        // évite l'erreur sql en cas de donnée vide
        if (empty($data)) {
            throw new InvalidArgumentException("Aucune donnée fournie pour la mise à jour.");
        }

        $setClause = [];
        foreach ($data as $field => $value) {
            if (!$this->isAllowedField($field) || $field === $this->primaryKey) {
                continue;
            }
            $setClause[] = "$field = :$field";
        }

        if (empty($setClause)) {
            throw new InvalidArgumentException("Aucun champs valide à mettre à jour.");
        }

        // Construction du SQL
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE {$this->primaryKey} = :id";

        // Préparation de la requête
        $stmt = $this->db->prepare($sql);
        foreach ($data as $field => $value) {
            if ($this->isAllowedField($field) && $field !== $this->primaryKey) {
                if (is_bool($value)) {
                    $stmt->bindValue(":$field", $value, PDO::PARAM_BOOL);
                } elseif (is_int($value)) {
                    $stmt->bindValue(":$field", $value, PDO::PARAM_INT);
                } elseif ($value instanceof \DateTimeInterface) {
                    // On convertit en format SQL avant insertion
                    $stmt->bindValue(":$field", $value->format('Y-m-d H:i:s'), PDO::PARAM_STR);
                } else {
                    $stmt->bindValue(":$field", $value, PDO::PARAM_STR);
                }
            }
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }


    /**
     * Insère un enregistrement dans une table
     *
     * @param array $data
     * @return integer
     */
    public function insert(
        array $data
    ): int {
        // Vérification des champs
        $filtered = [];
        foreach ($data as $field => $value) {
            if ($this->isAllowedField($field)) {
                $filtered[$field] = $value;
            }
        }

        if (empty($filtered)) {
            throw new InvalidArgumentException("Aucune donnée valide pour l'insertion.");
        }

        $columns = array_keys($filtered);
        $placeHolders = array_map(fn($col) => ":$col", $columns);

        // Construction du sql
        $sql = "INSERT INTO `{$this->table}` (" . implode(", ", $columns) . ") 
                VALUES (" . implode(", ", $placeHolders) . ")";

        // Préparation de la requête
        $stmt = $this->db->prepare($sql);

        foreach ($filtered as $field => $value) {
            if (is_bool($value)) {
                $stmt->bindValue(":$field", $value, PDO::PARAM_BOOL);
            } elseif (is_int($value)) {
                $stmt->bindValue(":$field", $value, PDO::PARAM_INT);
            } elseif ($value instanceof \DateTimeInterface) {
                // On convertit en format SQL avant insertion
                $stmt->bindValue(":$field", $value->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            } else {
                $stmt->bindValue(":$field", $value, PDO::PARAM_STR);
            }
        }

        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }


    /**
     * Supprime un enregistrement par son id
     *
     * @param integer $id
     * @return boolean
     */
    public function deleteById(
        int $id
    ): bool {
        $sql = "DELETE FROM `{$this->table}` WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    //----------------------------------------------

    /**
     * Vérifie si le champ est autorisé pour éviter les injections SQL.
     */

    protected function isAllowedField(
        string $field
    ): bool {
        return true;
    }


    protected function sanitizeOrder(
        ?string $orderBy,
        string $orderDirection,
        string $defaultField
    ): array {

        // Validation du champ
        if (!$this->isAllowedField($orderBy ?? '')) {
            $orderBy = $defaultField;
        }

        //Validation de la direction
        $orderDirection = strtoupper($orderDirection);
        if (!in_array($orderDirection, ['ASC', 'DESC'], true)) {
            $orderDirection = 'DESC';
        }
        return [$orderBy, $orderDirection];
    }
}
