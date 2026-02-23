<?php

namespace App\Models;

use App\Core\Model;

class Order extends Model
{
    private const ORDER_STATUS_PENDING = 'pending';

    /**
     * Create order and order items (single transaction; optional external order number and status).
     *
     * @param int $userId User ID
     * @param array{total: float, items: array<array{id: int, name: string, qty: int, price: float}>} $cart Cart data
     * @param string $paymentMethod Payment method
     * @param string $shippingAddress Shipping address
     * @param string|null $orderNumber Order number (null to auto-generate)
     * @param string $status Order status (default pending)
     * @param bool $inTransaction Caller manages transaction (true = no commit/rollBack)
     * @return int Order ID
     */
    public function create(
        int $userId,
        array $cart,
        string $paymentMethod,
        string $shippingAddress,
        ?string $orderNumber = null,
        string $status = self::ORDER_STATUS_PENDING,
        bool $inTransaction = false
    ): int {
        $items = $cart['items'] ?? [];
        $total = isset($cart['total']) ? (float) $cart['total'] : 0.0;
        if (count($items) === 0) {
            throw new \InvalidArgumentException('訂單項目不可為空');
        }
        if ($total < 0) {
            throw new \InvalidArgumentException('訂單金額不可為負');
        }

        $allowedStatus = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];
        if (!in_array($status, $allowedStatus, true)) {
            $status = self::ORDER_STATUS_PENDING;
        }

        $maxAttempts = ($orderNumber !== null && $orderNumber !== '') ? 1 : 3;
        $doCommit = !$inTransaction;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($doCommit) {
                $this->pdo->beginTransaction();
            }
            try {
                $num = $orderNumber;
                if ($num === null || $num === '') {
                    $config = require __DIR__ . '/../../config/app.php';
                    $prefix = (string) ($config['order_number_prefix'] ?? 'ORD');
                    $num = $prefix . date('YmdHis') . substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(6))), 0, 8);
                }
                $stmt = $this->pdo->prepare(
                    "INSERT INTO orders (user_id, order_number, total_amount, payment_method, shipping_address, status)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$userId, $num, $total, $paymentMethod, $shippingAddress, $status]);
                $orderId = intval($this->pdo->lastInsertId());

                $stmt2 = $this->pdo->prepare(
                    "INSERT INTO order_items (order_id, item_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)"
                );
                foreach ($items as $item) {
                    $stmt2->execute([
                        $orderId,
                        intval($item['id']),
                        (string) $item['name'],
                        intval($item['qty']),
                        floatval($item['price']),
                    ]);
                }
                if ($doCommit) {
                    $this->pdo->commit();
                }
                return $orderId;
            } catch (\PDOException $e) {
                if ($doCommit) {
                    $this->pdo->rollBack();
                }
                if (($e->getCode() === '23000' || $e->getCode() === 23000) && $attempt < $maxAttempts) {
                    continue;
                }
                throw $e;
            } catch (\Throwable $e) {
                if ($doCommit) {
                    $this->pdo->rollBack();
                }
                throw $e;
            }
        }
        throw new \RuntimeException('無法產生唯一訂單編號，請稍後再試');
    }
}
