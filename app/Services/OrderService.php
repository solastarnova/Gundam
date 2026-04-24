<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Models\CartModel;
use App\Models\OrderWriteModel;
use App\Models\OrderModel;
use App\Models\UserModel;
use PDOException;

/**
 * 處理購物車轉訂單流程（編號、入庫、錢包扣款與積分副作用）。
 */
class OrderService
{
    private OrderWriteModel $order;
    private OrderModel $orderModel;
    private CartModel $cartModel;

    public function __construct()
    {
        $this->order = new OrderWriteModel();
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

    /** 將購物車快照寫入訂單主檔、明細與付款相關資料。 */
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
        ?float $walletAmountUsed = null,
        ?int $pointsAmountUsed = null
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
            $pointsUsed = $pointsAmountUsed !== null ? max(0, $pointsAmountUsed) : 0;
            $userModel = new UserModel($pdo);

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
            if ($pointsUsed > 0) {
                $desc = sprintf('訂單 #%s 使用積分折抵', $orderNumber);
                if (!$userModel->spendPoints($userId, $pointsUsed, $orderId, $desc)) {
                    throw new \RuntimeException('points_deduct_failed');
                }
            }

            // paid/completed: bump total_spent and refresh membership; completed also awards points below
            if (in_array($status, [OrderStatusService::PAID, OrderStatusService::COMPLETED], true)) {
                $this->increaseTotalSpent($userId, $totalAmount);
                $userModel->refreshMembershipLevelBySpent($userId);
            }

            if ($status === OrderStatusService::COMPLETED) {
                $this->awardPointsForOrder($userId, $orderId, $totalAmount);
            }

            if ($clearCart) {
                $this->cartModel->clearCart($userId);
            }

            $pdo->commit();

            return [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
            ];
        } catch (PDOException $e) {
            $pdo->rollBack();
            if (
                $this->isDuplicatePaymentReferenceException($e)
                && $paymentProvider !== null
                && $paymentProvider !== ''
                && $paymentReference !== null
                && trim((string) $paymentReference) !== ''
            ) {
                $existing = $this->orderModel->findByUserIdAndPaymentReference(
                    $userId,
                    (string) $paymentProvider,
                    trim((string) $paymentReference)
                );
                if ($existing !== null) {
                    $this->cartModel->clearCart($userId);

                    return [
                        'order_id' => (int) $existing['id'],
                        'order_number' => (string) $existing['order_number'],
                        'idempotent' => true,
                    ];
                }
            }
            throw $e;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function isDuplicatePaymentReferenceException(PDOException $e): bool
    {
        $code = (string) $e->getCode();
        if ($code === '23000') {
            return true;
        }
        $info = $e->errorInfo ?? [];
        if (isset($info[1]) && (int) $info[1] === 1062) {
            return true;
        }
        $msg = $e->getMessage();

        return str_contains($msg, 'Duplicate') || str_contains($msg, 'uq_orders_user_provider_payment_ref');
    }

    private function increaseTotalSpent(int $userId, float $amount): void
    {
        if ($userId <= 0 || $amount <= 0) {
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET total_spent = total_spent + ? WHERE id = ?');
        $stmt->execute([$amount, $userId]);
    }

    private function awardPointsForOrder(int $userId, int $orderId, float $orderAmount): void
    {
        if ($userId <= 0 || $orderId <= 0 || $orderAmount <= 0) {
            return;
        }

        $userModel = new UserModel();
        if ($userModel->hasEarnedPointsForOrder($userId, $orderId)) {
            return;
        }

        $multiplier = $userModel->getPointsMultiplierForUser($userId);

        $pointsToAdd = (int) floor($orderAmount * $multiplier);
        if ($pointsToAdd <= 0) {
            return;
        }

        $desc = sprintf('訂單 #%d 消費獲得積分 (x%s)', $orderId, rtrim(rtrim(number_format($multiplier, 2, '.', ''), '0'), '.'));
        $userModel->addPoints($userId, $pointsToAdd, $orderId, $desc);
    }
}

