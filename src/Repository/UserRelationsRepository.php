<?php

namespace App\Repositories;

use App\Enum\UserRoles;
use App\Repositories\CarRepository;
use App\Repositories\RideRepository;
use App\Repositories\UserRepository;
use App\Repositories\BookingRepository;
use App\Models\User;
use PDO;

/**
 * Cette classe gére la correspondance entre un utilisateur et les voitures, les trajets et les réservations.
 */

class UserRelationsRepository extends UserRepository
{
    protected string $table = 'users';
    protected string $primaryKey = 'user_id';

    public function __construct(
        PDO $db,
        private CarRepository $carRepository,
        private RideRepository $rideRepository,
        private BookingRepository $bookingRepository
    ) {
        parent::__construct($db);
    }

    //-------------------------------------------


    //  ------ Récupérations spécifiques ---------

    /**
     * Récupére un objet User avec ses relations en liste d'objet.
     *
     * @param integer $userId
     * @param array $with
     * @return User|null
     */
    public function findUserWithRelations(
        int $userId,
        array $with = []
    ): ?User {

        // Récupération de l'utilisateur
        $user = $this->findUserById($userId);
        if (!$user) {
            return null;
        }

        // Vérification des relations
        if (in_array('cars', $with, true)) {
            // réinitialise la collection à un tableau vide
            $user->setUserCars([]);
            // Récupération de la liste des voitures
            $cars = $this->carRepository->fetchAllCarsByOwner([$userId]);
            // Parcourir chaque voiture pour les ajouter à l'utilisateur
            foreach ($cars as $car) {
                $user->addUserCar($car);
            }
        }

        if (in_array('rides', $with, true)) {
            // réinitialise la collection à un tableau vide
            $user->setUserRides([]);
            // Récupération de la liste des trajets
            $rides = $this->rideRepository->fetchAllRidesByDriver($userId);
            foreach ($rides as $ride) {
                $user->addUserRide($ride);
            }
        }

        if (in_array('bookings', $with, true)) {
            // réinitialise la collection à un tableau vide
            $user->setUserBookings([]);
            // Récupération de la liste des réservations 
            $bookings = $this->bookingRepository->fetchAllBookingsByPassengerId([$userId]);
            foreach ($bookings as $booking) {
                $user->addUserBooking($booking);
            }
        }
        return $user;
    }


    /**
     * Récupére un objet User conducteur avec la liste des objets Car.
     *
     * @param integer $driverId
     * @return User|null
     */
    public function findUserWithCars(
        int $driverId
    ): ?User {
        // Récupérer l'utilisateur avec la relation 'cars'
        $driver = $this->findUserWithRelations($driverId, ['cars']);

        // Vérifier son existence
        if (!$driver) {
            return null;
        }

        // vérifier que l'utilisateur a le rôle conducteur
        if (!in_array(UserRoles::CONDUCTEUR, $driver->getUserRoles(), true)) {
            return null;
        }
        return $driver;
    }

    /**
     * Récupére un objet User conducteur avec la liste des objets Ride.
     *
     * @param integer $driverId
     * @return User|null
     */
    public function findUserWithRides(
        int $driverId
    ): ?User {
        // Récupérer l'utilisateur avec la relation 'rides'
        $driver = $this->findUserWithRelations($driverId, ['rides']);

        // Vérifier son existence
        if (!$driver) {
            return null;
        }

        // vérifier que l'utilisateur a le rôle conducteur
        if (!in_array(UserRoles::CONDUCTEUR, $driver->getUserRoles(), true)) {
            return null;
        }
        return $driver;
    }

    /**
     * Récupére un objet User passager avec la liste des objets Booking.
     *
     * @param integer $userId
     * @return User|null
     */
    public function findUserWithBookings(
        int $userId
    ): ?User {
        // Récupérer l'utilisateur avec la relation 'rides'
        $user = $this->findUserWithRelations($userId, ['bookings']);

        // Vérifier son existence
        if (!$user) {
            return null;
        }

        // vérifier que l'utilisateur a le rôle conducteur
        if (!in_array(UserRoles::PASSAGER, $user->getUserRoles(), true)) {
            return null;
        }
        return $user;
    }

    //---------------------------------------------------

    /**
     * Récupére la liste des objets User avec ses relations en liste brute avec tri et pagination. 
     * Cette fonction permet d'éviter les jointures pour ne pas avoir de doublons sur les utilisateurs.
     *
     * @param array $with
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllUsersWithRelations(
        array $with = [],
        string $orderBy = 'user_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {

        // Récupération de tous les utilisateurs
        $users = $this->findAllUsersByFields([], $orderBy, $orderDirection, $limit, $offset); // Tous les utilisateurs sans distinction de rôle


        // Pour chaque utilisateur de la liste, vérification des relations
        foreach ($users as $user) {

            if (in_array('cars', $with, true)) {
                // récupére la liste brute
                $user->setCars($this->carRepository->fetchAllCarsByOwner($user->getId()));
            }

            if (in_array('rides', $with, true)) {
                // récupére la liste brute
                $user->setRides($this->rideRepository->fetchAllRidesByDriver($user->getId()));
            }

            if (in_array('bookings', $with, true)) {
                // récupére la liste brute
                $user->setBookings($this->bookingRepository->fetchAllBookingsByPassengerId($user->getId()));
            }
        }
        return $users;
    }


    /**
     * Récupére la liste des objets User conducteur avec leur voiture en liste brute avec tri et pagination. 
     *
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllUsersWithCars(
        string $orderBy = 'user_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        // Récupérer les utilisateurs avec la relation 'cars'
        $users = $this->findAllUsersWithRelations(['cars'], $orderBy, $orderDirection, $limit, $offset);
        return $users;
    }

    /**
     * Récupére la liste des objets User conducteur avec leur trajet en liste brute avec tri et pagination.
     *
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllUsersWithRides(
        string $orderBy = 'user_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        // Récupérer les utilisateurs avec la relation 'rides'
        $users = $this->findAllUsersWithRelations(['rides'], $orderBy, $orderDirection, $limit, $offset);
        return $users;
    }

    /**
     * Récupére la liste des objets User passager avec leur réservation en liste brute avec tri et pagination.
     *
     * @param string $orderBy
     * @param string $orderDirection
     * @param integer $limit
     * @param integer $offset
     * @return array
     */
    public function findAllUsersWithBookings(
        string $orderBy = 'user_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {
        // Récupérer les utilisateurs avec la relation 'bookings'
        $users = $this->findAllUsersWithRelations(['bookings'], $orderBy, $orderDirection, $limit, $offset);
        return $users;
    }
}
