<?php

namespace App\Repositories;

use App\Repository\BaseModel;
use App\Models\User; // Utile si le repository hydrate les objets au lieu de tableaux
use App\Enum\UserRoles;
use PDO;
use InvalidArgumentException;

// pas besoin de database car instancié par BaseModel
// PDOExeption Utilise si try et catch mais pas necessaire car utilisé dans DataBase


/**
 * Cette classe gére la correspondance entre un utilisateur et la BDD.
 */

class UserRepository extends BaseModel
{
    /**
     * @var string Le nom de la table en BDD
     */
    protected string $table = 'users';

    protected string $primaryKey = 'user_id'; // Utile car utiliser dans BaseModel

    private array $allowedFields = ['user_id', 'last_name', 'first_name', 'email', 'user_name', 'phone', 'address', 'city', 'zip_code', 'picture', 'licence_no', 'credit', 'api_token'];

    public function __construct(PDO $db)
    {
        parent::__construct($db);
    }



    /**
     * Hydrate un tableau BDD en objet User
     *
     * @param array $data
     * @return User
     */
    private function hydrateUser(array $data): User
    {
        $user = new User(
            id: (int)$data['user_id'],
            lastName: $data['last_name'],
            firstName: $data['first_name'],
            email: $data['email'],
            password: $data['password'],
            isHashed: true,
            userName: $data['user_name'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            zipCode: $data['zip_code'] ?? null,
            uriPicture: $data['picture'] ?? null,
            licenceNo: $data['licence_no'] ?? null,
            credit: (int)$data['credit'] ?? null,
            apiToken: $data['api_token'] ?? null,
            roles: $this->getUserRoles((int)$data['user_id']), // Récupération du rôle via la table pivot 
            createdAt: !empty($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : null,
            updatedAt: !empty($data['updated_at']) ? new \DateTimeImmutable($data['updated_at']) : null
        );


        return $user;
    }

    private function mapUserToArray(User $user): array
    {
        return [
            'last_name' => $user->getLastName(),
            'first_name' => $user->getFirstName(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'user_name' => $user->getUserName(),
            'phone' => $user->getPhone(),
            'address' => $user->getAddress(),
            'city' => $user->getCity(),
            'zip_code' => $user->getZipCode(),
            'picture' => $user->getUriPicture(),
            'licence_no' => $user->getLicenceNo(),
            'credit' => $user->getCredit(),
            'api_token' => $user->getApiToken()
        ];
    }

    // ------- Gestion des rôles ---------- 
    private function getUserRoles(int $userId): array
    {
        $sql = "SELECT r.role_name
        FROM user_roles ur
        INNER JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.user_id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $roles = [];

        foreach ($rows as $row) {
            $roles[] = UserRoles::from($row['role_name']);
        }

        // Ajouter le rôle Passager si absent
        if (!in_array(UserRoles::PASSAGER, $roles, true)) {
            $roles[] = UserRoles::PASSAGER;
        }

        return $roles;
    }


    // ------ Récupérations ------ 

    public function findUserById(int $id): ?User
    {
        $row = parent::findById($id);
        return $row ? $this->hydrateUser((array) $row) : null;
    }

    public function findAllUsers(): array
    {
        $rows = parent::findAll();
        return array_map(fn($row) => $this->hydrateUser((array) $row), $rows);
    }

    public function findUserByField(string $field, mixed $value): ?User
    {
        if (!in_array($field, $this->allowedFields)) {
            throw new InvalidArgumentException("Champ non autorisé ; $field");
        }

        $row = parent::findOneByField($field, $value);
        return $row ? $this->hydrateUser((array) $row) : null;
    }

    public function findAllUsersByField(string $field, mixed $value): array
    {
        if (!in_array($field, $this->allowedFields)) {
            throw new InvalidArgumentException("Champ non autorisé ; $field");
        }

        $rows = parent::findAllByField($field, $value);
        return array_map(fn($row) => $this->hydrateUser((array) $row), $rows);
    }

    //  ------ Récupérations scpécifiques ---------

    public function findUserByEmail(string $email): ?User
    {
        return $this->findUserByField('email', $email);
    }

    public function findUserByRole(UserRoles $role): array
    {
        $sql = "SELECT u.* FROM users u 
            INNER JOIN user_roles ur ON u.user_id = ur.user_id 
            INNER JOIN roles r ON ur.role_id = r.role_id 
            WHERE r.role_name =:role";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['role' => $role->value]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->hydrateUser((array) $row), $rows);
    }


    // ------ Mise à jour ------ 

    public function updateUser(User $user): bool
    {
        return $this->updateById($user->getId(), $this->mapUserToArray($user));
    }

    // ------ Insertion ------ 

    public function insertUser(User $user): int
    {
        return $this->insert($this->mapUserToArray($user));
    }


    // ------ Suppression ------ 

    public function deleteUser(int $id): bool
    {
        return $this->deleteById($id);
    }
}
