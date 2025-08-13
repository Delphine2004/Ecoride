<?php

namespace App\Models;

use PDO;
use InvalidArgumentException;


/** 
 * Cette classe représente une voiture dans la base de données.
 * Elle contient également la logique pour la sauvegarde, la récupération et la modification
 * */

class Car extends BaseModel
{

    /**
     * @var string Le nom de la table en base de données.
     */

    protected string $table = 'cars';


    /**
     * @var array Propriétés privées représentant les colonnes de la table `cars`.
     * L'encapsulation (via `private`) garantit que les données sont protégées et
     * ne peuvent être modifiées que par les méthodes de cette classe (setters).
     */
    private ?int $id = null;
    private string $brand;
    private string $model;
    private string $color;
    private int $year;
    private $power; // Comment faire pour enum???
    private int $seatsNumber;
    private string $registrationNumber;
    private \DateTime $registrationDate;
}
