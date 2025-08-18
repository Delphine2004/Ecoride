<?php

// Rajouter les méthodes sp

namespace App\Models;


use App\Enum\UserStatus;
use InvalidArgumentException;


/** 
 * Cette classe représente un utilisateur dans la BDD.
 * Elle contient la validation des données 
 * ainsi que les actions réalisables par les utilisateurs en fonction de leur status.
 */

class User
{




    // déclaration des propriétés façon moderne
    function __construct(
        private ?int $id = null,
        private string $login,
        private string $password,
        private UserStatus $role,
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

    public function getRole(): UserStatus
    {
        return $this->role;
    }


    // ---------Les Setters ---------

    public function setLogin(string $login): self
    {
        if (empty(trim($login))) {
            throw new InvalidArgumentException("Le login ne peut pas être vide. ");
        }
        $this->login = trim($login);
        return $this;
    }

    public function setPassword(string $password, bool $isHashed = false): self
    {
        if (!$isHashed) {
            if (strlen(trim($password)) < 8) {
                // modifier la longueur pour 14 plus tard
                throw new InvalidArgumentException("Le mot de passe doit contenir au moins 8 caractéres.");
            }
            $password = password_hash(trim($password), PASSWORD_DEFAULT);
        }
        $this->password = $password;
        return $this;
    }

    public function setRole(UserStatus $role): self
    {
        $this->role = $role;
        return $this;
    }

    // ----- Autres méthodes de vérification -----

    public function verifyPassword(string $inputPassword): bool
    {
        return password_verify($inputPassword, $this->password);
    }

    public function hasRole(UserStatus $roleToCheck): bool
    {
        return $this->role === $roleToCheck;
    }


    // ----- Méthodes en fonction du status ----- 

    // Méthodes communes à tous les status
    // modifier mdp
    // modifier email
    // modifier info




}
