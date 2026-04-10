<?php

namespace App\Core;

use PDO;

/**
 * Active-record style base: injectable PDO, defaults to Database::getConnection().
 */
class Model
{
    protected \PDO $pdo;

    public function __construct(?\PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
}
