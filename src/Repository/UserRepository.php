<?php

namespace App\Repositories;

use App\Repository\BaseRepository;
use App\Models\User; // Utile si le repository hydrate les objets au lieu de tableaux
use App\Enum\UserRoles;
use PDO;
use InvalidArgumentException;

// pas besoin de database car instancié par BaseRepository
// PDOExeption Utilise si try et catch mais pas necessaire car utilisé dans DataBase


/**
 * Cette classe gére la correspondance entre un utilisateur et la BDD.
 */

class UserRepository extends BaseRepository
{
    /**
     * @var string Le nom de la table en BDD
     */
    protected string $table = 'users';

    protected string $primaryKey = 'user_id'; // Utile car utiliser dans BaseRepository

    private array $allowedFields = ['user_id', 'last_name', 'first_name', 'email', 'user_name', 'phone', 'address', 'city', 'zip_code', 'picture', 'licence_no', 'credit', 'api_token'];

    public function __construct(PDO $db)
    {
        parent::__construct($db);
    }



    /**
     * Remplit un objet User avec les données de la table users.
     *
     * @param array $data
     * @return User
     */
    private function hydrateUser(array $data): User
    {
        $user = new User(
            userId: (int)$data['user_id'],
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
            roles: $this->getUserRoles((int)$data['user_id']),
            createdAt: !empty($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : null,
            updatedAt: !empty($data['updated_at']) ? new \DateTimeImmutable($data['updated_at']) : null
        );


        return $user;
    }

    /**
     * Transforme User en tableau pour insert et update.
     *
     * @param User $user
     * @return array
     */
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
    /**
     * Obtenir le rôle d'un utilisateur via la table pivot.
     *
     * @param integer $userId
     * @return array
     */
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

    /**
     * Récupére un utilisateur par son id.
     *
     * @param integer $userId
     * @return User|null
     */
    public function findUserById(int $userId): ?User
    {
        $row = parent::findById($userId);
        return $row ? $this->hydrateUser((array) $row) : null;
    }

    /**
     * Récupére tous les utilisateurs avec pagination et tri.
     *
     * @param integer $limit
     * @param string|null $orderBy
     * @param string $orderDirection
     * @return array
     */
    public function findAllUsers(?string $orderBy = null, string $orderDirection = 'DESC', int $limit = 50, int $offset = 0): array
    {
        $rows = parent::findAll($orderBy, $orderDirection, $limit, $offset);
        return array_map(fn($row) => $this->hydrateUser((array) $row), $rows);
    }

    /**
     * Récupére un utilisateur selon un champs spécifique.
     *
     * @param string $field
     * @param mixed $value
     * @return User|null
     */
    public function findUserByField(string $field, mixed $value): ?User
    {
        if (!in_array($field, $this->allowedFields)) {
            throw new InvalidArgumentException("Champ non autorisé ; $field");
        }

        $row = parent::findOneByField($field, $value);
        return $row ? $this->hydrateUser((array) $row) : null;
    }

    /**
     * Récupére tous les utilisateurs selon un champ spécifique avec pagination et tri.
     *
     * @param string $field
     * @param mixed $value
     * @param integer $limit
     * @param string|null $orderBy
     * @param string $orderDirection
     * @return array
     */
    public function findAllUsersByField(string $field, mixed $value, ?string $orderBy = null, string $orderDirection = 'DESC', int $limit = 50, int $offset = 0): array
    {
        if (!in_array($field, $this->allowedFields)) {
            throw new InvalidArgumentException("Champ non autorisé ; $field");
        }

        $rows = parent::findAllByField($field, $value, $orderBy, $orderDirection, $limit, $offset);
        return array_map(fn($row) => $this->hydrateUser((array) $row), $rows);
    }


    //  ------ Récupérations spécifiques ---------

    /**
     * Récupére un utilisateur par son email.
     *
     * @param string $email
     * @return User|null
     */
    public function findUserByEmail(string $email): ?User
    {
        return $this->findUserByField('email', $email);
    }

    /**
     * Récupére tous les utilisateurs par leur rôle.
     *
     * @param UserRoles $role
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @return array
     */
    public function findUsersByRole(UserRoles $role, string $orderBy = 'role_id', string $orderDirection, int $limit = 20): array
    {
        // Sécurisation du champ ORDER BY
        if (!in_array($orderBy, $this->allowedFields, true)) {
            $orderBy = 'role_id';
        }

        // Sécurisation du champ direction
        $orderDirection = strtoupper($orderDirection);
        if (!in_array($orderDirection, ['ASC', 'DESC'], true)) {
            $orderDirection = 'DESC';
        }

        // Construction du SQL
        $sql = "SELECT u.* FROM users u 
            INNER JOIN user_roles ur ON u.user_id = ur.user_id 
            INNER JOIN roles r ON ur.role_id = r.role_id 
            WHERE r.role_name =:role";

        $params = ['role' => $role];

        // Tri et limite
        $sql .= " ORDER BY r.$orderBy $orderDirection LIMIT :limit";

        // Préparation de la requête
        $stmt = $this->db->prepare($sql);


        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->hydrateUser((array) $row), $rows);
    }


    //------------------------------------------


    // ------ Mise à jour ------ 
    /**
     * Met à jour les données concernant un utilisateur.
     *
     * @param User $user
     * @return boolean
     */
    public function updateUser(User $user): bool
    {
        return $this->updateById($user->getUserId(), $this->mapUserToArray($user));
    }

    // ------ Insertion ------ 
    /**
     * Insert un utilisateur dans la BD.
     *
     * @param User $user
     * @return integer
     */
    public function insertUser(User $user): int
    {
        return $this->insert($this->mapUserToArray($user));
    }


    // ------ Suppression ------ 
    /**
     * Supprime un utilisateur de la BD.
     *
     * @param integer $userId
     * @return boolean
     */
    public function deleteUser(int $userId): bool
    {
        return $this->deleteById($userId);
    }
}
