<?php

namespace App\DTO;

use InvalidArgumentException;

class CreateUserDTO
{
    public string $lastName;
    public string $firstName;
    public string $email;
    public string $password;
    public string $login;
    public string $phone;
    public string $address;
    public string $city;
    public string $zipCode;
    public ?string $uriPicture = null;
    public ?float $credits = null;
    public ?string $preferences = null;


    public function __construct(array $data)
    {
        $this->lastName = trim($data['last_name'] ?? '');
        if (empty($this->lastName)) {
            throw new InvalidArgumentException("Le nom est obligatoire.");
        }

        $this->firstName = trim($data['first_name'] ?? '');
        if (empty($this->firstName)) {
            throw new InvalidArgumentException("Le prénom est obligatoire.");
        }

        $this->email = trim($data['email'] ?? '');
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Email invalide.");
        }

        $this->password = trim($data['password'] ?? '');
        if (strlen($this->password) < 10) {
            throw new InvalidArgumentException("Le mot de passe est trop court.");
        }

        $this->login = trim($data['login'] ?? '');
        if (empty($this->login)) {
            throw new InvalidArgumentException("Le login est obligatoire.");
        }

        $this->phone = trim($data['phone'] ?? '');
        if (empty($this->phone)) {
            throw new InvalidArgumentException("Le téléphone est obligatoire.");
        }

        $this->address = trim($data['address'] ?? '');
        if (empty($this->address)) {
            throw new InvalidArgumentException("L'adresse est obligatoire.");
        }

        $this->city = trim($data['city'] ?? '');
        if (empty($this->city)) {
            throw new InvalidArgumentException("La ville est obligatoire.");
        }

        $this->zipCode = trim($data['zip_code'] ?? '');
        if (empty($this->zipCode)) {
            throw new InvalidArgumentException("Le code postal est obligatoire.");
        }

        $this->uriPicture = isset($data['picture']) ? trim($data['picture']) : null;
        $this->uriPicture = isset($data['credits']) ? (int)($data['credits']) : null;
        $this->uriPicture = isset($data['preferences']) ? trim($data['preferences']) : null;
    }
}
