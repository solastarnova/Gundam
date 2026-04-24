<?php

namespace App\Services;

use PDO;

/**
 * 集中處理訂單相關庫存異動。
 */
class InventoryService
{
    public static function replenishStockForOrder(PDO $pdo, int $orderId): void
    {
        $stmt = $pdo->prepare(
            'SELECT item_id, quantity FROM order_items WHERE order_id = ?'
        );
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $updateStmt = $pdo->prepare(
            'UPDATE items SET stock_quantity = stock_quantity + ? WHERE id = ?'
        );
        foreach ($items as $item) {
            $updateStmt->execute([(int) ($item['quantity'] ?? 0), (int) ($item['item_id'] ?? 0)]);
        }
    }
}

