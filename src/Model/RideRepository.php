<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\Ride;
use PDO;

use App\Config\Database; // pas besoin de database car instancié par BaseModel
use PDOException; // Utilise si try et catch mais pas necessaire car utilisé dans DataBase


class RideRepository extends BaseModel
{

    /**
     * @var string Le nom de la table en BDD
     */

    protected string $table = 'rides';

    /**
     * Hydrate un tableau BDD en objet Ride
     *
     * @param array $data
     * @return Ride
     */
    private function hydrateRide(array $data): Ride
    {
        return new Ride(
            id: $data['ride_id'] ?? null,
            driver: $data['driver_id'] ?? null,
            departureDateTime: $data['departure_date_time'] ?? null,
            departurePlace: $data['departure_place'] ?? '',
            arrivalDateTime: $data['arrival_date_time'] ?? null,
            arrivalPlace: $data['arrival_place'] ?? null,
            duration: $data['duration_minute'] ?? null,
            price: $data['price'] ?? null,
            availableSeats: $data['available_seats'] ?? null,
            status: $data['status'] ?? '',
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,

        );
    }

    /**
     * Trouver un trajet par des critéres précis
     *
     * @param array $criteria
     * @return Ride[]
     */
    public function findRideByCriteria(array $criteria = []): array
    {

        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($criteria['departure_date_time'])) {
            $sql .= " AND departure_date_time >= :departure_date_time";
            $params[':departure_date_time'] = $criteria['departure_date_time'];
        }
        if (!empty($criteria['departure_place'])) {
            $sql .= " AND departure_place LIKE :departure_place";
            $params[':departure_place'] = "%{$criteria['departure_place']}%";
        }
        if (!empty($criteria['arrival_place'])) {
            $sql .= " AND arrival_place LIKE :arrival_place";
            $params[':arrival_place'] = "%{$criteria['arrival_place']}%";
        }
        if (isset($criteria['available_seats']) && $criteria['available_seats'] > 0) {
            $sql .= " AND available_seats >= :available_seats";
            $params[':available_seats'] = $criteria['available_seats'];
        }

        $sql .= " ORDER BY departure_date_time ASC LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(fn($row) => $this->hydrateRide($row), $rows);
    }
}
