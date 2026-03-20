<?php

namespace App\Models;

use App\Core\Model;

/**
 * User model: password stored with password_hash (PASSWORD_DEFAULT) in users.password.
 */
class UserModel extends Model
{
    /**
     * Cache for existing users table columns.
     *
     * Some environments may not have run the latest migrations yet.
     * We detect columns at runtime to avoid SQL 1054 fatal errors.
     *
     * @var array<string, bool>|null
     */
    private static ?array $usersColumns = null;

    private function ensureUsersColumnsLoaded(): void
    {
        if (self::$usersColumns !== null) {
            return;
        }
        self::$usersColumns = [];
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM users");
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $name = (string) ($row['Field'] ?? '');
                if ($name !== '') {
                    self::$usersColumns[$name] = true;
                }
            }
        } catch (\Throwable $e) {
            // SHOW COLUMNS 失敗時視為欄位不存在
            self::$usersColumns = [];
        }
    }

    private function hasUsersColumn(string $column): bool
    {
        $this->ensureUsersColumnsLoaded();
        return self::$usersColumns[$column] ?? false;
    }

    public function findByEmail(string $email): ?array
    {
        $cols = ['id', 'name', 'email', 'password'];
        if ($this->hasUsersColumn('status')) {
            $cols[] = 'status';
        }
        $sql = "SELECT " . implode(', ', $cols) . " FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $cols = ['id', 'name', 'email'];
        if ($this->hasUsersColumn('status')) {
            $cols[] = 'status';
        }
        if ($this->hasUsersColumn('created_at')) {
            $cols[] = 'created_at';
        }
        $stmt = $this->pdo->prepare("SELECT " . implode(', ', $cols) . " FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Update user status (admin: active / disabled).
     *
     * @param int    $id
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        if (!$this->hasUsersColumn('status')) {
            return false;
        }
        if (!in_array($status, ['active', 'disabled'], true)) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        return $stmt->rowCount() > 0;
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function create(string $name, string $email, string $password): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $hash]);
        return (int) $this->pdo->lastInsertId();
    }

    public function verifyPassword(string $plainPassword, string $storedHash): bool
    {
        return $storedHash !== '' && password_verify($plainPassword, $storedHash);
    }

    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Verify user password by id (used by account settings forms).
     */
    public function verifyPasswordForUser(int $userId, string $plainPassword): bool
    {
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $storedHash = $row['password'] ?? '';
        return $storedHash !== '' && $this->verifyPassword($plainPassword, $storedHash);
    }

    /**
     * Update user's email.
     */
    public function updateEmail(int $userId, string $newEmail): bool
    {
        if ($newEmail === '') {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$newEmail, $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Update user's phone (optional field).
     */
    public function updatePhone(int $userId, string $phone): bool
    {
        if (!$this->hasUsersColumn('phone')) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $stmt->execute([$phone, $userId]);
        return $stmt->rowCount() > 0;
    }

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
        $minLen = (int) \App\Core\Config::get('min_password_length', 8);
        if (strlen($newPassword) < $minLen) {
            return "新密碼至少 {$minLen} 個字元";
        }
        $this->updatePassword($userId, $newPassword);
        return null;
    }

    public function getTotalCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get paginated user list for admin (optional search).
     *
     * @param array $filters
     * @param int   $page
     * @param int   $perPage
     * @return array{total: int, rows: list<array<string, mixed>>}
     */
    public function getListForAdmin(array $filters, int $page, int $perPage): array
    {
        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
        $offset = (max(1, $page) - 1) * $perPage;

        $where = '';
        $params = [];
        if ($search !== '') {
            $where = ' WHERE name LIKE ? OR email LIKE ?';
            $params = ["%{$search}%", "%{$search}%"];
        }

        $countSql = "SELECT COUNT(*) FROM users" . $where;
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $sql = "SELECT * FROM users" . $where . " ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $idx = 1;
        foreach ($params as $v) {
            $stmt->bindValue($idx++, $v);
        }
        $stmt->bindValue($idx++, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue($idx++, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        return ['total' => $total, 'rows' => $rows];
    }
}
