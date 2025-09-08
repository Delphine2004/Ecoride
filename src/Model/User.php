<?php

// Rajouter les méthodes liées à userRepository

namespace App\Models;


use App\Enum\UserRoles;
use InvalidArgumentException;
use DateTimeImmutable;

//Pas besoin de base model dans une entité

/** 
 * Cette classe représente un utilisateur dans la BDD.
 * Elle contient la validation des données 
 * ainsi que les actions réalisables par les utilisateurs en fonction de leur rôle.
 */

class User
{

    // Promotion des propriétés (depuis PHP 8)
    function __construct(
        private ?int $userId = null, // n'a pas de valeur au moment de l'instanciation
        private string $lastName,
        private string $firstName,
        private string $email,
        private string $password,
        private bool $isHashed = false,

        private ?string $userName = null, // Champ optionnel en fonction du rôle
        private ?string $phone = null, // Champ optionnel en fonction du rôle
        private ?string $address = null, // Champ optionnel en fonction du rôle
        private ?string $city = null, // Champ optionnel en fonction du rôle
        private ?string $zipCode = null, // Champ optionnel en fonction du rôle
        private ?string $uriPicture = null, // Champ optionnel en fonction du rôle
        private ?string $licenceNo = null, // Champ optionnel en fonction du rôle
        private ?int $credits = null, // Champ optionnel en fonction du rôle

        private ?string $apiToken = null, // n'a pas de valeur au moment de l'instanciation
        /**@var UserRoles[] */
        private array $roles = [UserRoles::PASSAGER], // Statut par défaut / en tableau pour pouvoir stoker plusieurs rôles pour un utilisateur
        private array $cars = [], // pour stocker les voitures d'un conducteur
        private array $rides = [], // pour stocker les trajets d'un conducteur
        private array $bookings = [], // pour stocker les réservations



        private ?DateTimeImmutable $createdAt = null, // n'a pas de valeur au moment de l'instanciation
        private ?DateTimeImmutable $updatedAt = null // n'a pas de valeur au moment de l'instanciation
    ) {
        // Affectation avec validation
        $this
            ->setLastName($lastName)
            ->setFirstName($firstName)
            ->setUserName($userName)
            ->setEmail($email)
            ->setPassword($password, $isHashed)
            ->setPhone($phone)
            ->setAddress($address)
            ->setCity($city)
            ->setZipCode($zipCode)
            ->setUriPicture($uriPicture)
            ->setLicenceNo($licenceNo)
            ->setCredits($credits)
            ->setApiToken($apiToken)
            ->setRoles($roles);

        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }


    // ---------Les Getters ---------


    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function getUriPicture(): ?string
    {
        return $this->uriPicture;
    }

    public function getLicenceNo(): ?string
    {
        return $this->licenceNo;
    }

    public function getCredits(): float
    {
        return $this->credits;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function getUserRoles(): array
    {
        return $this->roles;
    }

    public function getCars(): array
    {
        return $this->cars;
    }

    public function getRides(): array
    {
        return $this->rides;
    }

    public function getBookings(): array
    {
        return $this->bookings;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }



    // ---------Les Setters ---------

    public function setUserId(int $id): self
    {
        $this->userId = $id;
        return $this;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = trim($lastName);
        $this->updateTimestamp();
        return $this;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = trim($firstName);
        $this->updateTimestamp();
        return $this;
    }

    public function setEmail(string $email): self
    {
        if (empty(trim($email))) {
            throw new InvalidArgumentException("L'email ne peut pas être vide. ");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("L'email est invalide.");
        }
        $this->email = strtolower(trim($email));
        $this->updateTimestamp();
        return $this;
    }

    public function setPassword(string $password, bool $isHashed = false): self
    {
        if (!$isHashed) {
            $this->validatePassword($password);
            $password = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->password = $password;
        $this->updateTimestamp();
        return $this;
    }

    public function setUserName(?string $userName): self
    {
        if ($userName !== null && strlen(trim($userName)) > 50) {
            throw new InvalidArgumentException("Le nom d'utilisateur est trop long.");
        }
        $this->userName = $userName !== null ? trim($userName) : null;
        $this->updateTimestamp();
        return $this;
    }

    public function setPhone(?string $phone): self
    {
        if ($phone !== null && !preg_match('/^[0-9]{10}$/', trim($phone))) {
            throw new InvalidArgumentException("Le numéro de téléphone doit contenir 10 chiffres.");
        }
        $this->phone = $phone !== null ? trim($phone) : null;
        $this->updateTimestamp();
        return $this;
    }

    public function setAddress(?string $address): self
    {
        if ($address !== null && strlen($address) > 100) {
            throw new InvalidArgumentException("L'adresse est trop longue.");
        }
        $this->address = $address !== null ? trim($address) : null;
        $this->updateTimestamp();
        return $this;
    }

    public function setCity(?string $city): self
    {
        if ($city  !== null && strlen($city) > 50) {
            throw new InvalidArgumentException("Le nom de la ville est trop long.");
        }
        $this->city = $city !== null ? trim($city) : null;
        $this->updateTimestamp();
        return $this;
    }

    public function setZipCode(?string $zipCode): self
    {
        if ($zipCode  !== null && !preg_match('/^[0-9]{5}$/', trim($zipCode))) {
            throw new InvalidArgumentException("Le code postal doit contenir exactement 5 chiffres.");
        }
        $this->zipCode = $zipCode !== null ? trim($zipCode) : null;
        $this->updateTimestamp();
        return $this;
    }

    public function setUriPicture(?string $uriPicture): self
    {
        if ($uriPicture  !== null && !filter_var($uriPicture, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("L'url de l'image est invalide.");
        }

        $this->uriPicture = $uriPicture !== null ? trim($uriPicture) : null;
        $this->updateTimestamp();
        return $this;
    }

    public function setLicenceNo(?string $licenceNo): self
    {
        if ($licenceNo  !== null && strlen($licenceNo) > 50) {
            throw new InvalidArgumentException("Le numéro de permis est trop long.");
        }
        $this->licenceNo = $licenceNo !== null ? trim($licenceNo) : null;
        $this->updateTimestamp();
        return $this;
    }

    public function setCredits(?float $credits): self
    {
        if ($credits !== null && $credits < 0) {
            throw new InvalidArgumentException("Le crédit ne peut pas être négatif.");
        }
        $this->credits = $credits;
        $this->updateTimestamp();
        return $this;
    }

    public function setApiToken(?string $apiToken): self
    {
        $this->apiToken = $apiToken;
        $this->updateTimestamp();
        return $this;
    }

    public function setRoles(array $roles): self
    {
        foreach ($roles as $role) {
            if (!$role instanceof UserRoles) {
                throw new InvalidArgumentException('Chaque rôle doit être une instance de UserRoles.');
            }
        }

        if (!in_array(UserRoles::PASSAGER, $roles, true)) {
            $roles[] = UserRoles::PASSAGER; //conserve le rôle passager
        }
        $this->roles = $roles;
        $this->updateTimestamp();
        return $this;
    }

    public function setCars(array $cars): self
    {
        $this->cars = $cars;
        return $this;
    }

    public function setRides(array $rides): self
    {
        $this->rides = $rides;
        return $this;
    }

    public function setBookings(array $bookings): self
    {
        $this->bookings = $bookings;
        return $this;
    }

    //----------------------------------------
    public function addCar(Car $car): void
    {
        $this->cars[] = $car;
    }

    public function addRide(Ride $ride): void
    {
        $this->rides[] = $ride;
    }

    public function addBooking(Booking $booking): void
    {
        $this->bookings[] = $booking;
    }


    // ------- Gestion des rôles --------

    public function addRole(UserRoles $role): self
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
        $this->updateTimestamp();
        return $this;
    }

    public function removeRole(UserRoles $role): self
    {
        $this->roles = array_filter($this->roles, fn($r) => $r !== $role);
        $this->updateTimestamp();
        return $this;
    }

    //Fonction qui permet à un passager d'ajouter le rôle conducteur.

    /*
    public function changeToDriver(): void
    {
        if ($this->hasRole(UserRoles::CONDUCTEUR)) {
            throw new InvalidArgumentException("L'utilisateur est déjà conducteur.");
        }
        if (!$this->hasRole(UserRoles::PASSAGER)) {
            throw new InvalidArgumentException("Seulement les passagers peuvent devenir chaufeur.");
        }
        $this->addRole(UserRoles::CONDUCTEUR);
    }*/


    // ----- Méthodes de vérification -----

    public function verifyPassword(string $inputPassword): bool
    {
        return password_verify($inputPassword, $this->password);
    }

    private function validatePassword(string $password): void
    {
        $password = trim($password);
        // modifier la longueur pour 14 plus tard
        $regexPassword = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';

        if (!preg_match($regexPassword, $password)) {

            throw new InvalidArgumentException('Le mot de passe doit contenir au minimun une minuscule, une majuscule, un chiffre, un caractère spécial et contenir 8 caractéres au total.');
        };
    }


    //-----------Gestion des crédits----------------
    public function decrementCredits(float $amount): void
    {
        if ($amount > $this->credits) {
            throw new InvalidArgumentException("Crédits insuffisants.");
        }
        $this->credits = $amount;
    }

    public function incrementCredit(float $amount): void
    {
        $this->credits += $amount;
    }


    // ---- Mise à jour de la date de modification

    private function updateTimestamp(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
