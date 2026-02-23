<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\CartModel;
use App\Models\Order;
use App\Models\OrderModel;
use App\Services\PaymentService;
use App\Services\ShippingService;

class PaymentController extends Controller
{
    private PaymentService $paymentService;
    private CartModel $cartModel;
    private OrderModel $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->paymentService = new PaymentService();
        $this->cartModel = new CartModel();
        $this->orderModel = new OrderModel();
    }

    public function getPublishableKey(): void
    {
        $this->setupJsonApi();

        $key = getenv('STRIPE_PUBLISHABLE_KEY') ?: '';
        if ($key === '') {
            $this->json(['success' => false, 'message' => 'Stripe 配置錯誤'], 500);
            return;
        }
        $this->json(['success' => true, 'publishable_key' => $key]);
    }

    public function createIntent(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $cartItems = $this->cartModel->getCartItems($userId);
        if (empty($cartItems)) {
            $this->json(['success' => false, 'message' => '購物車是空的'], 400);
            return;
        }

        $subtotal = $this->cartModel->calculateSubtotal($cartItems);
        $shippingMethod = $_POST['shipping_method'] ?? 'standard';
        $totalAmount = ShippingService::calculateTotal($subtotal, $shippingMethod);
        $amountInCents = PaymentService::convertToCents($totalAmount);
        if ($amountInCents <= 0) {
            $this->json(['success' => false, 'message' => '訂單金額無效'], 400);
            return;
        }

        try {
            $result = $this->paymentService->createPaymentIntent($amountInCents, 'hkd', [
                'user_id' => (string) $userId,
                'cart_items_count' => (string) count($cartItems),
                'shipping_method' => $shippingMethod,
            ]);
            $this->json([
                'success' => true,
                'client_secret' => $result['client_secret'],
                'payment_intent_id' => $result['payment_intent_id'],
                'amount' => $result['amount'],
                'currency' => $result['currency'],
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function createPaypalOrder(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $cartItems = $this->cartModel->getCartItems($userId);
        if (empty($cartItems)) {
            $this->json(['success' => false, 'message' => '購物車是空的'], 400);
            return;
        }

        $subtotal = $this->cartModel->calculateSubtotal($cartItems);
        $shippingMethod = $_POST['shipping_method'] ?? 'standard';
        $shippingFee = ShippingService::calculateShippingFee($subtotal, $shippingMethod);
        $totalAmount = ShippingService::calculateTotal($subtotal, $shippingMethod);
        if ($totalAmount <= 0) {
            $this->json(['success' => false, 'message' => '訂單金額無效'], 400);
            return;
        }

        $orderNumber = $this->generateOrderNumber();
        $this->json([
            'success' => true,
            'order_number' => $orderNumber,
            'amount' => number_format($totalAmount, 2, '.', ''),
            'currency' => 'HKD',
            'items' => $cartItems,
            'shipping_method' => $shippingMethod,
            'shipping_fee' => $shippingFee,
        ]);
    }

    public function confirm(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $paymentIntentId = trim($_POST['payment_intent_id'] ?? $_POST['paypal_order_id'] ?? '');
        $shippingAddress = trim($_POST['shipping_address'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? 'credit_card');
        $shippingMethod = trim($_POST['shipping_method'] ?? 'standard');

        if ($paymentIntentId === '') {
            $this->json(['success' => false, 'message' => '缺少支付資訊'], 400);
            return;
        }
        if ($shippingAddress === '') {
            $this->json(['success' => false, 'message' => '請填寫配送地址'], 400);
            return;
        }

        $paymentStatus = 'succeeded';
        if ($paymentMethod !== 'paypal') {
            try {
                $intent = $this->paymentService->getPaymentIntent($paymentIntentId);
                $paymentStatus = $intent['status'];
            } catch (\Throwable $e) {
                $this->json(['success' => false, 'message' => '無法驗證支付'], 400);
                return;
            }
        }

        if ($paymentStatus !== 'succeeded') {
            $this->json(['success' => false, 'message' => '支付尚未完成', 'status' => $paymentStatus], 400);
            return;
        }

        $cartItems = $this->cartModel->getCartItems($userId);
        if (empty($cartItems)) {
            $this->json(['success' => false, 'message' => '購物車是空的'], 400);
            return;
        }

        $subtotal = $this->cartModel->calculateSubtotal($cartItems);
        $totalAmount = ShippingService::calculateTotal($subtotal, $shippingMethod);
        $itemsForOrder = [];
        foreach ($cartItems as $row) {
            $itemsForOrder[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'qty' => $row['qty'],
                'price' => $row['price'],
            ];
        }

        try {
            $orderNumber = $this->generateOrderNumber();
            $pdo = Database::getConnection();
            $pdo->beginTransaction();
            try {
                $order = new Order();
                $orderId = $order->create(
                    $userId,
                    ['total' => $totalAmount, 'items' => $itemsForOrder],
                    $paymentMethod === 'paypal' ? 'paypal' : 'credit',
                    $shippingAddress,
                    $orderNumber,
                    'paid',
                    true
                );
                $this->cartModel->clearCart($userId);
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }

            $this->json([
                'success' => true,
                'message' => '訂單已確認',
                'order_number' => $orderNumber,
                'order_id' => $orderId,
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => '建立訂單失敗，請稍後再試'], 500);
        }
    }

    private function generateOrderNumber(): string
    {
        $config = $this->getConfig();
        $prefix = (string) ($config['order_number_prefix'] ?? 'ORD');
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
}
