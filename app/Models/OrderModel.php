<?php

namespace App\Models;

use App\Core\Model;
use App\Services\OrderStatusService;

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
     * Get order items by order ID.
     *
     * @param int $orderId
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
        if (!OrderStatusService::isAllowed($status)) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$status, $orderId, $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Update order status (admin; no user_id check; status must be in config order_status.allowed).
     *
     * @param int    $orderId
     * @param string $status
     * @return bool
     */
    public function updateStatusByAdmin(int $orderId, string $status): bool
    {
        if (!OrderStatusService::isAllowed($status)) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $orderId]);
        return $stmt->rowCount() > 0;
    }

    public function orderNumberExists(string $orderNumber): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM orders WHERE order_number = ? LIMIT 1");
        $stmt->execute([$orderNumber]);
        return $stmt->fetch() !== false;
    }

    /**
     * Get order count per user ID (for admin user list).
     *
     * @param array $userIds
     * @return array<int, int> [user_id => count]
     */
    public function getOrderCountByUserIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }
        $ids = array_map('intval', array_values($userIds));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT user_id, COUNT(*) AS cnt FROM orders WHERE user_id IN ($placeholders) GROUP BY user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ids);
        $result = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[(int) $row['user_id']] = (int) $row['cnt'];
        }
        return $result;
    }

    /**
     * Get paginated order list for admin (optional status filter).
     *
     * @param array $filters
     * @param int   $page
     * @param int   $perPage
     * @return array{total: int, rows: list<array<string, mixed>>}
     */
    public function getListForAdmin(array $filters, int $page, int $perPage): array
    {
        $status = isset($filters['status']) ? trim((string) $filters['status']) : '';
        $offset = (max(1, $page) - 1) * $perPage;

        $where = '';
        $params = [];
        if ($status !== '') {
            $where = ' WHERE o.status = ?';
            $params = [$status];
        }

        $countSql = "SELECT COUNT(*) FROM orders" . ($status !== '' ? ' WHERE status = ?' : '');
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $sql = "SELECT o.*, u.name AS user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id" . $where . " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
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

    /**
     * Get order count by status (all orders; for admin).
     *
     * @return array<string, int>
     */
    public function getAllOrderStats(): array
    {
        $stmt = $this->pdo->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status");
        $allowed = OrderStatusService::allowed();
        $stats = array_fill_keys($allowed, 0);
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $s = $row['status'] ?? '';
            if (isset($stats[$s])) {
                $stats[$s] = (int) $row['cnt'];
            }
        }
        return $stats;
    }

    /**
     * Get order count by status for one user.
     *
     * @param int $userId
     * @return array<string, int>
     */
    public function getUserOrderStats(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT status, COUNT(*) AS cnt FROM orders WHERE user_id = ? GROUP BY status");
        $stmt->execute([$userId]);
        $allowed = OrderStatusService::allowed();
        $stats = array_fill_keys($allowed, 0);
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $s = $row['status'] ?? '';
            if (isset($stats[$s])) {
                $stats[$s] = (int) $row['cnt'];
            }
        }
        return $stats;
    }

    public function getTotalCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM orders");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get recent orders for admin dashboard.
     *
     * @param int $limit
     * @return list<array<string, mixed>>
     */
    public function getRecentOrders(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.*, u.name as user_name
             FROM orders o
             LEFT JOIN users u ON o.user_id = u.id
             ORDER BY o.created_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Replenish inventory for an order that was returned/cancelled.
     *
     * Stock is deducted during order creation; when admin sets status to
     * `cancelled`, we reverse the deduction by adding back order_items.quantity.
     *
     * This method assumes caller controls when to call it (e.g. only on
     * transition into `cancelled`).
     */
    public function replenishStockForOrder(int $orderId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE items i
             JOIN order_items oi ON i.id = oi.item_id
             SET i.stock_quantity = i.stock_quantity + oi.quantity
             WHERE oi.order_id = ?"
        );
        $stmt->execute([$orderId]);
    }
}
