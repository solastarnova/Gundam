<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Models\CartModel;
use App\Models\Order;
use App\Models\OrderModel;

class OrderService
{
    private Order $order;
    private OrderModel $orderModel;
    private CartModel $cartModel;

    public function __construct()
    {
        $this->order = new Order();
        $this->orderModel = new OrderModel();
        $this->cartModel = new CartModel();
    }

    public function generateOrderNumber(): string
    {
        $prefix = (string) Config::get('order_number_prefix', 'ORD');
        $date = date('Ymd');
        $maxAttempts = 10;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $random = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $orderNumber = $prefix . $date . $random;
            if (!$this->orderModel->orderNumberExists($orderNumber)) {
                return $orderNumber;
            }
        }

        return $prefix . $date . substr(bin2hex(random_bytes(4)), 0, 8);
    }

    /**
     * Create order from cart items; optionally clear cart in same transaction.
     *
     * @param int    $userId
     * @param array  $cartItems
     * @param float  $totalAmount
     * @param string $paymentMethod
     * @param string $shippingAddress
     * @param string $status
     * @param string|null $orderNumber
     * @param bool   $clearCart
     * @param string|null $paymentProvider
     * @param string|null $paymentReference
     * @param float|null $walletAmountUsed
     * @return array{order_id: int, order_number: string}
     */
    public function createOrderFromCart(
        int $userId,
        array $cartItems,
        float $totalAmount,
        string $paymentMethod,
        string $shippingAddress,
        string $status = 'pending',
        ?string $orderNumber = null,
        bool $clearCart = true,
        ?string $paymentProvider = null,
        ?string $paymentReference = null,
        ?float $walletAmountUsed = null
    ): array {
        if (empty($cartItems)) {
            throw new \InvalidArgumentException('購物車是空的');
        }
        if ($totalAmount <= 0) {
            throw new \InvalidArgumentException('訂單金額無效');
        }

        if ($orderNumber === null || $orderNumber === '') {
            $orderNumber = $this->generateOrderNumber();
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $walletUsed = $walletAmountUsed !== null ? max(0.0, $walletAmountUsed) : 0.0;

            $orderId = $this->order->create(
                $userId,
                ['total' => $totalAmount, 'items' => $cartItems],
                $paymentMethod,
                $shippingAddress,
                $orderNumber,
                $status,
                true,
                $paymentProvider,
                $paymentReference
            );

            if ($walletUsed > 0.0) {
                $desc = sprintf('訂單 #%s 使用錢包支付', $orderNumber);
                WalletService::deductWithinTransaction($pdo, $userId, $walletUsed, 'payment', $orderId, $desc);
            }

            if ($clearCart) {
                $this->cartModel->clearCart($userId);
            }

            $pdo->commit();

            return [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

