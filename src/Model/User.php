<?php


namespace App\Models;

use App\Models\BaseModel;
use InvalidArgumentException;


/** 
 * Cette classe représente un utilisateur dans la BDD.
 * Elle contient seulement la validation des données.
 */

class User extends BaseModel
{

    /**
     * @var string Le nom de la table en BDD
     */

    protected string $table = 'users';



    function __construct(
        private ?int $id = null, // utilisation de l'identifiant UUID
        private string $login,
        private string $password,
        private array $role = [], // en tableau car multi rôle possible
    ) {
        // Affectation avec valisation
        $this->setLogin($login)->setPassword($password)->setRole($role);
    }

    // ---------Les Getters ---------

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getLogin(): string
    {
        return $this->login;
    }

    public function getPassword(): string
    {
        return $this->password;
    }


    public function getRole(): array
    {
        return $this->role;
    }


    // ---------Les Setters ---------

    public function setLogin(string $login): self
    {
        if (empty(trim($login))) {
            throw new InvalidArgumentException("Le login ne peut pas être vide. ");
        }
        $this->login = $login;
        return $this;
    }

    public function setPassword(string $password): self
    {
        if (strlen(trim($password < 8))) {
            // modifier la longueur pour 14 plus tard
            throw new InvalidArgumentException("Le mot de passe doit contenir au moins 14 caractéres.");
        }
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    public function setRole(array $roles): self
    {
        foreach ($roles as $role) {
            if (!is_string($role)) {
                throw new InvalidArgumentException("Chaque rôle doit être une chaine de caractères.");
            }
        }
        $this->role = $roles;
        return $this;
    }

    // ----- Autres méthodes -----

    public function verifyPassword(string $inputPassword): bool
    {
        return password_verify($inputPassword, $this->password);
    }

    public function hasRole(string $roleToCheck): bool
    {
        return in_array($roleToCheck, $this->role);
    }
}
