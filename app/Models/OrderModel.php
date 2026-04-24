<?php

namespace App\Models;

use App\Core\Model;
use App\Services\OrderStatusService;
use App\Services\InventoryService;

/**
 * Order queries: storefront lists, admin list/detail helpers, status updates.
 */
class OrderModel extends Model
{
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

    public function getOrderItems(int $orderId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT oi.*, i.image_path AS item_image_path
             FROM order_items oi
             LEFT JOIN items i ON i.id = oi.item_id
             WHERE oi.order_id = ?
             ORDER BY oi.id"
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function updateStatus(int $orderId, int $userId, string $status): bool
    {
        if (!OrderStatusService::isAllowed($status)) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$status, $orderId, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function updateStatusByAdmin(int $orderId, string $status): bool
    {
        if (!OrderStatusService::isAllowed($status)) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $orderId]);
    }

    public function orderNumberExists(string $orderNumber): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM orders WHERE order_number = ? LIMIT 1");
        $stmt->execute([$orderNumber]);
        return $stmt->fetch() !== false;
    }

    public function findByUserIdAndPaymentReference(int $userId, string $paymentProvider, string $paymentReference): ?array
    {
        $paymentProvider = trim($paymentProvider);
        $paymentReference = trim($paymentReference);
        if ($userId <= 0 || $paymentProvider === '' || $paymentReference === '') {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT * FROM orders WHERE user_id = ? AND payment_provider = ? AND payment_reference = ? LIMIT 1'
        );
        $stmt->execute([$userId, $paymentProvider, $paymentReference]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

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

    public function replenishStockForOrder(int $orderId): void
    {
        InventoryService::replenishStockForOrder($this->pdo, $orderId);
    }
}
