<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\CartModel;
use App\Services\PaymentService;
use App\Services\OrderService;
use App\Services\ShippingService;
use App\Services\WalletService;

class PaymentController extends Controller
{
    private ?PaymentService $paymentService = null;
    private CartModel $cartModel;
    private OrderService $orderService;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel = new CartModel();
        $this->orderService = new OrderService();
    }

    public function getPublishableKey(): void
    {
        $this->setupJsonApi();

        $key = getenv('STRIPE_PUBLISHABLE_KEY') ?: '';
        if ($key === '') {
            $msg = Config::get('messages.payment.stripe_config_error');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 500);
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
            $msg = Config::get('messages.payment.cart_empty');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }

        $subtotal = $this->cartModel->calculateSubtotal($cartItems);
        $shippingMethod = $_POST['shipping_method'] ?? 'standard';
        $totalAmount = ShippingService::calculateTotal($subtotal, $shippingMethod);
        $useWallet = $this->toBool($_POST['use_wallet'] ?? '1');

        $walletService = new WalletService();
        $walletBalance = $walletService->getBalance($userId);
        $walletToUse = $useWallet ? max(0.0, min($walletBalance, $totalAmount)) : 0.0;
        $_SESSION['wallet_use_checkout'] = $walletToUse;

        $payableAmount = $totalAmount - $walletToUse;
        $amountInCents = PaymentService::convertToCents($payableAmount);
        if ($amountInCents <= 0) {
            $msg = Config::get('messages.payment.order_amount_invalid');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }

        $paymentService = $this->getStripePaymentService();
        if ($paymentService === null) {
            $msg = Config::get('messages.payment.stripe_config_error');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 500);
            return;
        }

        try {
            $orderNumber = $this->orderService->generateOrderNumber();
            $result = $paymentService->createPaymentIntent($amountInCents, $this->getStripeCurrencyCode(), [
                'user_id' => (string) $userId,
                'cart_items_count' => (string) count($cartItems),
                'shipping_method' => $shippingMethod,
                'wallet_used' => (string) $walletToUse,
            ]);
            $this->json([
                'success' => true,
                'client_secret' => $result['client_secret'],
                'payment_intent_id' => $result['payment_intent_id'],
                'order_number' => $orderNumber,
                'amount' => $result['amount'],
                'currency' => $result['currency'],
                'wallet_used' => $walletToUse,
                'total_amount' => $totalAmount,
            ]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()], 500);
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
            $msg = Config::get('messages.payment.cart_empty');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }

        $subtotal = $this->cartModel->calculateSubtotal($cartItems);
        $shippingMethod = $_POST['shipping_method'] ?? 'standard';
        $shippingFee = ShippingService::calculateShippingFee($subtotal, $shippingMethod);
        $totalAmount = ShippingService::calculateTotal($subtotal, $shippingMethod);
        $useWallet = $this->toBool($_POST['use_wallet'] ?? '1');

        $walletService = new WalletService();
        $walletBalance = $walletService->getBalance($userId);
        $walletToUse = $useWallet ? max(0.0, min($walletBalance, $totalAmount)) : 0.0;
        $_SESSION['wallet_use_checkout'] = $walletToUse;

        $payableAmount = $totalAmount - $walletToUse;
        if ($payableAmount < 0) {
            $payableAmount = 0;
        }
        if ($totalAmount <= 0) {
            $msg = Config::get('messages.payment.order_amount_invalid');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }

        $orderNumber = $this->orderService->generateOrderNumber();
        $this->json([
            'success' => true,
            'order_number' => $orderNumber,
            'amount' => number_format($payableAmount, 2, '.', ''),
            'currency' => $this->getCurrencyCode(),
            'items' => $cartItems,
            'shipping_method' => $shippingMethod,
            'shipping_fee' => $shippingFee,
            'wallet_used' => $walletToUse,
            'total_amount' => $totalAmount,
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
        $orderNumber = trim($_POST['order_number'] ?? '');
        $shippingAddress = trim($_POST['shipping_address'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? 'credit_card');
        $shippingMethod = trim($_POST['shipping_method'] ?? 'standard');
        $useWallet = $this->toBool($_POST['use_wallet'] ?? '1');

        if ($paymentIntentId === '') {
            $msg = Config::get('messages.payment.missing_info');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }
        if ($shippingAddress === '') {
            $msg = Config::get('messages.payment.shipping_required');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }

        $paymentStatus = 'succeeded';
        if ($paymentMethod !== 'paypal') {
            $paymentService = $this->getStripePaymentService();
            if ($paymentService === null) {
                $msg = Config::get('messages.payment.stripe_config_error');
                $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 500);
                return;
            }
            try {
                $intent = $paymentService->getPaymentIntent($paymentIntentId);
                $paymentStatus = $intent['status'];
            } catch (\Throwable $e) {
                $msg = Config::get('messages.payment.verify_failed');
                $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
                return;
            }
        }

        if ($paymentStatus !== 'succeeded') {
            $msg = Config::get('messages.payment.not_completed');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg, 'status' => $paymentStatus], 400);
            return;
        }

        $cartItems = $this->cartModel->getCartItems($userId);
        if (empty($cartItems)) {
            $msg = Config::get('messages.payment.cart_empty');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }

        $subtotal = $this->cartModel->calculateSubtotal($cartItems);
        $totalAmount = ShippingService::calculateTotal($subtotal, $shippingMethod);
        $walletUsed = $useWallet && isset($_SESSION['wallet_use_checkout']) ? (float) $_SESSION['wallet_use_checkout'] : 0.0;
        if ($walletUsed < 0) {
            $walletUsed = 0.0;
        }
        if ($walletUsed > $totalAmount) {
            $walletUsed = $totalAmount;
        }
        $itemsForOrder = [];
        foreach ($cartItems as $row) {
            $itemsForOrder[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'qty' => $row['qty'],
                'price' => $row['price'],
            ];
        }

        if ($orderNumber === '') {
            $orderNumber = $this->orderService->generateOrderNumber();
        }

        try {
            $result = $this->orderService->createOrderFromCart(
                $userId,
                $itemsForOrder,
                $totalAmount,
                $paymentMethod === 'paypal' ? 'paypal' : 'credit',
                $shippingAddress,
                'paid',
                $orderNumber,
                true,
                $paymentMethod === 'paypal' ? 'paypal' : 'stripe',
                $paymentIntentId,
                $walletUsed
            );

            unset($_SESSION['wallet_use_checkout']);

            $this->json([
                'success' => true,
                'message' => Config::get('messages.order.confirmed'),
                'order_number' => $result['order_number'],
                'order_id' => $result['order_id'],
            ]);
        } catch (\InvalidArgumentException $e) {
            $msg = Config::get('messages.order.insufficient_stock');
            $this->json([
                'success' => false,
                'error' => $msg,
                'message' => $msg,
            ], 400);
        } catch (\Throwable $e) {
            $msg = Config::get('messages.payment.confirm_failed');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 500);
        }
    }

    private function toBool($value): bool
    {
        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private function getStripePaymentService(): ?PaymentService
    {
        if ($this->paymentService !== null) {
            return $this->paymentService;
        }

        try {
            $this->paymentService = new PaymentService();
            return $this->paymentService;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getCurrencyCode(): string
    {
        $currency = Config::get('currency', []);
        if (!is_array($currency)) {
            return 'HKD';
        }

        $code = strtoupper(trim((string) ($currency['code'] ?? 'HKD')));
        return $code !== '' ? $code : 'HKD';
    }

    private function getStripeCurrencyCode(): string
    {
        return strtolower($this->getCurrencyCode());
    }
}
