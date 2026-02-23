<?php

namespace App\Models;

use App\Core\Model;

class OrderModel extends Model
{
    /**
     * Get user order list (with item_count).
     *
     * @param int $userId User ID
     * @param int|null $limit Max count, null for no limit
     * @return list<array<string, mixed>>
     */
    public function getUserOrders(int $userId, ?int $limit = null): array
    {
        $sql = "SELECT o.*,
                (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
                FROM orders o
                WHERE o.user_id = ?
                ORDER BY o.created_at DESC";
        if ($limit !== null) {
            $sql .= " LIMIT " . (int) $limit;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function getOrderById(int $orderId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$orderId, $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * 取得訂單明細
     *
     * @param int $orderId 訂單 ID
     * @return array
     */
    public function getOrderItems(int $orderId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Update order status (own orders only).
     *
     * @param int $orderId Order ID
     * @param int $userId User ID
     * @param string $status Status
     * @return bool
     */
    public function updateStatus(int $orderId, int $userId, string $status): bool
    {
        $allowed = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$status, $orderId, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function orderNumberExists(string $orderNumber): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM orders WHERE order_number = ? LIMIT 1");
        $stmt->execute([$orderNumber]);
        return $stmt->fetch() !== false;
    }

    /**
     * @return array{pending: int, paid: int, shipped: int, completed: int, cancelled: int}
     */
    public function getUserOrderStats(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT status, COUNT(*) AS cnt FROM orders WHERE user_id = ? GROUP BY status");
        $stmt->execute([$userId]);
        $stats = ['pending' => 0, 'paid' => 0, 'shipped' => 0, 'completed' => 0, 'cancelled' => 0];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $s = $row['status'] ?? '';
            if (isset($stats[$s])) {
                $stats[$s] = (int) $row['cnt'];
            }
        }
        return $stats;
    }
}
