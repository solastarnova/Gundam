<?php

namespace App\Models;

use App\Core\Config;
use App\Core\Model;
use App\Services\OrderStatusService;

/**
 * Write path for orders: header + order_items rows inside optional outer transaction.
 */
class OrderWriteModel extends Model
{
    /**
     * @param array{
     *   items: array<int, array{id:int|string, qty:int|string, name?:string, price:int|float|string}>,
     *   total: int|float|string
     * } $cart
     */
    public function create(
        int $userId,
        array $cart,
        string $paymentMethod,
        string $shippingAddress,
        string $orderNumber,
        ?string $status = null,
        bool $inTransaction = false,
        ?string $paymentProvider = null,
        ?string $paymentReference = null
    ): int {
        $items = $cart['items'] ?? [];
        $total = isset($cart['total']) ? (float) $cart['total'] : 0.0;
        if (count($items) === 0) {
            throw new \InvalidArgumentException('訂單項目不可為空');
        }
        if ($total < 0) {
            throw new \InvalidArgumentException('訂單金額不可為負');
        }
        if ($orderNumber === '') {
            throw new \InvalidArgumentException('訂單編號不可為空，請使用 OrderService::generateOrderNumber() 產生');
        }

        $defaultStatus = OrderStatusService::default();
        if ($status === null || !OrderStatusService::isAllowed($status)) {
            $status = $defaultStatus;
        }

        $doCommit = !$inTransaction;
        if ($doCommit) {
            $this->pdo->beginTransaction();
        }
        try {
            $required = [];
            foreach ($items as $item) {
                $itemId = (int) ($item['id'] ?? 0);
                $qty = (int) ($item['qty'] ?? 0);
                if ($itemId <= 0 || $qty <= 0) {
                    continue;
                }
                if (!isset($required[$itemId])) {
                    $required[$itemId] = 0;
                }
                $required[$itemId] += $qty;
            }
            if ($required) {
                $placeholders = implode(',', array_fill(0, count($required), '?'));
                $stmtStock = $this->pdo->prepare(
                    "SELECT id, stock_quantity FROM items WHERE id IN ($placeholders) FOR UPDATE"
                );
                $stmtStock->execute(array_keys($required));
                $stockById = [];
                while ($row = $stmtStock->fetch(\PDO::FETCH_ASSOC)) {
                    $stockById[(int) $row['id']] = (int) $row['stock_quantity'];
                }
                foreach ($required as $itemId => $qtyNeeded) {
                    $currentStock = $stockById[$itemId] ?? 0;
                    if ($currentStock < $qtyNeeded) {
                        $message = (string) Config::get('messages.order.insufficient_stock', '購買的貨品庫存不足');
                        throw new \InvalidArgumentException($message);
                    }
                }
                $stmtUpdateStock = $this->pdo->prepare(
                    "UPDATE items SET stock_quantity = stock_quantity - ? WHERE id = ?"
                );
                foreach ($required as $itemId => $qtyNeeded) {
                    $stmtUpdateStock->execute([$qtyNeeded, $itemId]);
                }
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO orders (user_id, order_number, total_amount, payment_method, payment_provider, payment_reference, shipping_address, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $userId,
                $orderNumber,
                $total,
                $paymentMethod,
                $paymentProvider,
                $paymentReference,
                $shippingAddress,
                $status,
            ]);
            $orderId = intval($this->pdo->lastInsertId());

            $stmt2 = $this->pdo->prepare(
                "INSERT INTO order_items (order_id, item_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)"
            );
            $stmtUserItem = $this->pdo->prepare(
                "INSERT INTO user_item (user_id, item_id, quantity, status, date_time) VALUES (?, ?, ?, ?, NOW())"
            );
            foreach ($items as $item) {
                $itemId = intval($item['id']);
                $qty = intval($item['qty']);
                $stmt2->execute([
                    $orderId,
                    $itemId,
                    (string) $item['name'],
                    $qty,
                    floatval($item['price']),
                ]);
                $itemStatus = ReviewModel::STATUS_CONFIRMED;
                for ($i = 0; $i < $qty; $i++) {
                    $stmtUserItem->execute([$userId, $itemId, 1, $itemStatus]);
                }
            }
            if ($doCommit) {
                $this->pdo->commit();
            }
            return $orderId;
        } catch (\PDOException $e) {
            if ($doCommit) {
                $this->pdo->rollBack();
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($doCommit) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
