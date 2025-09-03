<?php

namespace App\Repositories;

use App\Repositories\CarRepository;
use App\Repositories\RideRepository;
use App\Repositories\UserRepository;
use App\Repositories\BookingRepository;
use App\Models\Car;
use App\Models\Ride;
use App\Models\User;
use App\Models\Booking;
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

    private function hydrateUserRelation() {}

    // Mappings des autres repositories????

    //  ------ Récupérations spécifiques ---------

}

//Une fonction qui retourne un utilisateur en objet avec ses voitures en liste
//Une fonction qui retourne un utilisateur en objet avec ses trajets en liste
//Une fonction qui retourne un utilisateur en objet avec ses réservations en liste

//Une fonction qui retourne tous les utilisateurs en objet avec leur voiture en liste
//Une fonction qui retourne tous les utilisateurs en objet avec leur trajet en liste
//Une fonction qui retourne tous les utilisateurs en objet avec leur réservation en liste


/*
// Récupére un utilisateur avec ses voitures  - A VERIFIER
    public function findUserWithCars(
        int $ownerId,
        string $orderBy = 'user_id',
        string $orderDirection = 'DESC',
        int $limit = 20,
        int $offset = 0
    ): ?User {

        // Vérifier si l'ordre et la direction sont définis et valides.
        [$orderBy, $orderDirection] = $this->sanitizeOrder(
            $orderBy,
            $orderDirection,
            'user_id'
        );

        // Construction du SQL
        $sql = "SELECT u.*
                    c.car_id, c.car_brand, c.car_model, c.car_power
                FROM {$this->table} u
                LEFT JOIN cars c ON u.user_id = c.user_id
                WHERE user_id = :userId";

        $sql .= " ORDER BY c.$orderBy $orderDirection 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;


        // Préparation de la requête
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':userId', $ownerId, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => $this->hydrateUser((array) $row), $rows);
    }
*/