<?php

namespace App\Models;

use App\Core\Model;

class AdminModel extends Model
{
    /**
     * 根据用户名查找管理员
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * 根据ID查找管理员
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * 验证密码
     */
    public function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        return password_verify($plainPassword, $hashedPassword);
    }
    
    /**
     * 更新最后登录时间
     */
    public function updateLastLogin(int $adminId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$adminId]);
    }
    
    /**
     * 修改密码
     */
    public function changePassword(int $adminId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
        return $stmt->execute([$hash, $adminId]);
    }
}