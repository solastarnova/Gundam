<?php

namespace App\Models;

use App\Core\Model;

class AdminModel extends Model
{
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    public function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        return password_verify($plainPassword, $hashedPassword);
    }
    
    public function updateLastLogin(int $adminId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$adminId]);
    }
    
    public function changePassword(int $adminId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
        return $stmt->execute([$hash, $adminId]);
    }
}