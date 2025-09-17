<?php

namespace App\Service;

use App\Repository\CarRepository;
use App\Repository\RideRepository;
use App\Repository\UserRepository;
use App\Repository\BookingRepository;
use App\Model\Ride;
use App\Model\User;
use App\Model\Booking;
use App\Enum\UserRoles;

use InvalidArgumentException;

abstract class BaseService
{


    public function __construct(
        protected CarRepository $carRepository,
        protected RideRepository $rideRepository,
        protected UserRepository $userRepository,
        protected BookingRepository $bookingRepository,
    ) {}



    /**
     * Vérifie si l'utilisateur a des voitures.
     *
     * @param integer $userId
     * @return boolean
     */
    public function userHasCars(int $userId): bool
    {
        // Récupération de l'utilisateur
        $user = $this->userRepository->findUserById($userId);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw new InvalidArgumentException("Utilisateur introuvable.");
        }

        $this->ensureDriver($userId);
        return count($this->carRepository->findAllCarsByOwner($userId)) > 0;
    }





    //------------------------Vérification des rôles-------------------
    /**
     * Vérifie un rôle en particulier.
     *
     * @param integer $userId
     * @param string $roleName
     * @return boolean
     */
    public function hasRole(int $userId, UserRoles $role): bool
    {
        // Trouver les roles de l'utilisateur
        $roles = $this->userRepository->getUserRoles($userId);



        // Vérifier si le rôle qui lui est associé est correct
        return in_array($role, $roles, true);
    }

    public function hasAnyRole(int $userId, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($userId, $role)) {
                return true;
            }
        }
        return false;
    }


    public function isPassenger(int $userId): bool
    {
        return $this->hasRole($userId, UserRoles::PASSAGER);
    }


    public function isDriver(int $userId): bool
    {
        return $this->hasRole($userId, UserRoles::CONDUCTEUR);
    }


    public function isCustomer(int $userId): bool
    {
        return $this->hasAnyRole($userId, [UserRoles::PASSAGER, UserRoles::CONDUCTEUR]);
    }


    public function isEmployee(int $userId): bool
    {
        return $this->hasRole($userId, UserRoles::EMPLOYE);
    }


    public function isAdmin(int $userId): bool
    {
        return $this->hasRole($userId, UserRoles::ADMIN);
    }

    public function isStaff(int $userId): bool
    {
        return $this->hasAnyRole($userId, [UserRoles::EMPLOYE, UserRoles::ADMIN]);
    }


    public function isUser(int $userId): bool
    {
        return $this->hasAnyRole($userId, [UserRoles::PASSAGER, UserRoles::CONDUCTEUR, UserRoles::EMPLOYE, UserRoles::ADMIN]);
    }


    /**
     * Vérifie que l'utilisateur a le rôle PASSAGER.
     *
     * @param integer $userId
     * @return void
     */
    protected function ensurePassenger(int $userId): void
    {
        if (!$this->isPassenger($userId)) {
            throw new InvalidArgumentException("Seulement les utilisateurs ayant le rôle PASSAGER peuvent effectuer cette action.");
        }
    }

    /**
     * Vérifie que l'utilisateur a le rôle CONDUCTEUR.
     *
     * @param integer $userId
     * @return void
     */
    protected function ensureDriver(int $userId): void
    {
        if (!$this->isDriver($userId)) {
            throw new InvalidArgumentException("Seulement les utilisateurs ayant le rôle CONDUCTEUR peuvent effectuer cette action.");
        }
    }

    /**
     * Vérifie que l'utilisateur a les rôles PASSAGER ou CONDUCTEUR.
     *
     * @param integer $userId
     * @return void
     */
    protected function ensureCustomer(int $userId): void
    {
        if (!$this->isCustomer($userId)) {
            throw new InvalidArgumentException("Seulement les utilisateurs ayant le role PASSAGER ou CONDUCTEUR peuvent effectuer cette action.");
        }
    }
    /**
     * Vérifie que l'utilisateur a le rôle EMPLOYE.
     *
     * @param integer $userId
     * @return void
     */
    protected function ensureEmployee(int $userId): void
    {
        if (!$this->isEmployee($userId)) {
            throw new InvalidArgumentException("Seulement les utilisateurs ayant le rôle EMPLOYE peuvent effectuer cette action.");
        }
    }

    /**
     * Vérifie que l'utilisateur a bien le rôle ADMIN.
     *
     * @param integer $userId
     * @return void
     */
    protected function ensureAdmin(int $userId): void
    {
        if (!$this->isAdmin($userId)) {
            throw new InvalidArgumentException("Seulement les utilisateurs ayant le rôle ADMIN peuvent effectuer cette action.");
        }
    }

    /**
     * Vérifie que l'utilisateur a les rôles EMPLOYE ou ADMIN.
     *
     * @param integer $userId
     * @return void
     */
    protected function ensureStaff(int $userId): void
    {
        if (!$this->isStaff($userId)) {
            throw new InvalidArgumentException("Seulement les utilisateurs ayant le rôle EMPLOYE ou ADMIN peuvent effectuer cette action.");
        }
    }

    /**
     * Vérifie que l'utilisateur a bien un rôle .
     *
     * @param integer $userId
     * @return void
     */
    protected function ensureUser(int $userId): void
    {
        if (!$this->isUser($userId)) {
            throw new InvalidArgumentException("Seulement les utilisateurs ayant un rôle peuvent effectuer cette action.");
        }
    }



    //--------------------Trajet----------------------------

    // Envoi une confirmation de création de trajet par email.
    // Confirmations
    public function sendRideConfirmationToDriver(User $user, Ride $ride)
    {
        // Ici tu pourrais envoyer un email, un SMS, ou juste logger
        echo sprintf(
            "Confirmation envoyée à %s pour le trajet %d (départ %s)\n",
            $user->getUserLogin(),
            $ride->getRideId(),
            $ride->getRideDepartureDateTime()
        );
    }

    public function sendRideConfirmationToPassenger(User $user, Ride $ride) {}

    // Annulation
    // Envoi une confirmation d'annulation de trajet par email au passager.
    public function sendRideCancelationToPassenger(User $passenger, Ride $ride) {}

    // Envoi une confirmation d'annulation de trajet par email au conducteur.
    public function sendRideCancelationToDriver(User $driver, Ride $ride) {}

    // Actions
    // envoi une confirmation de démarrage du trajet
    public function sendRideStart() {}

    public function sendRideConfirmationStopToDriver() {}

    public function sendRideFinalizationRequestToPassenger() {}

    // Envoi une confirmation de finalisation de trajet par email au passager. - Doit demander au participant de valider 
    //- voir comment faire pour non validé
    public function sendRideFinalizationToPassenger(User $passenger, Ride $ride) {}

    // Envoi une confirmation de finalisation de trajet par email au conducteur.
    public function sendRideFinalizationToDriver(User $driver, Ride $ride) {}


    //-----------------Réservation--------------------------

    // Envoi une confirmation de création de réservation par email.
    public function sendBookingConfirmationToPassenger(User $passenger, Booking $booking) {}

    // Envoi une confirmation de création de réservation par email.
    public function sendBookingConfirmationToDriver(User $driver, Booking $booking) {}


    // Envoi une confirmation d'annulation de réservation sans frais par email au passager.
    public function sendBookingCancelationToPassenger(User $passenger, Booking $booking) {}

    // Envoi une confirmation d'annulation de réservation sans frais par email au conducteur.
    public function sendBookingCancelationToDriver(User $driver, Booking $booking) {}


    // Envoi une confirmation d'annulation de réservation tardive par email au passager.
    public function sendBookingLateCancelationToPassenger(User $passenger, Booking $booking) {}

    // Envoi une confirmation d'annulation de réservation tardive par email au conducteur.
    public function sendBookingLateCancelationToDriver(User $driver, Booking $booking) {}
}
