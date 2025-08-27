<?php

namespace App\Repositories;

use App\Models\BaseModel;
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

    private array $allowedFields = [];

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
        return new User(
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
            roles: UserRoles::from($data['roles']) ?? [UserRoles::PASSAGER], // RAJOUTER VALEUR PAR DEAFUT
            createdAt: new \DateTimeImmutable($data['created_at']),
            updatedAt: new \DateTimeImmutable($data['updated_at'])
        );
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
            'api_token' => $user->getApiToken(),
            'roles' => $user->getRoles()
        ];
    }


    // ------ Récupération ------ 

    public function findUserById(int $id): ?User
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }
        return $this->hydrateUser($data);
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

    // ----------------------------------------------

    public function findUsersByEmail(string $email): array
    {
        return $this->findAllUsersByField('email', $email);
    }

    public function findUsersByRole(string $role): array
    {
        return $this->findAllUsersByField('role', $role);
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
