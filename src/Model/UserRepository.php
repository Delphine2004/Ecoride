<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\User; // Utile si le repository hydrate les objets au lieu de tableaux
use App\Enum\UserRoles;
use PDO;

use App\Config\Database; // pas besoin de database car instancié par BaseModel
use PDOException; // Utilise si try et catch mais pas necessaire car utilisé dans DataBase


/**
 * Cette classe gére la correspondance entre un utilisateur avec la BDD.
 */

class UserRepository extends BaseModel
{
    /**
     * @var string Le nom de la table en BDD
     */
    protected string $table = 'users';


    /**
     * Hydrate un tableau BDD en objet User
     *
     * @param array $data
     * @return User
     */
    private function hydrateUser(array $data): User
    {
        return new User(
            id: $data['user_id'] ?? null,
            lastName: $data['last_name'] ?? '',
            firstName: $data['first_name'] ?? '',
            email: $data['email'] ?? '',
            password: $data['password'] ?? '',
            isHashed: true,
            userName: $data['user_name'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            zipCode: $data['zip_code'] ?? null,
            uriPicture: $data['picture'] ?? null,
            licenceNo: $data['licence_no'] ?? null,
            credit: $data['credit'] ?? null,
            apiToken: $data['api_token'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            roles: $data['roles'] ?? [UserRoles::PASSAGER]
        );
    }


    /**
     * Trouver un utilisateur par son adresse email
     *
     * @param string $email
     * @return User|null
     */
    public function findUserByEmail(string $email): ?User
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->hydrateUser($data) : null;
    }

    /**
     * Trouver tous les utilisateurs par rôle
     *
     * @param string $role
     * @return array
     */
    public function findByRole(string $role): array
    {
        $sql =  "SELECT * FROM 
                {$this->table} u
                INNER JOIN roles r ON u.role_id = r.id
                WHERE r.name = :role";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['role' => $role]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => $this->hydrateUser($row), $rows);
    }

    /**
     * Trouver un utilisateur par un critère précis
     *
     * @param array $criteria
     * @return array
     */
    public function findUserByCriteria(array $criteria = []): array
    {

        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($criteria['last_name'])) {
            $sql .= " AND last_name LIKE :last_name";
            $params[':last_name'] = "%{$criteria['last_name']}%";
        }
        if (!empty($criteria['user_name'])) {
            $sql .= " AND user_name LIKE :user_name";
            $params[':user_name'] = "%{$criteria['user_name']}%";
        }
        if (!empty($criteria['city'])) {
            $sql .= " AND city LIKE :city";
            $params[':city'] = "%{$criteria['city']}%";
        }
        if (!empty($criteria['created_at'])) {
            $sql .= " AND created_at LIKE :created_at";
            $params[':created_at'] = "%{$criteria['created_at']}%";
        }
        if (!empty($criteria['updated_at'])) {
            $sql .= " AND updated_at LIKE :updated_at";
            $params[':updated_at'] = "%{$criteria['updated_at']}%";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(fn($row) => $this->hydrateUser($row), $rows);
    }
}
