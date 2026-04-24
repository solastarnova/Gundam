<?php

namespace App\Core;

use PDO;

/**
 * 提供可注入 PDO 的基礎模型類別（預設使用 Database::getConnection()）。
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
