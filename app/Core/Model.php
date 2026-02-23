<?php

namespace App\Core;

use PDO;

class Model
{
    protected \PDO $pdo;

    public function __construct(?\PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }
}
