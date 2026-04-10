<?php

namespace App\Services;

use App\Core\Database;

/**
 * User wallet balance and ledger rows (must use provided PDO when inside a transaction).
 */
class WalletService
{
    public function getBalance(int $userId): float
    {
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->prepare('SELECT balance FROM user_wallets WHERE user_id = ?');
            $stmt->execute([$userId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ? (float) $row['balance'] : 0.0;
        } catch (\Throwable $e) {
            error_log('WalletService::getBalance failed: ' . $e->getMessage());
            return 0.0;
        }
    }

    public static function deductWithinTransaction(
        \PDO $pdo,
        int $userId,
        float $amount,
        string $type,
        ?int $orderId = null,
        string $description = ''
    ): void {
        if ($amount <= 0) {
            return;
        }
        $stmt = $pdo->prepare('UPDATE user_wallets SET balance = balance - ? WHERE user_id = ? AND balance >= ?');
        $stmt->execute([$amount, $userId, $amount]);
        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException('錢包餘額不足');
        }
        $stmtLog = $pdo->prepare(
            'INSERT INTO user_wallet_transactions (user_id, amount, type, order_id, description, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmtLog->execute([
            $userId,
            -$amount,
            $type,
            $orderId,
            $description,
        ]);
    }

    public static function addCreditWithinTransaction(
        \PDO $pdo,
        int $userId,
        float $amount,
        string $type,
        ?int $orderId = null,
        string $description = ''
    ): void {
        if ($amount <= 0) {
            return;
        }
        $stmt = $pdo->prepare(
            'INSERT INTO user_wallets (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)'
        );
        $stmt->execute([$userId, $amount]);

        $stmtLog = $pdo->prepare(
            'INSERT INTO user_wallet_transactions (user_id, amount, type, order_id, description, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmtLog->execute([
            $userId,
            $amount,
            $type,
            $orderId,
            $description,
        ]);
    }

    public static function hasRefundForOrder(\PDO $pdo, int $userId, int $orderId): bool
    {
        $stmt = $pdo->prepare(
            "SELECT id FROM user_wallet_transactions WHERE user_id = ? AND order_id = ? AND type = 'refund' LIMIT 1"
        );
        $stmt->execute([$userId, $orderId]);

        return $stmt->fetch() !== false;
    }
}


