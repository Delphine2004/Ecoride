<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\Car;
use App\Config\Database;
use PDO;
use PDOException;

class CarRepository extends BaseModel
{
    /**
     * @var string Le nom de la table en BDD.
     */

    protected string $table = 'cars';
}
