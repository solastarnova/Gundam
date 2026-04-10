<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Constants;
use App\Core\Controller;
use App\Models\CartModel;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Services\PaymentService;
use App\Services\OrderService;
use App\Services\ShippingService;
use App\Services\WalletService;

class PaymentController extends Controller
{
    private ?PaymentService $paymentService = null;
    private CartModel $cartModel;
    private OrderService $orderService;
    private OrderModel $orderModel;
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel = new CartModel();
        $this->orderService = new OrderService();
        $this->orderModel = new OrderModel();
        $this->userModel = new UserModel();
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
            $msg = Config::get('messages.common.cart_empty');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }

        $subtotal = $this->cartModel->calculateSubtotal($cartItems);
        $shippingMethod = $_POST['shipping_method'] ?? 'standard';
        $totalAmount = ShippingService::calculateTotal($subtotal, $shippingMethod);
        $useWallet = $this->toBool($_POST['use_wallet'] ?? '1');
        $usePoints = $this->toBool($_POST['use_points'] ?? '0');

        $walletService = new WalletService();
        $walletBalance = $walletService->getBalance($userId);
        $walletToUse = $useWallet ? max(0.0, min($walletBalance, $totalAmount)) : 0.0;
        $_SESSION['wallet_use_checkout'] = $walletToUse;

        $remainAfterWallet = max(0.0, $totalAmount - $walletToUse);
        $pointsBalance = $this->userModel->getPointsBalance($userId);
        $pointsToUse = $usePoints ? min($pointsBalance, (int) floor($remainAfterWallet * Constants::POINTS_PER_HKD)) : 0;
        $_SESSION['points_use_checkout'] = $pointsToUse;

        $pointsHkdUsed = $pointsToUse / Constants::POINTS_PER_HKD;
        $payableAmount = $remainAfterWallet - $pointsHkdUsed;
        $amountInCents = PaymentService::convertToCents($payableAmount);
        if ($amountInCents <= 0) {
            if ($totalAmount > 0 && $payableAmount <= 0.00001) {
                $msg = Config::get('messages.payment.payable_zero_use_wallet_button');
                $this->json([
                    'success' => false,
                    'error' => $msg,
                    'message' => $msg,
                    'wallet_checkout' => true,
                ], 400);
            } else {
                $msg = Config::get('messages.payment.order_amount_invalid');
                $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            }
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
                'points_used' => (string) $pointsToUse,
            ]);
            $this->json([
                'success' => true,
                'client_secret' => $result['client_secret'],
                'payment_intent_id' => $result['payment_intent_id'],
                'order_number' => $orderNumber,
                'amount' => $result['amount'],
                'currency' => $result['currency'],
                'wallet_used' => $walletToUse,
                'points_used' => $pointsToUse,
                'points_hkd_used' => $pointsHkdUsed,
                'total_amount' => $totalAmount,
            ]);
        } catch (\Throwable $e) {
            error_log('Payment createIntent: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $msg = Config::get('messages.payment.intent_create_failed');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 500);
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
            $msg = Config::get('messages.common.cart_empty');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }

        $subtotal = $this->cartModel->calculateSubtotal($cartItems);
        $shippingMethod = $_POST['shipping_method'] ?? 'standard';
        $shippingFee = ShippingService::calculateShippingFee($subtotal, $shippingMethod);
        $totalAmount = ShippingService::calculateTotal($subtotal, $shippingMethod);
        $useWallet = $this->toBool($_POST['use_wallet'] ?? '1');
        $usePoints = $this->toBool($_POST['use_points'] ?? '0');

        $walletService = new WalletService();
        $walletBalance = $walletService->getBalance($userId);
        $walletToUse = $useWallet ? max(0.0, min($walletBalance, $totalAmount)) : 0.0;
        $_SESSION['wallet_use_checkout'] = $walletToUse;

        $remainAfterWallet = max(0.0, $totalAmount - $walletToUse);
        $pointsBalance = $this->userModel->getPointsBalance($userId);
        $pointsToUse = $usePoints ? min($pointsBalance, (int) floor($remainAfterWallet * Constants::POINTS_PER_HKD)) : 0;
        $_SESSION['points_use_checkout'] = $pointsToUse;
        $pointsHkdUsed = $pointsToUse / Constants::POINTS_PER_HKD;

        $payableAmount = $remainAfterWallet - $pointsHkdUsed;
        if ($payableAmount < 0) {
            $payableAmount = 0;
        }
        if ($totalAmount <= 0) {
            $msg = Config::get('messages.payment.order_amount_invalid');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }
        if ($payableAmount <= 0.00001) {
            $msg = Config::get('messages.payment.payable_zero_use_wallet_button');
            $this->json([
                'success' => false,
                'error' => $msg,
                'message' => $msg,
                'wallet_checkout' => true,
            ], 400);
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

    public function walletCheckout(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $shippingAddress = trim((string) ($_POST['shipping_address'] ?? ''));
        $shippingMethod = trim((string) ($_POST['shipping_method'] ?? 'standard'));
        $useWallet = $this->toBool($_POST['use_wallet'] ?? '0');

        if ($shippingAddress === '') {
            $msg = Config::get('messages.payment.shipping_required');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }

        if (!$useWallet) {
            $msg = Config::get('messages.payment.wallet_checkout_requires_wallet');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }

        $cartItems = $this->cartModel->getCartItems($userId);
        if (empty($cartItems)) {
            $msg = Config::get('messages.common.cart_empty');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }

        $subtotal = $this->cartModel->calculateSubtotal($cartItems);
        $totalAmount = round(ShippingService::calculateTotal($subtotal, $shippingMethod), 2);

        if ($totalAmount <= 0) {
            $msg = Config::get('messages.payment.order_amount_invalid');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }

        $walletService = new WalletService();
        $walletBalance = round($walletService->getBalance($userId), 2);
        $walletToUse = $totalAmount;

        if ($walletBalance + 0.00001 < $walletToUse) {
            $msg = Config::get('messages.payment.wallet_insufficient_for_total');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }

        $payableAfterWallet = max(0.0, $totalAmount - $walletToUse);
        if ($payableAfterWallet > 0.00001) {
            $msg = Config::get('messages.payment.wallet_checkout_not_zero');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
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

        $orderNumber = $this->orderService->generateOrderNumber();

        $walletPaymentRef = 'wallet:' . $orderNumber;

        try {
            $result = $this->orderService->createOrderFromCart(
                $userId,
                $itemsForOrder,
                $totalAmount,
                'wallet',
                $shippingAddress,
                'paid',
                $orderNumber,
                true,
                'wallet',
                $walletPaymentRef,
                $walletToUse
            );

            unset($_SESSION['wallet_use_checkout']);
            unset($_SESSION['points_use_checkout']);

            $payload = [
                'success' => true,
                'message' => Config::get('messages.order.confirmed'),
                'order_number' => $result['order_number'],
                'order_id' => $result['order_id'],
            ];
            if (!empty($result['idempotent'])) {
                $payload['idempotent'] = true;
            }
            $this->json($payload);
        } catch (\InvalidArgumentException $e) {
            $msg = Config::get('messages.order.insufficient_stock');
            $this->json([
                'success' => false,
                'error' => $msg,
                'message' => $msg,
            ], 400);
        } catch (\RuntimeException $e) {
            error_log('walletCheckout RuntimeException: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $msg = Config::get('messages.payment.wallet_insufficient_for_total');
            if (strpos($e->getMessage(), '餘額') === false) {
                $msg = Config::get('messages.payment.confirm_failed');
            }
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
        } catch (\Throwable $e) {
            error_log('walletCheckout: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $msg = Config::get('messages.payment.confirm_failed');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 500);
        }
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
        $usePoints = $this->toBool($_POST['use_points'] ?? '0');

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
                error_log('Payment confirm getPaymentIntent: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
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

        $paymentProvider = $paymentMethod === 'paypal' ? 'paypal' : 'stripe';
        $existingOrder = $this->orderModel->findByUserIdAndPaymentReference($userId, $paymentProvider, $paymentIntentId);
        if ($existingOrder !== null) {
            unset($_SESSION['wallet_use_checkout']);
            unset($_SESSION['points_use_checkout']);
            $this->json([
                'success' => true,
                'message' => Config::get('messages.order.confirmed'),
                'order_number' => $existingOrder['order_number'],
                'order_id' => (int) $existingOrder['id'],
                'idempotent' => true,
            ]);
            return;
        }

        $cartItems = $this->cartModel->getCartItems($userId);
        if (empty($cartItems)) {
            $msg = Config::get('messages.common.cart_empty');
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

        $remainAfterWallet = max(0.0, $totalAmount - $walletUsed);
        $pointsUsed = $usePoints && isset($_SESSION['points_use_checkout']) ? (int) $_SESSION['points_use_checkout'] : 0;
        $maxPoints = (int) floor($remainAfterWallet * Constants::POINTS_PER_HKD);
        if ($pointsUsed < 0) {
            $pointsUsed = 0;
        }
        if ($pointsUsed > $maxPoints) {
            $pointsUsed = $maxPoints;
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
                'completed',
                $orderNumber,
                true,
                $paymentMethod === 'paypal' ? 'paypal' : 'stripe',
                $paymentIntentId,
                $walletUsed
            );

            if ($pointsUsed > 0) {
                $this->userModel->spendPoints(
                    $userId,
                    $pointsUsed,
                    (int) $result['order_id'],
                    sprintf((string) Config::get('messages.order.points_spend_note'), (string) $result['order_number'])
                );
            }

            unset($_SESSION['wallet_use_checkout']);
            unset($_SESSION['points_use_checkout']);

            $payload = [
                'success' => true,
                'message' => Config::get('messages.order.confirmed'),
                'order_number' => $result['order_number'],
                'order_id' => $result['order_id'],
            ];
            if (!empty($result['idempotent'])) {
                $payload['idempotent'] = true;
            }
            $this->json($payload);
        } catch (\InvalidArgumentException $e) {
            $msg = Config::get('messages.order.insufficient_stock');
            $this->json([
                'success' => false,
                'error' => $msg,
                'message' => $msg,
            ], 400);
        } catch (\Throwable $e) {
            error_log('Payment confirm: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
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
