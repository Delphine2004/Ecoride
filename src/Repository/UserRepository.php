<?php

namespace App\Repositories;

use App\Repositories\BaseRepository;
use App\Models\User;
use App\Enum\UserRoles;
use InvalidArgumentException;
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

        $roles = [];
        if (!empty($data['roles'])) {
            // si le rôle ou les rôles sont presents
            $rolesNames = explode(',', $data['roles']);
            foreach ($rolesNames as $roleName) {
                $roles[] = UserRoles::from($roleName);
            }
        } else {
            // sinon recherche du rôle via SQL
            $roles = $this->getUserRoles((int)$data['user_id']);
        }
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
            roles: $roles,
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
        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'user_id'
        );

        // Chercher les éléments.
        $rows = parent::findAll($orderBy, $orderDirection, $limit, $offset);
        return array_map(fn($row) => $this->hydrateUser((array) $row), $rows);
    }

    /**
     * Récupére un utilisateur selon un champ spécifique.
     *
     * @param string $field
     * @param mixed $value
     * @return User|null
     */
    public function findUserByField(
        string $field,
        mixed $value
    ): ?User {
        $row = $this->findAllUsersByField($field, $value, limit: 1);
        return $row[0] ?? null;
    }

    /**
     * Récupére tous les utilisateurs selon un champ spécifique avec pagination et tri.
     *
     * @param string|null $field
     * @param mixed $value
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllUsersByField(
        ?string $field = null,
        mixed $value = null,
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
        // Group_concat pour éviter le n+1
        $sql = "SELECT u.*, GROUP_CONCAT(r.role_name) AS roles 
                FROM {$this->table} u
                LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.role_id
                ";

        if ($field && $value !== null) {
            if ($field === 'role_name') {
                $sql .= " WHERE r.role_name = :value";
            } elseif ($this->isAllowedField($field)) {
                $sql .= " WHERE u.$field = :value";
            } else {
                throw new InvalidArgumentException("Champs non autorisé: $field");
            }
        }


        // Groupement, tri et limite
        $sql .= "GROUP BY u.user_id 
                ORDER BY u.$orderBy $orderDirection 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;


        // Préparation de la requête
        $stmt = $this->db->prepare($sql);

        if ($field && $value !== null) {
            $stmt->bindValue(':value', $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
     * Récupére un utilisateur par son token.
     *
     * @param string $token
     * @return User|null
     */
    public function findUserByApiToken(string $token): ?User
    {
        return $this->findUserByField('api_token', $token);
    }

    /**
     * Récupére tous les utilisateurs selon un rôle.
     *
     * @param string $role
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllUsersByRole(
        string $role,
        string $orderBy = 'user_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->findAllUsersByField('role_name', $role, $orderBy, $orderDirection, $limit, $offset);
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
