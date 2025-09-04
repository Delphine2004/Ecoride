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

    private CarRepository $carRepository;
    private RideRepository $rideRepository;
    private BookingRepository $bookingRepository;

    public function __construct(PDO $db, CarRepository $carRepository, RideRepository $rideRepository, BookingRepository $bookingRepository)
    {
        parent::__construct($db);
        $this->carRepository = $carRepository;
        $this->rideRepository = $rideRepository;
        $this->bookingRepository = $bookingRepository;
    }


    //  ------ Récupérations spécifiques ---------

    // Récupére un objet User avec ses relations en liste d'objet - OK
    public function findUserWithRelations(int $userId, array $with = []): ?User
    {

        // Récuperation de l'utilisateur
        $user = $this->findUserById($userId);
        if (!$user) {
            return null;
        }

        // Relations
        if (in_array('cars', $with, true)) {
            $user->setCars([]);
            $cars = $this->carRepository->findAllCarsByOwner([$userId]);
            foreach ($cars as $car) {
                $user->addCar($car);
            }
        }

        if (in_array('rides', $with, true)) {
            $user->setRides([]);
            $rides = $this->rideRepository->findAllRidesByDriver([$userId]);
            foreach ($rides as $ride) {
                $user->addRide($ride);
            }
        }

        if (in_array('bookings', $with, true)) {
            $user->setBookings([]);
            $bookings = $this->bookingRepository->findAllBookingsByPassengerId([$userId]);
            foreach ($bookings as $booking) {
                $user->addBooking($booking);
            }
        }
        return $user;
    }

    // Récupére un objet User conducteur avec la liste des objets Car.
    public function findUserWithCars(int $driverId): ?User
    {
        // Récupérer l'utilisateur avec la relation 'cars'
        $driver = $this->findUserWithRelations($driverId, ['cars']);

        // Vérifier son existance
        if (!$driver) {
            return null;
        }

        // vérifier que l'utilisateur a le rôle conducteur
        if (!in_array(UserRoles::CONDUCTEUR, $driver->getRoles(), true)) {
            return null;
        }
        return $driver;
    }

    // Récupére un objet User conducteur avec la liste des objets Ride.
    public function findUserWithRides(int $driverId): ?User
    {
        // Récupérer l'utilisateur avec la relation 'rides'
        $driver = $this->findUserWithRelations($driverId, ['rides']);

        // Vérifier son existance
        if (!$driver) {
            return null;
        }

        // vérifier que l'utilisateur a le rôle conducteur
        if (!in_array(UserRoles::CONDUCTEUR, $driver->getRoles(), true)) {
            return null;
        }
        return $driver;
    }

    // Récupére un objet User passager avec la liste des objets Booking.
    public function findUserWithBookings(int $userId): ?User
    {
        // Récupérer l'utilisateur avec la relation 'rides'
        $user = $this->findUserWithRelations($userId, ['bookings']);

        // Vérifier son existance
        if (!$user) {
            return null;
        }

        // vérifier que l'utilisateur a le rôle conducteur
        if (!in_array(UserRoles::PASSAGER, $user->getRoles(), true)) {
            return null;
        }
        return $user;
    }

    //---------------------------------------------------

    // Récupére un liste d'objet User avec ses relations en liste simple. Cette fonction permet d'éviter les jointures pour ne pas avoir de doublons sur les utilisateurs.
    public function findAllUsersWithRelations(
        array $with = [],
        string $orderBy = 'user_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ) {
        // en cours de création - se baser sur findAllUsersWithCars et findUserWithRelations


    }

    // Récupére la liste des objets User conducteur avec leur voiture en liste simple avec tri et pagination. 
    public function findAllUserWithCars(
        string $orderBy = 'user_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): array {

        //Récupérer les utilisateurs qui ont le rôle conducteur.
        $drivers = $this->findAllUsersByRole('conducteur');
        if (!$drivers) return [];


        // Créer un mapping des conducteurs
        $driverMap = [];
        foreach ($drivers as $driver) {
            $driverMap[$driver->getUserId()] = $driver;
            $driver->setCarOwner([]); // initialise la liste des voitures
        }


        // ---------Récupérer les voitures du conducteur qui est dans la liste d'objet
        // récupérer les ids des conducteurs
        $driverIds = array_map(fn(User $u) => $u->getUserId(), $drivers);
        if (empty($driverIds)) {
            return [];
        }

        // Puis utiliser carRepository et findAllCarsByOwner pour recupérer les voitures des driversIds
        $cars = $this->carRepository->findAllCarsByOwner($driverIds);


        // Associer les voitures aux conducteurs.
        foreach ($cars as $car) {
            $owner = $car->getCarOwner();
            if ($owner === null) {
                continue;
            }
            $ownerId = $owner->getUserId();
            if ($ownerId !== null && isset($driverMap[$ownerId])) {
                $driverMap[$ownerId]->addCar($car);
            }
        }

        //Retour du résultat
        return array_values($driverMap);
    }


    // Récupére la liste des objets User conducteur avec leur trajet en liste simple avec tri et pagination.
    public function findAllUserWithRides() {}


    // Récupére la liste des objets User passager avec leur réservation en liste simple avec tri et pagination.
    public function findAllUserWithBooking() {}
}
