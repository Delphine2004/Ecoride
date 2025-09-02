<?php

namespace App\Repositories;

use App\Repositories\BaseRepository;
use App\Models\User;
use App\Enum\UserRoles;
use PDO;


/**
 * Cette classe gére la correspondance entre un utilisateur et la BDD.
 */

class UserRepository extends BaseRepository
{

    protected string $table = 'users';
    protected string $primaryKey = 'user_id';
    private array $allowedFields = [
        'user_id',
        'last_name',
        'first_name',
        'email',
        'user_name',
        'phone',
        'address',
        'city',
        'zip_code',
        'picture',
        'licence_no',
        'credit',
        'api_token'
    ];

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
    public function hydrateUser(array $data): User
    {
        return new User(
            userId: (int)$data['user_id'],
            lastName: $data['last_name'],
            firstName: $data['first_name'],
            email: $data['email'],
            password: $data['password'], // ok si vient de la BD donc déjà hashé
            isHashed: true,
            userName: $data['user_name'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            zipCode: $data['zip_code'] ?? null,
            uriPicture: $data['picture'] ?? null,
            licenceNo: $data['licence_no'] ?? null,
            credit: isset($data['credit']) ? (int)$data['credit'] : null,
            apiToken: $data['api_token'] ?? null,
            roles: $this->getUserRoles((int)$data['user_id']),
            createdAt: !empty($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : null,
            updatedAt: !empty($data['updated_at']) ? new \DateTimeImmutable($data['updated_at']) : null
        );
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

    /**
     * Surcharge la fonction isAllowedField de BaseRepository
     *
     * @param string $field
     * @return boolean
     */
    protected function isAllowedField(string $field): bool
    {
        return in_array($field, $this->allowedFields, true);
    }

    // ------- Gestion des rôles ---------- 

    // A UTILISER DANS BOOKING
    /**
     * Obtenir le rôle d'un utilisateur via la table pivot.
     *
     * @param integer $userId
     * @return array
     */
    public function getUserRoles(int $userId): array
    {
        $sql = "SELECT r.role_name
        FROM user_roles ur
        INNER JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
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
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllUsers(
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
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
    public function findUserByField(
        string $field,
        mixed $value
    ): ?User {
        // Vérifie si le champ est autorisé.
        if (!$this->isAllowedField($field)) {
            return null;
        }

        $row = parent::findOneByField($field, $value);
        return $row ? $this->hydrateUser((array) $row) : null;
    }

    /**
     * Récupére tous les utilisateurs selon un champ spécifique avec pagination et tri.
     *
     * @param string $field
     * @param mixed $value
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllUsersByField(
        string $field,
        mixed $value,
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        // Vérifie si le champ est autorisé.
        if (!$this->isAllowedField($field)) {
            return [];
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


    // Récupére tous les utilisateurs en fonction d'un rôle précis. - A VERIFIER
    public function findAllUsersByRole(
        UserRoles $role,
        string $orderBy = 'user_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {

        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'user_id'
        );

        // Construction du SQL
        $sql = "SELECT u.* 
                FROM {$this->table} u 
                INNER JOIN user_roles ur ON u.user_id = ur.user_id 
                INNER JOIN roles r ON ur.role_id = r.role_id 
                WHERE r.role_name =:role
            ";

        // Tri et limite
        $sql .= " ORDER BY u.$orderBy $orderDirection 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;


        // Préparation de la requête
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':role', $role->value, PDO::PARAM_STR);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->hydrateUser((array) $row), $rows);
    }

    // Récupére tous les utilisateurs avec leurs rôles - A VERIFIER
    public function findAllUsersWithRoles(
        string $orderBy = 'role_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'user_id'
        );

        // Construction du SQL
        $sql = "SELECT 
                    u.user_id, u.last_name, u.first_name, 
                    GROUP_CONCAT(r.role_name)
                FROM {$this->table} u
                JOIN user_roles ur ON u.user_id = ur.user_id
                JOIN roles r ON ur.role_id = r.role_id
                GROUP BY u.user_id";

        // Tri et limite
        $sql .= " ORDER BY u.$orderBy $orderDirection 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;


        // Préparation de la requête
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->hydrateUser((array) $row), $rows);
    }

    // Récupére tous les utilisateurs avec leurs voitures  - A VERIFIER
    public function findAllUsersWithCars(
        int $ownerId,
        string $orderBy = 'user_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {

        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'car_id'
        );

        // Construction du SQL
        $sql = "SELECT 
                    u.user_id, u.last_name, u.first_name,
                    c.car_id, c.car_brand, c.car_model, c.car_power
                FROM {$this->table} u
                LEFT JOIN cars c ON u.user_id = c.user_id
                WHERE user_id = :userId";

        $sql .= " ORDER BY c.$orderBy $orderDirection 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;


        // Préparation de la requête
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':userId', $ownerId, PDO::PARAM_INT);
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
