<?php

namespace App\DTO;

use InvalidArgumentException;

class UpdateUserDTO
{
    public ?string $firstName = null;
    public ?string $lastName = null;
    public ?string $email = null;
    public ?string $login = null;
    public ?string $phone = null;
    public ?string $address = null;
    public ?string $city = null;
    public ?string $zipCode = null;
    public ?string $uriPicture = null;
    public ?string $licenceNo = null;
    public ?float $credits = null;
    public array $preferences = [];

    public function __construct(array $data)
    {

        if (isset($data['last_name'])) {
            $this->lastName = trim($data['last_name']);
        }


        if (isset($data['first_name'])) {
            $this->firstName = trim($data['first_name']);
        }

        if (isset($data['email'])) {
            $this->email = trim($data['email']);
            if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Email invalide.");
            }
        }


        if (isset($data['login'])) {
            $this->login = trim($data['login']);
        }

        if (isset($data['phone'])) {
            $this->phone = trim($data['phone']);
        }

        if (isset($data['address'])) {
            $this->address = trim($data['address']);
        }

        if (isset($data['city'])) {
            $this->city = trim($data['city']);
        }

        if (isset($data['zip_code'])) {
            $this->zipCode = trim($data['zip_code']);
        }

        if (isset($data['picture'])) {
            $this->uriPicture = trim($data['picture']);
        }

        if (isset($data['licence_no'])) {
            $this->licenceNo =  trim($data['licence_no']);
        }

        if (isset($data['credits'])) {
            $this->credits =  (float)($data['credits']);
        }

        if (isset($data['preferences']) && is_array($data['preferences'])) {
            $allowedKeys = ['smoker', 'pets', 'note'];
            foreach ($data['preferences'] as $key => $value) {
                if (in_array($key, $allowedKeys, true)) {
                    $this->preferences[$key] = $value;
                }
            }
        }
    }
}
