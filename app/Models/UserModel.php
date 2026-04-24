<?php

namespace App\Models;

use App\Core\Constants;
use App\Core\Model;

class UserModel extends Model
{
    /** @var array<string, bool>|null */
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
        if ($this->hasUsersColumn('firebase_uid')) {
            $cols[] = 'firebase_uid';
        }
        if ($this->hasUsersColumn('has_set_password')) {
            $cols[] = 'has_set_password';
        }
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
        $cols = ['u.id', 'u.name', 'u.email'];
        if ($this->hasUsersColumn('firebase_uid')) {
            $cols[] = 'u.firebase_uid';
        }
        if ($this->hasUsersColumn('has_set_password')) {
            $cols[] = 'u.has_set_password';
        }
        if ($this->hasUsersColumn('phone')) {
            $cols[] = 'u.phone';
        }
        if ($this->hasUsersColumn('status')) {
            $cols[] = 'u.status';
        }
        if ($this->hasUsersColumn('created_at')) {
            $cols[] = 'u.created_at';
        }
        if ($this->hasUsersColumn('total_spent')) {
            $cols[] = 'u.total_spent';
        }
        if ($this->hasUsersColumn('last_level_up_time')) {
            $cols[] = 'u.last_level_up_time';
        }
        if ($this->hasUsersColumn('membership_level')) {
            $cols[] = 'u.membership_level';
            $cols[] = 'mr.level_name';
            $cols[] = 'mr.discount_percent';
            $cols[] = 'mr.points_multiplier';
        }
        if ($this->hasUsersColumn('is_level_locked')) {
            $cols[] = 'u.is_level_locked';
        }

        $sql = "SELECT " . implode(', ', $cols) . " FROM users u";
        if ($this->hasUsersColumn('membership_level')) {
            $sql .= ' LEFT JOIN membership_rules mr ON mr.level_key = u.membership_level';
        }
        $sql .= ' WHERE u.id = ? LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

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

    public function findByFirebaseUid(string $firebaseUid): ?array
    {
        if ($firebaseUid === '' || !$this->hasUsersColumn('firebase_uid')) {
            return null;
        }
        $cols = ['id', 'name', 'email', 'password', 'firebase_uid'];
        if ($this->hasUsersColumn('has_set_password')) {
            $cols[] = 'has_set_password';
        }
        if ($this->hasUsersColumn('status')) {
            $cols[] = 'status';
        }
        $sql = 'SELECT ' . implode(', ', $cols) . ' FROM users WHERE firebase_uid = ? LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$firebaseUid]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function linkFirebaseUid(int $userId, string $firebaseUid): bool
    {
        if ($firebaseUid === '' || !$this->hasUsersColumn('firebase_uid')) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            'UPDATE users SET firebase_uid = ? WHERE id = ? AND firebase_uid IS NULL'
        );
        $stmt->execute([$firebaseUid, $userId]);

        return $stmt->rowCount() > 0;
    }

    public function createWithFirebaseUid(string $name, string $email, string $firebaseUid): int
    {
        if (!$this->hasUsersColumn('firebase_uid')) {
            throw new \RuntimeException('users.firebase_uid column is required');
        }
        $hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        if ($this->hasUsersColumn('has_set_password')) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (name, email, password, firebase_uid, has_set_password) VALUES (?, ?, ?, ?, 0)'
            );
            $stmt->execute([$name, $email, $hash, $firebaseUid]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (name, email, password, firebase_uid) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$name, $email, $hash, $firebaseUid]);
        }

        return (int) $this->pdo->lastInsertId();
    }

    public function create(string $name, string $email, string $password): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($this->hasUsersColumn('has_set_password')) {
            $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password, has_set_password) VALUES (?, ?, ?, 1)");
            $stmt->execute([$name, $email, $hash]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hash]);
        }
        return (int) $this->pdo->lastInsertId();
    }

    public function verifyPassword(string $plainPassword, string $storedHash): bool
    {
        return $storedHash !== '' && password_verify($plainPassword, $storedHash);
    }

    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($this->hasUsersColumn('has_set_password')) {
            $stmt = $this->pdo->prepare("UPDATE users SET password = ?, has_set_password = 1 WHERE id = ?");
            $stmt->execute([$hash, $userId]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);
        }
        return $stmt->rowCount() > 0;
    }

    public function verifyPasswordForUser(int $userId, string $plainPassword): bool
    {
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $storedHash = $row['password'] ?? '';
        return $storedHash !== '' && $this->verifyPassword($plainPassword, $storedHash);
    }

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
            return (string) \App\Core\Config::get('messages.account.user_not_found');
        }
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $storedHash = $row['password'] ?? '';
        if (!$this->verifyPassword($oldPassword, $storedHash)) {
            return (string) \App\Core\Config::get('messages.account.password_current_wrong');
        }
        $minLen = (int) \App\Core\Config::get('min_password_length', 8);
        if (strlen($newPassword) < $minLen) {
            return sprintf(
                (string) \App\Core\Config::get('messages.account.password_new_min'),
                $minLen
            );
        }
        $this->updatePassword($userId, $newPassword);
        return null;
    }

    public function getTotalCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        return (int) $stmt->fetchColumn();
    }

    public function getListForAdmin(array $filters, int $page, int $perPage): array
    {
        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
        $membershipLevel = trim((string) ($filters['membership_level'] ?? ''));
        $offset = (max(1, $page) - 1) * $perPage;

        $whereParts = [];
        $params = [];
        if ($search !== '') {
            $whereParts[] = '(u.name LIKE ? OR u.email LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($membershipLevel !== '' && $this->hasUsersColumn('membership_level')) {
            $whereParts[] = 'u.membership_level = ?';
            $params[] = $membershipLevel;
        }

        $where = $whereParts ? (' WHERE ' . implode(' AND ', $whereParts)) : '';

        $countSql = "SELECT COUNT(*) FROM users u" . $where;
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $selectCols = ['u.*'];
        $join = '';
        if ($this->hasUsersColumn('membership_level')) {
            $selectCols[] = 'mr.level_name';
            $selectCols[] = 'mr.discount_percent';
            $selectCols[] = 'mr.points_multiplier';
            $join = ' LEFT JOIN membership_rules mr ON mr.level_key = u.membership_level';
        }

        $sql = 'SELECT ' . implode(', ', $selectCols) . ' FROM users u' . $join . $where . ' ORDER BY u.id DESC LIMIT ? OFFSET ?';
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

    public function updateMembershipLevel(int $id, ?string $membershipLevel): bool
    {
        if (!$this->hasUsersColumn('membership_level')) {
            return false;
        }

        $level = trim((string) $membershipLevel);
        if ($level === '') {
            $level = Constants::MEMBERSHIP_LEVEL_BRONZE;
        }

        $existsStmt = $this->pdo->prepare('SELECT 1 FROM users WHERE id = ? LIMIT 1');
        $existsStmt->execute([$id]);
        if ($existsStmt->fetchColumn() === false) {
            return false;
        }

        if ($this->hasUsersColumn('is_level_locked')) {
            $stmt = $this->pdo->prepare(
                'UPDATE users SET membership_level = ?, is_level_locked = 1, last_level_up_time = NOW() WHERE id = ?'
            );
        } else {
            $stmt = $this->pdo->prepare('UPDATE users SET membership_level = ?, last_level_up_time = NOW() WHERE id = ?');
        }
        $stmt->execute([$level, $id]);

        return true;
    }

    public function getMembershipRules(): array
    {
        try {
            $stmt = $this->pdo->query('SELECT * FROM membership_rules ORDER BY sort_order ASC, min_spent ASC');
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            throw new \Exception('Membership rules configuration missing in DB.', 0, $e);
        }
    }

    public function getMembershipRuleByLevel(string $levelKey): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM membership_rules WHERE level_key = ? LIMIT 1');
            $stmt->execute([$levelKey]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            throw new \Exception('Membership rules configuration missing in DB.', 0, $e);
        }
    }

    /**
     * Tier title for UI: uses i18n key account.points.level.{level_key} when present, else DB level_name.
     *
     * @param array<string, mixed> $rule membership_rules row (needs level_key; level_name optional fallback)
     */
    public static function membershipLevelDisplayName(array $rule): string
    {
        $levelKey = trim((string) ($rule['level_key'] ?? ''));
        $fallback = (string) ($rule['level_name'] ?? '');
        if ($levelKey === '') {
            return $fallback;
        }
        $msgKey = 'account.points.level.' . $levelKey;
        $msg = __m($msgKey);

        return $msg !== $msgKey ? $msg : $fallback;
    }

    public function getMembershipInfo(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, total_spent, points, total_points_earned, total_points_spent, membership_level
             FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$user) {
            return null;
        }

        $rules = $this->getMembershipRules();
        $currentLevel = (string) ($user['membership_level'] ?? Constants::MEMBERSHIP_LEVEL_BRONZE);

        $currentRule = null;
        $currentIndex = -1;
        foreach ($rules as $idx => $rule) {
            if ((string) ($rule['level_key'] ?? '') === $currentLevel) {
                $currentRule = $rule;
                $currentIndex = (int) $idx;
                break;
            }
        }
        if ($currentRule === null && !empty($rules)) {
            $currentRule = $rules[0];
            $currentLevel = (string) ($currentRule['level_key'] ?? Constants::MEMBERSHIP_LEVEL_BRONZE);
            $currentIndex = 0;
        }

        $totalSpent = (float) ($user['total_spent'] ?? 0);
        $nextRule = null;
        if ($currentIndex >= 0 && isset($rules[$currentIndex + 1])) {
            $nextRule = $rules[$currentIndex + 1];
        }

        $currentMinSpent = (float) ($currentRule['min_spent'] ?? 0);
        $nextMinSpent = (float) ($nextRule['min_spent'] ?? 0);

        $gapToNext = 0.0;
        if ($nextRule !== null) {
            $gapToNext = max(0.0, $nextMinSpent - $totalSpent);
        }

        $progressPercent = 100.0;
        if ($nextRule !== null) {
            $range = max(0.01, $nextMinSpent - $currentMinSpent);
            $progressPercent = min(100.0, max(0.0, (($totalSpent - $currentMinSpent) / $range) * 100));
        }

        return [
            'user' => $user,
            'rules' => $rules,
            'current_level_key' => $currentLevel,
            'current_rule' => $currentRule,
            'next_rule' => $nextRule,
            'gap_to_next' => $gapToNext,
            'progress_percent' => $progressPercent,
            'current_min_spent' => $currentMinSpent,
            'next_min_spent' => $nextMinSpent,
            'points_to_hkd_rate' => Constants::POINTS_PER_HKD,
        ];
    }

    /**
     * Member shelf price: base minus membership_rules.discount_percent (0–100), rounded to 2 decimals.
     */
    public static function getDiscountedPrice(float $basePrice, float $discountPercent): float
    {
        $p = max(0.0, min(100.0, $discountPercent));
        if ($p <= 0.0) {
            return round($basePrice, 2);
        }

        return round($basePrice * (1 - ($p / 100.0)), 2);
    }

    /**
     * Logged-in user's current tier discount percent (0–100); 0 for guest or missing data.
     */
    public function getMemberDiscountPercentForUser(int $userId): float
    {
        if ($userId <= 0) {
            return 0.0;
        }

        $membershipInfo = $this->getMembershipInfo($userId);
        if ($membershipInfo === null) {
            return 0.0;
        }

        $currentRule = $membershipInfo['current_rule'] ?? null;

        return max(0.0, min(100.0, (float) ($currentRule['discount_percent'] ?? 0)));
    }

    /**
     * Points earn multiplier from current tier (membership_rules.points_multiplier). 1.0 if guest, unknown user, or non-positive rule value.
     */
    public function getPointsMultiplierForUser(int $userId): float
    {
        if ($userId <= 0) {
            return 1.0;
        }

        $membershipInfo = $this->getMembershipInfo($userId);
        if ($membershipInfo === null) {
            return 1.0;
        }

        $currentRule = $membershipInfo['current_rule'] ?? null;
        $m = (float) ($currentRule['points_multiplier'] ?? 1.0);
        if ($m <= 0.0) {
            return 1.0;
        }

        return $m;
    }

    /**
     * Rule row index for a level_key (-1 if not found). Rules must be ordered low → high tier.
     *
     * @param array<int, array<string, mixed>> $rules
     */
    private function getLevelIndex(string $levelKey, array $rules): int
    {
        foreach ($rules as $idx => $rule) {
            if ((string) ($rule['level_key'] ?? '') === $levelKey) {
                return (int) $idx;
            }
        }

        return -1;
    }

    /**
     * Sync membership from total_spent when not locked. Upgrades only (no downgrade when spending drops).
     */
    public function refreshMembershipLevelBySpent(int $userId): string
    {
        if ($userId <= 0) {
            return Constants::MEMBERSHIP_LEVEL_BRONZE;
        }

        $user = $this->findById($userId);
        if (!$user) {
            return Constants::MEMBERSHIP_LEVEL_BRONZE;
        }

        if (!$this->hasUsersColumn('membership_level')) {
            return Constants::MEMBERSHIP_LEVEL_BRONZE;
        }

        $currentLevel = (string) ($user['membership_level'] ?? Constants::MEMBERSHIP_LEVEL_BRONZE);

        if ($this->hasUsersColumn('is_level_locked') && !empty($user['is_level_locked'])) {
            return $currentLevel;
        }

        $rules = $this->getMembershipRules();
        if ($rules === []) {
            return $currentLevel;
        }

        $currentSpent = (float) ($user['total_spent'] ?? 0);
        $spentBasedLevel = Constants::MEMBERSHIP_LEVEL_BRONZE;
        foreach ($rules as $rule) {
            if ($currentSpent >= (float) ($rule['min_spent'] ?? 0)) {
                $spentBasedLevel = (string) ($rule['level_key'] ?? Constants::MEMBERSHIP_LEVEL_BRONZE);
            }
        }

        $currentIndex = $this->getLevelIndex($currentLevel, $rules);
        $targetIndex = $this->getLevelIndex($spentBasedLevel, $rules);

        if ($targetIndex < 0) {
            $targetIndex = 0;
        }

        if ($targetIndex > $currentIndex) {
            if ($this->hasUsersColumn('last_level_up_time')) {
                $stmt = $this->pdo->prepare(
                    'UPDATE users SET membership_level = ?, last_level_up_time = NOW() WHERE id = ?'
                );
            } else {
                $stmt = $this->pdo->prepare('UPDATE users SET membership_level = ? WHERE id = ?');
            }
            $stmt->execute([$spentBasedLevel, $userId]);

            return $spentBasedLevel;
        }

        return $currentLevel;
    }

    /**
     * Clear admin level lock and re-sync membership from total_spent rules.
     */
    public function unlockLevel(int $userId): string
    {
        if ($userId <= 0) {
            return Constants::MEMBERSHIP_LEVEL_BRONZE;
        }

        $existsStmt = $this->pdo->prepare('SELECT 1 FROM users WHERE id = ? LIMIT 1');
        $existsStmt->execute([$userId]);
        if ($existsStmt->fetchColumn() === false) {
            return Constants::MEMBERSHIP_LEVEL_BRONZE;
        }

        if ($this->hasUsersColumn('is_level_locked')) {
            $stmt = $this->pdo->prepare('UPDATE users SET is_level_locked = 0 WHERE id = ?');
            $stmt->execute([$userId]);
        }

        return $this->refreshMembershipLevelBySpent($userId);
    }

    public function addPoints(int $userId, int $points, ?int $orderId = null, string $description = ''): bool
    {
        if ($points <= 0) {
            return false;
        }

        $pdo = $this->pdo;
        $started = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $started = true;
        }

        try {
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET points = points + ?, total_points_earned = total_points_earned + ?
                 WHERE id = ?'
            );
            $stmt->execute([$points, $points, $userId]);
            if ($stmt->rowCount() === 0) {
                throw new \RuntimeException('user_not_found');
            }

            $logStmt = $pdo->prepare(
                'INSERT INTO points_log (user_id, order_id, change_type, points_change, amount_hkd, description, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())'
            );
            $logStmt->execute([
                $userId,
                $orderId,
                'earn',
                $points,
                $points / Constants::POINTS_PER_HKD,
                $description,
            ]);

            if ($started) {
                $pdo->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if ($started && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }

    public function spendPoints(int $userId, int $points, ?int $orderId = null, string $description = ''): bool
    {
        if ($points <= 0) {
            return false;
        }

        $pdo = $this->pdo;
        $started = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $started = true;
        }

        try {
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET points = points - ?, total_points_spent = total_points_spent + ?
                 WHERE id = ? AND points >= ?'
            );
            $stmt->execute([$points, $points, $userId, $points]);
            if ($stmt->rowCount() === 0) {
                throw new \RuntimeException('insufficient_points');
            }

            $logStmt = $pdo->prepare(
                'INSERT INTO points_log (user_id, order_id, change_type, points_change, amount_hkd, description, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())'
            );
            $logStmt->execute([
                $userId,
                $orderId,
                'spend',
                -$points,
                $points / Constants::POINTS_PER_HKD,
                $description,
            ]);

            if ($started) {
                $pdo->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if ($started && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }

    public function getPointsBalance(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT points FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (int) $value : 0;
    }

    public function getPointsLogs(int $userId, int $limit = 30): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM points_log WHERE user_id = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(2, max(1, $limit), \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function hasEarnedPointsForOrder(int $userId, int $orderId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM points_log WHERE user_id = ? AND order_id = ? AND change_type = 'earn' LIMIT 1"
        );
        $stmt->execute([$userId, $orderId]);
        return $stmt->fetch() !== false;
    }
}
