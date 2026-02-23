<?php

namespace App\Models;

use App\Core\Model;

/**
 * User model: password stored with password_hash (PASSWORD_DEFAULT) in users.password.
 */
class UserModel extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, name, email FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    /** Create user (password stored with password_hash). */
    public function create(string $name, string $email, string $password): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $hash]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Verify password (password_verify). */
    public function verifyPassword(string $plainPassword, string $storedHash): bool
    {
        return $storedHash !== '' && password_verify($plainPassword, $storedHash);
    }

    /** Update password by email (password_hash). */
    public function updatePasswordByEmail(string $email, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hash, $email]);
        return $stmt->rowCount() > 0;
    }

    /** Update password by user id (password_hash). */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $userId]);
        return $stmt->rowCount() > 0;
    }

    /** Verify old password and set new one (account settings). */
    public function changePassword(int $userId, string $oldPassword, string $newPassword): ?string
    {
        $user = $this->findById($userId);
        if (!$user) {
            return '用戶不存在';
        }
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $storedHash = $row['password'] ?? '';
        if (!$this->verifyPassword($oldPassword, $storedHash)) {
            return '目前密碼不正確';
        }
        $config = require __DIR__ . '/../../config/app.php';
        $minLen = (int) ($config['min_password_length'] ?? 8);
        if (strlen($newPassword) < $minLen) {
            return "新密碼至少 {$minLen} 個字元";
        }
        $this->updatePassword($userId, $newPassword);
        return null;
    }
}
