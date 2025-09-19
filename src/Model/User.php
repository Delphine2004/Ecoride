<?php

namespace App\Model;

use App\Enum\UserRoles;
use App\Utils\RegexPatterns;
use InvalidArgumentException;
use DateTimeImmutable;


/** 
 * Cette classe représente un utilisateur dans la BDD.
 * Elle contient la validation des données.
 */

class User
{

    function __construct(
        public ?int $userId = null, // n'a pas de valeur au moment de l'instanciation
        public ?string $lastName = null,
        public ?string $firstName = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?bool $isHashed = false,

        public ?string $login = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $zipCode = null,
        public ?string $uriPicture = null,
        public ?string $licenceNo = null,
        public ?int $credits = null,
        public ?string $preferences = null,

        public array $roles = [], // pour pouvoir stoker plusieurs rôles pour un utilisateur
        public array $cars = [], // pour stocker les voitures d'un conducteur
        public array $rides = [], // pour stocker les trajets d'un conducteur
        public array $bookings = [], // pour stocker les réservations


        public ?DateTimeImmutable $createdAt = null, // n'a pas de valeur au moment de l'instanciation
        public ?DateTimeImmutable $updatedAt = null // n'a pas de valeur au moment de l'instanciation
    ) {
        // Affectation avec validation
        $this
            ->setUserLastName($lastName)
            ->setUserFirstName($firstName)
            ->setUserEmail($email)
            ->setUserPassword($password, $isHashed)
            ->setUserLogin($login)
            ->setUserPhone($phone)
            ->setUserAddress($address)
            ->setUserCity($city)
            ->setUserZipCode($zipCode)
            ->setUserUriPicture($uriPicture)
            ->setUserLicenceNo($licenceNo)
            ->setUserCredits($credits)
            ->setUserPreference($preferences)
            ->setUserRoles($roles)
            ->setUserCars($cars)
            ->setUserRides($rides)
            ->setUserBookings($bookings);

        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    public static function fromDatabaseRow(array $row): self
    {
        $userId = $row['user_id'] ?? null;
        $lastName = $row['last_name'] ?? null;
        $firstName = $row['first_name'] ?? null;
        $email = $row['email'] ?? null;
        $password = $row['password'] ?? null;
        $login = $row['login'] ?? null;
        $phone = $row['phone'] ?? null;
        $address = $row['address'] ?? null;
        $city = $row['city'] ?? null;
        $zipCode = $row['zip_code'] ?? null;
        $uriPicture = $row['picture'] ?? null;
        $licenceNo = $row['licence_no'] ?? null;
        $credits = (int)$row['credits'] ?? null;
        $preferences = $row['preferences'] ?? null;
        $createdAt = $row['created_at'] ? new DateTimeImmutable($row['created_at']) : null;
        $updatedAt = $row['updated_at'] ? new DateTimeImmutable($row['updated_at']) : null;

        return new self(
            userId: $userId,
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            password: $password,
            login: $login,
            phone: $phone,
            address: $address,
            city: $city,
            zipCode: $zipCode,
            uriPicture: $uriPicture,
            licenceNo: $licenceNo,
            credits: $credits,
            preferences: $preferences,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }


    // ---------Les Getters ---------


    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getUserLastName(): ?string
    {
        return $this->lastName;
    }

    public function getUserFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getUserEmail(): ?string
    {
        return $this->email;
    }

    public function getUserPassword(): ?string
    {
        return $this->password;
    }

    public function getUserLogin(): ?string
    {
        return $this->login;
    }

    public function getUserPhone(): ?string
    {
        return $this->phone;
    }

    public function getUserAddress(): ?string
    {
        return $this->address;
    }

    public function getUserCity(): ?string
    {
        return $this->city;
    }

    public function getUserZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function getUserUriPicture(): ?string
    {
        return $this->uriPicture;
    }

    public function getUserLicenceNo(): ?string
    {
        return $this->licenceNo;
    }

    public function getUserCredits(): ?float
    {
        return $this->credits;
    }

    public function getUserPreference(): ?string
    {
        return $this->preferences;
    }

    public function getUserRoles(): array
    {
        return $this->roles;
    }

    public function getUserCars(): array
    {
        return $this->cars;
    }

    public function getUserRides(): array
    {
        return $this->rides;
    }

    public function getUserBookings(): array
    {
        return $this->bookings;
    }

    public function getUserCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUserUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }



    // ---------Les Setters ---------


    public function setUserLastName(?string $lastName): self
    {
        $this->lastName = trim($lastName);

        if (empty($lastName)) {
            throw new InvalidArgumentException("Le nom est obligatoire.");
        }

        if (!preg_match(RegexPatterns::ONLY_TEXTE_REGEX, $lastName)) {
            throw new InvalidArgumentException("Le nom doit être compris entre 4 et 20 caractères autorisés.");
        }

        $this->lastName = strtoupper($lastName);
        $this->updateTimestamp();
        return $this;
    }

    public function setUserFirstName(?string $firstName): self
    {
        $this->firstName = trim($firstName);

        if (empty($firstName)) {
            throw new InvalidArgumentException("Le prénom est obligatoire.");
        }

        if (!preg_match(RegexPatterns::ONLY_TEXTE_REGEX, $firstName)) {
            throw new InvalidArgumentException("Le prénom doit être compris entre 4 et 20 caractères autorisés.");
        }

        $this->firstName = ucfirst($firstName);
        $this->updateTimestamp();
        return $this;
    }

    public function setUserEmail(?string $email): self
    {
        $this->email = trim($email);

        if (empty($email)) {
            throw new InvalidArgumentException("L'email est obligatoire. ");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("L'email est invalide.");
        }
        $this->email = strtolower($email);
        $this->updateTimestamp();
        return $this;
    }

    public function setUserPassword(?string $password, bool $isHashed = false): self
    {
        $this->password = trim($password);

        if (!$isHashed) {
            $this->validatePassword($password);
            $password = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->password = $password;
        $this->updateTimestamp();
        return $this;
    }


    public function setUserLogin(?string $login): self
    {
        if ($login !== null) {
            $login = trim($login);

            if (!preg_match(RegexPatterns::LOGIN, $login)) {
                throw new InvalidArgumentException("Le login doit contenir entre 10 et 25 caractères autorisés.");
            }
        }
        $this->login = $login;
        $this->updateTimestamp();
        return $this;
    }

    public function setUserPhone(?string $phone): self
    {
        if ($phone !== null) {
            $phone = trim($phone);

            if (!preg_match(RegexPatterns::FRENCH_MOBILE_PHONE, $phone)) {
                throw new InvalidArgumentException("Le numéro de téléphone doit contenir 10 chiffres.");
            }
        }

        $this->phone = $phone;
        $this->updateTimestamp();
        return $this;
    }

    public function setUserAddress(?string $address): self
    {
        if ($address !== null) {
            $address = trim($address);

            if (!preg_match(RegexPatterns::ADDRESS, $address)) {
                throw new InvalidArgumentException("L'adresse doit contenir entre 11 et 40 caractères autorisés.");
            }
        }

        $this->address = $address;
        $this->updateTimestamp();
        return $this;
    }

    public function setUserCity(?string $city): self
    {
        if ($city !== null) {
            $city = trim($city);

            if (!preg_match(RegexPatterns::ONLY_TEXTE_REGEX, $city)) {
                throw new InvalidArgumentException("Le ville doit contenir entre 4 et 20 caractères autorisés.");
            }
        }

        $this->city = strtoupper($city);
        $this->updateTimestamp();
        return $this;
    }

    public function setUserZipCode(?string $zipCode): self
    {
        if ($zipCode !== null) {
            $zipCode = trim($zipCode);

            if (!preg_match(RegexPatterns::ZIP_CODE, $zipCode)) {
                throw new InvalidArgumentException("Le code postal doit contenir exactement 5 chiffres.");
            }
        }

        $this->zipCode = $zipCode;
        $this->updateTimestamp();
        return $this;
    }

    public function setUserUriPicture(?string $uriPicture): self
    {
        if ($uriPicture !== null && !filter_var($uriPicture, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("L'url de l'image est invalide.");
        }

        $this->uriPicture = $uriPicture;
        $this->updateTimestamp();
        return $this;
    }

    public function setUserLicenceNo(?string $licenceNo): self
    {
        if ($licenceNo !== null) {
            $licenceNo = trim($licenceNo);

            if (!preg_match(RegexPatterns::OLD_LICENCE_NUMBER, strtoupper($licenceNo)) && !preg_match(RegexPatterns::NEW_LICENCE_NUMBER, strtoupper($licenceNo))) {
                throw new InvalidArgumentException("Le format du numéro de permis est incorrect.");
            }
        }

        $this->licenceNo = strtoupper($licenceNo);
        $this->updateTimestamp();
        return $this;
    }

    public function setUserCredits(?float $credits): self
    {
        if ($credits !== null && $credits < 0) {
            throw new InvalidArgumentException("Le crédit ne peut pas être négatif.");
        }

        $this->credits = $credits;
        $this->updateTimestamp();
        return $this;
    }

    public function setUserPreference(?string $preferences): self
    {
        if ($preferences !== null) {
            $preferences = trim($preferences);

            if (!preg_match(RegexPatterns::COMMENT_REGEX, $preferences)) {
                throw new InvalidArgumentException("Les préférences peuvent contenir entre 2 et 255 caractères autorisés.");
            }
        }
        $this->preferences = $preferences;
        $this->updateTimestamp();
        return $this;
    }

    public function setUserRoles(array $roles): self
    {
        $this->roles = $roles;
        $this->updateTimestamp();
        return $this;
    }

    public function setUserCars(array $cars): self
    {
        $this->cars = $cars;
        $this->updateTimestamp();
        return $this;
    }

    public function setUserRides(array $rides): self
    {
        $this->rides = $rides;
        $this->updateTimestamp();
        return $this;
    }

    public function setUserBookings(array $bookings): self
    {
        $this->bookings = $bookings;
        $this->updateTimestamp();
        return $this;
    }

    //----------------------------------------
    public function addUserCar(Car $car): void
    {
        $this->cars[] = $car;
    }

    public function addUserRide(Ride $ride): void
    {
        $this->rides[] = $ride;
    }

    public function addUserBooking(Booking $booking): void
    {
        $this->bookings[] = $booking;
    }


    // ------- Gestion des rôles --------

    public function addUserRole(UserRoles $role): self
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
        $this->updateTimestamp();
        return $this;
    }

    public function removeUserRole(UserRoles $role): self
    {
        $this->roles = array_filter($this->roles, fn($r) => $r !== $role);
        $this->updateTimestamp();
        return $this;
    }

    // ----- Méthodes de vérification -----

    public function verifyPassword(string $inputPassword): bool
    {
        return password_verify($inputPassword, $this->password);
    }

    private function validatePassword(string $password): void
    {
        $password = trim($password);

        if (!preg_match(RegexPatterns::PASSWORD, $password)) {

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

    public function incrementCredits(float $amount): void
    {
        $this->credits += $amount;
    }


    // ---- Mise à jour de la date de modification

    private function updateTimestamp(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
