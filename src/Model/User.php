<?php

// Rajouter les méthodes liées à userRepository

namespace App\Models;


use App\Enum\UserRoles;
use InvalidArgumentException;


/** 
 * Cette classe représente un utilisateur dans la BDD.
 * Elle contient la validation des données 
 * ainsi que les actions réalisables par les utilisateurs en fonction de leur rôle.
 */

class User
{

    // déclaration des propriétés façon moderne
    function __construct(
        private ?int $id = null,
        private string $lastName,
        private string $firstName,
        private string $email,
        private string $password,
        private bool $isHashed = false,
        private ?string $userName = null,
        private ?string $phone = null,
        private ?string $address = null,
        private ?string $city = null,
        private ?string $zipCode = null,
        private ?string $uriPicture = null,
        private ?string $licenceNo = null,
        private ?int $credit = null,
        private ?string $apiToken, // elle n'a pas de valeur au moment de l'instanciation
        private ?string $createdAt, // elle n'a pas de valeur au moment de l'instanciation
        private ?string $updatedAt, // elle n'a pas de valeur au moment de l'instanciation
        /**@var UserRoles[] */
        private array $roles = [UserRoles::PASSAGER] // Pour pouvoir stoker plusieurs rôles pour un utilisateur
    ) {
        // Affectation avec valisation
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
            ->setCredit($credit)
            ->setApiToken($apiToken)
            ->setRoles($roles);
    }


    // ---------Les Getters ---------


    public function getId(): ?int
    {
        return $this->id;
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

    public function getCredit(): ?int
    {
        return $this->credit;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }


    // ---------Les Setters ---------


    public function setLastName(string $lastName): self
    {
        $this->lastName = trim($lastName);
        return $this;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = trim($firstName);
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
        return $this;
    }

    public function setPassword(string $password, bool $isHashed = false): self
    {
        $password = trim($password);
        if (!$isHashed) {
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
                // modifier la longueur pour 14 plus tard
                throw new InvalidArgumentException('Le mot de passe doit contenir au minimun une minuscule, une majuscule, un chiffre, un caractère spécial et contenir 8 caractéres au total.');
            }
            $password = password_hash($password, PASSWORD_DEFAULT);
        }
        $this->password = $password;
        return $this;
    }

    public function setUserName(?string $userName): self
    {
        if ($userName !== null && strlen(trim($userName)) > 50) {
            throw new InvalidArgumentException("Le nom d'utilisateur est trop long.");
        }
        $this->userName = $userName !== null ? trim($userName) : null;
        return $this;
    }

    public function setPhone(?string $phone): self
    {
        if ($phone !== null && !preg_match('/^[0-9]{10}$/', trim($phone))) {
            throw new InvalidArgumentException("Le numéro de téléphone doit contenir 10 chiffres.");
        }
        $this->phone = $phone !== null ? trim($phone) : null;
        return $this;
    }

    public function setAddress(?string $address): self
    {
        if ($address !== null && strlen($address) > 100) {
            throw new InvalidArgumentException("L'adresse est trop longue.");
        }
        $this->address = $address !== null ? trim($address) : null;
        return $this;
    }

    public function setCity(?string $city): self
    {
        if ($city  !== null && strlen($city) > 50) {
            throw new InvalidArgumentException("Le nom de la ville est trop long.");
        }
        $this->city = $city !== null ? trim($city) : null;
        return $this;
    }

    public function setZipCode(?string $zipCode): self
    {
        if ($zipCode  !== null && !preg_match('/^[0-9]{5}$/', trim($zipCode))) {
            throw new InvalidArgumentException("Le code postal doit contenir exactement 5 chiffres.");
        }
        $this->zipCode = $zipCode !== null ? trim($zipCode) : null;
        return $this;
    }

    public function setUriPicture(?string $uriPicture): self
    {
        if ($uriPicture  !== null && !filter_var($uriPicture, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("L'url de l'image est invalide.");
        }

        $this->uriPicture = $uriPicture !== null ? trim($uriPicture) : null;
        return $this;
    }

    public function setLicenceNo(?string $licenceNo): self
    {
        if ($licenceNo  !== null && strlen($licenceNo) > 50) {
            throw new InvalidArgumentException("Le numéro de permis est trop long.");
        }
        $this->licenceNo = $licenceNo !== null ? trim($licenceNo) : null;
        return $this;
    }

    public function setCredit(?int $credit): self
    {
        if ($credit !== null && $credit < 0) {
            throw new InvalidArgumentException("Le crédit ne peut pas être négatif.");
        }
        $this->credit = $credit;
        return $this;
    }

    public function setApiToken(?string $apiToken): self
    {
        $this->apiToken = $apiToken;
        return $this;
    }

    public function setRoles(array $roles): self
    {
        foreach ($roles as $role) {
            if (!$role instanceof UserRoles) {
                throw new InvalidArgumentException('Chaque rôle doit être une instance de UserRoles.');
            }
        }
        $this->roles = $roles;
        return $this;
    }

    // ----- Méthodes de manipulation -----

    public function addRole(UserRoles $role): self
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
        return $this;
    }

    public function removeRole(UserRoles $role): self
    {
        $this->roles = array_filter($this->roles, fn($r) => $r !== $role);
        return $this;
    }

    // ----- Méthodes de vérification -----

    public function verifyPassword(string $inputPassword): bool
    {
        return password_verify($inputPassword, $this->password);
    }

    public function hasRole(UserRoles $roleToCheck): bool
    {
        return in_array($roleToCheck, $this->roles, true);
    }

    public function hasAnyRole(array $roleToCheck): bool
    {
        foreach ($roleToCheck as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    // ----- Méthodes en fonction du rôle ----- 

    // Méthodes communes à tous les rôle
    public function changePassword(string $oldPassword, string $newPassword): void
    {
        if (!$this->verifyPassword($oldPassword)) {
            throw new InvalidArgumentException("Le mot de passe actuel est incorrect.");
        }
        if (strlen(trim($newPassword)) < 8) {
            throw new InvalidArgumentException("Le nouveau mot de passe est trop court.");
        }
        $this->setPassword($newPassword);
    }

    public function updateProfile(array $newData): void
    {
        if (isset($newData['last_name'])) {
            $this->setLastName($newData['last_name']);
        }
        if (isset($newData['first_name'])) {
            $this->setFirstName($newData['first_name']);
        }
        if (isset($newData['email'])) {
            $this->setEmail($newData['email']);
        }
        if (isset($newData['user_name'])) {
            $this->setUserName($newData['user_name']);
        }
        if (isset($newData['phone'])) {
            $this->setPhone($newData['phone']);
        }
        if (isset($newData['address'])) {
            $this->setAddress($newData['address']);
        }
        if (isset($newData['city'])) {
            $this->setCity($newData['city']);
        }
        if (isset($newData['zip_code'])) {
            $this->setZipCode($newData['zip_code']);
        }
        if (isset($newData['picture'])) {
            $this->setUriPicture($newData['picture']);
        }
        if (isset($newData['licence_no'])) {
            $this->setLicenceNo($newData['licence_no']);
        }
    }

    // cette fonction uniquement pour les conducteurs et les passagers
    // à compléter
    public function deleteAccount(): void
    {
        if ($this->hasRole(UserRoles::ADMIN) || $this->hasRole(UserRoles::EMPLOYE)) {
            throw new InvalidArgumentException("Un admin ou un employé ne peut pas supprimer son compte.");
        }

        // Faire appelle à UserRepository::delete($this->id);
    }

    // Méthodes pour le rôle passager
    public function changeToDriver(): void
    {
        if ($this->hasRole(UserRoles::CONDUCTEUR)) {
            throw new InvalidArgumentException("L'utilisateur est déjà conducteur.");
        }
        if (!$this->hasRole(UserRoles::PASSAGER)) {
            throw new InvalidArgumentException("Seulement les passagers peuvent devenir chaufeur.");
        }
        $this->addRole(UserRoles::CONDUCTEUR);
    }

    public function joinRide(): void
    {
        if (!$this->hasRole(UserRoles::PASSAGER)) {
            throw new InvalidArgumentException("Seulement les passagers peuvent participer à un trajet.");
        }
    }

    public function leaveReview(): void
    {
        if (!$this->hasRole(UserRoles::PASSAGER)) {
            throw new InvalidArgumentException("Seulement les passager peuvent laisser un commentaire au conducteur.");
        }
    }


    // Méthodes pour le rôle conducteur
    public function addCar(): void
    {
        if (!$this->hasRole(UserRoles::CONDUCTEUR)) {
            throw new InvalidArgumentException("Seulement les conducteurs peuvent ajouter une voiture.");
        }
    }

    public function removeCar(): void
    {
        if (!$this->hasRole(UserRoles::CONDUCTEUR)) {
            throw new InvalidArgumentException("Seulement les conducteurs peuvent supprimer une voiture.");
        }
    }

    public function publishRide(): void
    {
        if (!$this->hasRole(UserRoles::CONDUCTEUR)) {
            throw new InvalidArgumentException("Seulement les conducteurs peuvent publier un trajet.");
        }
    }

    public function cancelRide(): void
    {
        if (!$this->hasRole(UserRoles::CONDUCTEUR)) {
            throw new InvalidArgumentException("Il faut avoir le rôle conducteur pour annuler un trajet.");
        }
    }

    public function finishRide(): void
    {
        if (!$this->hasRole(UserRoles::CONDUCTEUR)) {
            throw new InvalidArgumentException("Seulement les conducteurs peuvent finir un trajet.");
        }
    }


    // Méthodes pour le rôle employé
    public function approveReview(): void
    {
        if (!$this->hasRole(UserRoles::EMPLOYE)) {
            throw new InvalidArgumentException("Seulement les employés peuvent approuver un commentaire.");
        }
        // faire appel à la validation via userRepository
    }


    // Méthodes pour le rôle admin
    // à compléter
    public function createEmployee(): void
    {
        if (!$this->hasRole(UserRoles::ADMIN)) {
            throw new InvalidArgumentException("Seulement les admins peuvent créer un employé.");
        }
        // faire appel à la création via userRepository
    }

    // à compléter
    public function deleteUser(): void
    {
        if (!$this->hasRole(UserRoles::ADMIN)) {
            throw new InvalidArgumentException("Seulement les admins peuvent supprimer un autre compte.");
        }
        // faire appel à la suppression via userRepository
        // $userRepository->delete($this->id);
    }
}
