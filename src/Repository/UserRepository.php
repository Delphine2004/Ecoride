<?php

namespace App\Repository;

use App\Model\User;
use App\Enum\UserRoles;
use PDO;


/**
 * Cette classe gère la correspondance entre un utilisateur et la BDD.
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
        'login',
        'phone',
        'address',
        'city',
        'zip_code',
        'picture',
        'licence_no',
        'credits',
        'preferences'
    ];

    public function __construct(
        ?PDO $db = null
    ) {
        parent::__construct(User::class, $db);
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
            'last_name' => $user->getUserLastName(),
            'first_name' => $user->getUserFirstName(),
            'email' => $user->getUserEmail(),
            'password' => $user->getUserPassword(),
            'login' => $user->getUserlogin(),
            'phone' => $user->getUserPhone(),
            'address' => $user->getUserAddress(),
            'city' => $user->getUserCity(),
            'zip_code' => $user->getUserZipCode(),
            'picture' => $user->getUserUriPicture(),
            'licence_no' => $user->getUserLicenceNo(),
            'credits' => $user->getUserCredits(),
            'preferences' => json_encode($user->getUserPreference(), JSON_THROW_ON_ERROR)
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
     * Récupère un objet User par son id.
     *
     * @param integer $userId
     * @return User|null
     */
    public function findUserById(int $userId): ?User
    {
        return parent::findById($userId);
    }

    /**
     * Récupère un objet User selon un ou plusieurs champs spécifiques.
     *
     * @param array $criteria
     * @return User|null
     */
    public function findUserByFields(
        array $criteria = []
    ): ?User {
        // Vérifie si chaque champ est autorisé.
        foreach ($criteria as $field => $value) {
            if (!$this->isAllowedField($field)) {
                return null;
            }
        }

        // Chercher l'élément
        $row = $this->findAllUsersByFields($criteria, limit: 1);
        return $row[0] ?? null;
    }

    /**
     * Récupère la liste des objets User selon un ou plusieurs champs spécifiques avec tri et pagination.
     *
     * @param array $criteria
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllUsersByFields(
        array $criteria = [],
        ?string $orderBy = null,
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        // Vérifie si chaque champ est autorisé.
        foreach ($criteria as $field => $value) {
            if (!$this->isAllowedField($field)) {
                return [];
            }
        }

        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'user_id'
        );

        return parent::findAllByFields($criteria, $orderBy, $orderDirection, $limit, $offset);
    }

    /**
     * Récupère la liste brute des utilisateurs selon un champ spécifique avec tri et pagination.
     *
     * @param array $criteria
     * @param string|null $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function fetchAllUsersRowsByFields(
        array $criteria = [],
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
        return parent::findAllByFields($criteria, $orderBy, $orderDirection, $limit, $offset);
    }


    //  ------ Récupérations d'objet ---------

    /**
     * Récupère un objet User par son email.
     *
     * @param string $email
     * @return User|null
     */
    public function findUserByEmail(string $email): ?User
    {
        return $this->findUserByFields(['email' => $email]);
    }

    /**
     * Récupère un objet User par son login.
     *
     * @param string $login
     * @return User|null
     */
    public function findUserByLogin(string $login): ?User
    {
        return $this->findUserByFields(['login' => $login]);
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
     * Insère un utilisateur dans la BD.
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
