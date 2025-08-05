<?php
class User
{
    private string $id; // utilisation de l'identifiant UUID
    private string $login;
    private string $password;
    private array $role = []; // en tableau car multi rôle possible

    function __construct(string $id, string $login, string $password, array $role)
    {
        $this->id = $id;
        $this->login = $login;
        $this->password = $password;
        $this->role = $role;
    }



    // Encapsulation des propriétés privées dans des getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getRole(): array
    {
        return $this->role;
    }

    public function verifyPassword(string $inputPassword): bool
    {
        return password_verify($inputPassword, $this->password);
    }

    public function hasRole(string $roleTocheck)
    {
        return in_array($roleTocheck, $this->role);
    }
}
