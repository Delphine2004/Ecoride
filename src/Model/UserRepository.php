<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\User;
use App\Config\Database;
use PDO;
use PDOException;

class UserRepository extends BaseModel
{
    /**
     * @var string Le nom de la table en BDD
     */

    protected string $table = 'users';
}
