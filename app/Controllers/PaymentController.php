<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Constants;
use App\Core\Controller;
use App\Core\PaymentProvider;
use App\Models\CartModel;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Services\LalamoveCheckoutService;
use App\Services\PaymentService;
use App\Services\PayPalService;
use App\Services\CheckoutFingerprintService;
use App\Services\CheckoutContextService;
use App\Services\CheckoutSnapshotService;
use App\Services\OrderService;
use App\Services\WalletService;
use InvalidArgumentException;

/** 處理結帳付款相關 API 與付款確認流程。 */
class PaymentController extends Controller
{
    private ?PaymentService $paymentService = null;
    private ?PayPalService $payPalService = null;
    private CartModel $cartModel;
    private OrderService $orderService;
    private CheckoutContextService $checkoutContextService;
    private OrderModel $orderModel;
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel = new CartModel();
        $this->orderService = new OrderService();
        $this->checkoutContextService = new CheckoutContextService();
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

    /** 根據目前結帳上下文建立 Stripe PaymentIntent。 */
    public function createIntent(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $checkout = $this->prepareCheckoutContextForPaymentApis($userId);
        if ($checkout === null) {
            return;
        }
        $cartItems = $checkout['cart_items'];
        $totalAmount = $checkout['total_amount'];
        $shippingMethod = 'lalamove';
        $deduction = $this->computeCheckoutDeductions($userId, $totalAmount, '1', '0');
        $walletToUse = $deduction['wallet_used'];
        $pointsToUse = $deduction['points_used'];
        $pointsHkdUsed = $deduction['points_hkd_used'];
        $remainAfterWallet = max(0.0, $totalAmount - $walletToUse);
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
            $this->checkoutContextService->store($orderNumber, [
                'user_id' => $userId,
                'provider' => PaymentProvider::STRIPE,
                'provider_reference' => (string) $result['payment_intent_id'],
                'total_amount' => $totalAmount,
                'wallet_used' => $walletToUse,
                'points_used' => $pointsToUse,
                'payable_amount' => $payableAmount,
                'currency' => strtoupper((string) $result['currency']),
                'expires_at' => time() + 900,
                'checkout_token' => trim((string) ($_POST['checkout_token'] ?? '')),
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

    /** 根據目前結帳上下文建立 PayPal 訂單資料。 */
    public function createPaypalOrder(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $checkout = $this->prepareCheckoutContextForPaymentApis($userId);
        if ($checkout === null) {
            return;
        }
        $cartItems = $checkout['cart_items'];
        $totalAmount = $checkout['total_amount'];
        $shippingFee = $checkout['shipping_fee'];
        $shippingMethod = 'lalamove';
        $deduction = $this->computeCheckoutDeductions($userId, $totalAmount, '1', '0');
        $walletToUse = $deduction['wallet_used'];
        $pointsHkdUsed = $deduction['points_hkd_used'];

        $remainAfterWallet = max(0.0, $totalAmount - $walletToUse);
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
        $this->checkoutContextService->store($orderNumber, [
            'user_id' => $userId,
            'provider' => PaymentProvider::PAYPAL,
            'provider_reference' => '',
            'total_amount' => $totalAmount,
            'wallet_used' => $walletToUse,
            'points_used' => (int) ($deduction['points_used'] ?? 0),
            'payable_amount' => $payableAmount,
            'currency' => strtoupper($this->getCurrencyCode()),
            'expires_at' => time() + 900,
            'checkout_token' => trim((string) ($_POST['checkout_token'] ?? '')),
        ]);
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

    /**
     * @return array{cart_items:array<int, array<string, mixed>>, total_amount:float, shipping_fee:float}|null
     */
    private function prepareCheckoutContextForPaymentApis(int $userId): ?array
    {
        $cartItems = $this->cartModel->getCartItems($userId);
        if (empty($cartItems)) {
            $msg = Config::get('messages.common.cart_empty');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return null;
        }

        if (!LalamoveCheckoutService::isCheckoutConfigured()) {
            $msg = Config::get('messages.payment.lalamove_not_configured');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 503);
            return null;
        }

        $checkoutToken = trim((string) ($_POST['checkout_token'] ?? ''));
        if ($checkoutToken !== '') {
            $snap = null;
            try {
                $snapSvc = new CheckoutSnapshotService();
                $snap = $snapSvc->assertValidSnapshot($userId, $checkoutToken, $cartItems, $this->cartModel);
                $snapSvc->materializeLalamoveSession($snap);
            } catch (InvalidArgumentException $e) {
                $this->json(['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()], 400);
                return null;
            }
            $subtotal = round($this->cartModel->calculateSubtotal($cartItems), 2);
            $fee = (float) (is_array($snap) ? ($snap['fee'] ?? 0) : 0);

            return [
                'cart_items' => $cartItems,
                'total_amount' => round($subtotal + $fee, 2),
                'shipping_fee' => $fee,
            ];
        }

        $shippingAddress = trim((string) ($_POST['shipping_address'] ?? ''));
        $shippingLat = $this->normalizeCoordinate((string) ($_POST['shipping_lat'] ?? ''));
        $shippingLng = $this->normalizeCoordinate((string) ($_POST['shipping_lng'] ?? ''));
        if ($shippingAddress === '') {
            $msg = Config::get('messages.payment.shipping_required');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return null;
        }

        $totals = $this->tryTotalsForOrderConfirmation($cartItems, $shippingAddress, $shippingLat, $shippingLng);
        if ($totals === null) {
            return null;
        }

        return [
            'cart_items' => $cartItems,
            'total_amount' => (float) $totals['total'],
            'shipping_fee' => (float) $totals['shipping_fee'],
        ];
    }

    /**
     * @return array{wallet_used:float, points_used:int, points_hkd_used:float}
     */
    private function computeCheckoutDeductions(int $userId, float $totalAmount, string $defaultUseWallet = '1', string $defaultUsePoints = '0'): array
    {
        $useWallet = $this->toBool($_POST['use_wallet'] ?? $defaultUseWallet);
        $usePoints = $this->toBool($_POST['use_points'] ?? $defaultUsePoints);

        $walletService = new WalletService();
        $walletBalance = $walletService->getBalance($userId);
        $walletToUse = $useWallet ? max(0.0, min($walletBalance, $totalAmount)) : 0.0;
        $_SESSION['wallet_use_checkout'] = $walletToUse;

        $remainAfterWallet = max(0.0, $totalAmount - $walletToUse);
        $pointsBalance = $this->userModel->getPointsBalance($userId);
        $pointsToUse = $usePoints ? min($pointsBalance, (int) floor($remainAfterWallet * Constants::POINTS_PER_HKD)) : 0;
        $_SESSION['points_use_checkout'] = $pointsToUse;
        $pointsHkdUsed = $pointsToUse / Constants::POINTS_PER_HKD;

        return [
            'wallet_used' => $walletToUse,
            'points_used' => $pointsToUse,
            'points_hkd_used' => $pointsHkdUsed,
        ];
    }

    /** 處理零元或純錢包抵扣結帳流程。 */
    public function walletCheckout(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $walletCheckoutInput = $this->collectWalletCheckoutInput();
        $shippingAddress = $walletCheckoutInput['shipping_address'];
        $shippingLat = $walletCheckoutInput['shipping_lat'];
        $shippingLng = $walletCheckoutInput['shipping_lng'];
        $useWallet = $walletCheckoutInput['use_wallet'];
        $walletCheckoutToken = trim((string) ($walletCheckoutInput['checkout_token'] ?? ''));

        if (!$this->validateWalletCheckoutInput($walletCheckoutInput)) {
            return;
        }

        if (!$this->ensureLalamoveConfiguredForApi()) {
            return;
        }

        if (!$this->ensureWalletCheckoutEnabled($useWallet)) {
            return;
        }

        $cartItems = $this->getCartItemsOrReject($userId);
        if ($cartItems === null) {
            return;
        }

        if ($walletCheckoutToken !== '') {
            try {
                $snapSvc = new CheckoutSnapshotService();
                $snap = $snapSvc->assertValidSnapshot($userId, $walletCheckoutToken, $cartItems, $this->cartModel);
                $snapSvc->materializeLalamoveSession($snap);
                $shippingAddress = (string) ($snap['address_one_line'] ?? $shippingAddress);
                $shippingLat = isset($snap['quote_lat']) ? ($snap['quote_lat'] !== null && $snap['quote_lat'] !== '' ? (string) $snap['quote_lat'] : null) : null;
                $shippingLng = isset($snap['quote_lng']) ? ($snap['quote_lng'] !== null && $snap['quote_lng'] !== '' ? (string) $snap['quote_lng'] : null) : null;
                $shippingLat = $this->normalizeCoordinate((string) ($shippingLat ?? ''));
                $shippingLng = $this->normalizeCoordinate((string) ($shippingLng ?? ''));
            } catch (InvalidArgumentException $e) {
                $this->json(['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()], 400);
                return;
            }
            $totals = $this->tryTotalsForOrderConfirmation($cartItems, $shippingAddress, $shippingLat, $shippingLng);
        } else {
            $totals = $this->tryComputeCheckoutTotals($cartItems, $shippingAddress, $shippingLat, $shippingLng);
        }
        if ($totals === null) {
            return;
        }
        $totalAmount = (float) $totals['total'];

        if ($totalAmount <= 0) {
            $msg = Config::get('messages.payment.order_amount_invalid');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }

        $walletService = new WalletService();
        $walletBalance = round($walletService->getBalance($userId), 2);
        $walletToUse = $totalAmount;

        if (!$this->ensureWalletCanCoverTotal($walletBalance, $walletToUse)) {
            return;
        }

        $payableAfterWallet = max(0.0, $totalAmount - $walletToUse);
        if ($payableAfterWallet > 0.00001) {
            $msg = Config::get('messages.payment.wallet_checkout_not_zero');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }

        $this->createWalletOnlyOrder($userId, $cartItems, $totalAmount, $shippingAddress, $walletToUse, $walletCheckoutToken);
    }

    /**
     * @return array{shipping_address:string, shipping_lat:?string, shipping_lng:?string, use_wallet:bool}
     */
    private function collectWalletCheckoutInput(): array
    {
        return [
            'shipping_address' => trim((string) ($_POST['shipping_address'] ?? '')),
            'shipping_lat' => $this->normalizeCoordinate((string) ($_POST['shipping_lat'] ?? '')),
            'shipping_lng' => $this->normalizeCoordinate((string) ($_POST['shipping_lng'] ?? '')),
            'use_wallet' => $this->toBool($_POST['use_wallet'] ?? '0'),
            'checkout_token' => trim((string) ($_POST['checkout_token'] ?? '')),
        ];
    }

    /**
     * @param array{shipping_address:string, checkout_token:string} $walletCheckoutInput
     */
    private function validateWalletCheckoutInput(array $walletCheckoutInput): bool
    {
        $tok = trim((string) ($walletCheckoutInput['checkout_token'] ?? ''));
        if ($tok === '' && $walletCheckoutInput['shipping_address'] === '') {
            $msg = Config::get('messages.payment.shipping_required');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return false;
        }

        return true;
    }

    private function ensureWalletCanCoverTotal(float $walletBalance, float $walletToUse): bool
    {
        if ($walletBalance + 0.00001 < $walletToUse) {
            $msg = Config::get('messages.payment.wallet_insufficient_for_total');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return false;
        }

        return true;
    }

    private function ensureLalamoveConfiguredForApi(): bool
    {
        if (LalamoveCheckoutService::isCheckoutConfigured()) {
            return true;
        }
        $msg = Config::get('messages.payment.lalamove_not_configured');
        $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 503);
        return false;
    }

    private function ensureWalletCheckoutEnabled(bool $useWallet): bool
    {
        if ($useWallet) {
            return true;
        }
        $msg = Config::get('messages.payment.wallet_checkout_requires_wallet');
        $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
        return false;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function getCartItemsOrReject(int $userId): ?array
    {
        $cartItems = $this->cartModel->getCartItems($userId);
        if (!empty($cartItems)) {
            return $cartItems;
        }
        $msg = Config::get('messages.common.cart_empty');
        $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $cartItems
     * @return array{subtotal: float, shipping_fee: float, total: float}|null
     */
    private function tryComputeCheckoutTotals(array $cartItems, string $shippingAddress, ?string $shippingLat, ?string $shippingLng): ?array
    {
        try {
            return $this->computeCheckoutTotalsWithShipping($cartItems, $shippingAddress, $shippingLat, $shippingLng);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()], 400);
            return null;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $cartItems
     * @return array{subtotal: float, shipping_fee: float, total: float}|null
     */
    private function tryTotalsForOrderConfirmation(array $cartItems, string $shippingAddress, ?string $shippingLat, ?string $shippingLng): ?array
    {
        try {
            return $this->totalsForOrderConfirmation($cartItems, $shippingAddress, $shippingLat, $shippingLng);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()], 400);
            return null;
        }
    }

    private function respondExistingConfirmedOrder(int $userId, string $paymentMethod, string $paymentIntentId): bool
    {
        $paymentProvider = $paymentMethod === PaymentProvider::PAYPAL
            ? PaymentProvider::PAYPAL
            : PaymentProvider::STRIPE;
        $existingOrder = $this->orderModel->findByUserIdAndPaymentReference($userId, $paymentProvider, $paymentIntentId);
        if ($existingOrder === null) {
            return false;
        }
        unset($_SESSION['wallet_use_checkout']);
        unset($_SESSION['points_use_checkout']);
        $this->clearCheckoutLalamoveSession();
        $this->json([
            'success' => true,
            'message' => Config::get('messages.order.confirmed'),
            'order_number' => $existingOrder['order_number'],
            'order_id' => (int) $existingOrder['id'],
            'idempotent' => true,
        ]);
        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $cartItems
     */
    private function createWalletOnlyOrder(
        int $userId,
        array $cartItems,
        float $totalAmount,
        string $shippingAddress,
        float $walletToUse,
        string $checkoutToken = ''
    ): void {
        $itemsForOrder = $this->buildOrderItemsFromCart($cartItems);
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
                PaymentProvider::WALLET,
                $walletPaymentRef,
                $walletToUse
            );

            unset($_SESSION['wallet_use_checkout']);
            unset($_SESSION['points_use_checkout']);
            $this->clearCheckoutLalamoveSession();
            if (trim($checkoutToken) !== '') {
                (new CheckoutSnapshotService())->forget(trim($checkoutToken));
            }

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

    /**
     * @param array<int, array{id:mixed,name:mixed,qty:mixed,price:mixed}> $itemsForOrder
     */
    private function createConfirmedOrderFromPayment(
        int $userId,
        array $itemsForOrder,
        float $totalAmount,
        string $paymentMethod,
        string $shippingAddress,
        string $orderNumber,
        string $paymentIntentId,
        float $walletUsed,
        int $pointsUsed,
        string $checkoutTokenToForget = ''
    ): void {
        $paymentProvider = $paymentMethod === PaymentProvider::PAYPAL
            ? PaymentProvider::PAYPAL
            : PaymentProvider::STRIPE;

        try {
            $this->checkoutContextService->updateProviderReference($orderNumber, $paymentIntentId);
            $result = $this->orderService->createOrderFromCart(
                $userId,
                $itemsForOrder,
                $totalAmount,
                $paymentMethod,
                $shippingAddress,
                'paid',
                $orderNumber,
                true,
                $paymentProvider,
                $paymentIntentId,
                $walletUsed,
                $pointsUsed
            );

            unset($_SESSION['wallet_use_checkout']);
            unset($_SESSION['points_use_checkout']);
            $this->clearCheckoutLalamoveSession();
            if (trim($checkoutTokenToForget) !== '') {
                (new CheckoutSnapshotService())->forget(trim($checkoutTokenToForget));
            }

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
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
        } catch (\Throwable $e) {
            error_log('confirm create order failed: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $msg = Config::get('messages.payment.confirm_failed');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 500);
        }
    }

    /** 第三方支付授權後最終確認訂單。 */
    public function confirm(): void
    {
        $this->setupJsonApi();

        if (!$this->requireAuthForApi()) {
            return;
        }

        $confirm = $this->collectConfirmInput();
        $userId = (int) $_SESSION['user_id'];
        $paymentIntentId = $confirm['payment_intent_id'];
        $orderNumber = $confirm['order_number'];
        $shippingAddress = $confirm['shipping_address'];
        $shippingLat = $confirm['shipping_lat'];
        $shippingLng = $confirm['shipping_lng'];
        $paymentMethod = $confirm['payment_method'];
        $confirmCheckoutToken = trim((string) ($confirm['checkout_token'] ?? ''));

        if (!$this->validateConfirmInput($confirm)) {
            return;
        }

        if (!$this->ensureLalamoveConfiguredForApi()) {
            return;
        }

        $verifiedPayment = $this->ensurePaymentSucceeded($paymentMethod, $paymentIntentId);
        if ($verifiedPayment === null) {
            return;
        }

        if ($this->respondExistingConfirmedOrder($userId, $paymentMethod, $paymentIntentId)) {
            return;
        }

        $cartItems = $this->getCartItemsOrReject($userId);
        if ($cartItems === null) {
            return;
        }

        if ($confirmCheckoutToken !== '') {
            try {
                $snapSvc = new CheckoutSnapshotService();
                $snap = $snapSvc->assertValidSnapshot($userId, $confirmCheckoutToken, $cartItems, $this->cartModel);
                $snapSvc->materializeLalamoveSession($snap);
                $shippingAddress = (string) ($snap['address_one_line'] ?? $shippingAddress);
                $shippingLat = $this->normalizeCoordinate((string) ($snap['quote_lat'] ?? ''));
                $shippingLng = $this->normalizeCoordinate((string) ($snap['quote_lng'] ?? ''));
            } catch (InvalidArgumentException $e) {
                $this->json(['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()], 400);
                return;
            }
        }

        $totals = $this->tryTotalsForOrderConfirmation($cartItems, $shippingAddress, $shippingLat, $shippingLng);
        if ($totals === null) {
            return;
        }
        $totalAmount = (float) $totals['total'];
        $ctx = $this->checkoutContextService->load($orderNumber);
        if ($ctx === null || !$this->checkoutContextService->validate($ctx, $userId, $paymentMethod)) {
            $msg = Config::get('messages.payment.lalamove_checkout_context_invalid');
            if (!is_string($msg) || $msg === '') {
                $msg = (string) Config::get('messages.payment.lalamove_checkout_outdated');
            }
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }
        if (!$this->validateVerifiedPaymentAgainstContext($verifiedPayment, $ctx)) {
            $msg = Config::get('messages.payment.verify_failed');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return;
        }
        $walletUsed = (float) ($ctx['wallet_used'] ?? 0);
        $pointsUsed = (int) ($ctx['points_used'] ?? 0);
        $itemsForOrder = $this->buildOrderItemsFromCart($cartItems);

        if ($orderNumber === '') {
            $orderNumber = $this->orderService->generateOrderNumber();
        }

        $forgetSnapshotToken = trim((string) ($ctx['checkout_token'] ?? ''));
        if ($forgetSnapshotToken === '') {
            $forgetSnapshotToken = $confirmCheckoutToken;
        }

        $this->createConfirmedOrderFromPayment(
            $userId,
            $itemsForOrder,
            $totalAmount,
            $paymentMethod,
            $shippingAddress,
            $orderNumber,
            $paymentIntentId,
            $walletUsed,
            $pointsUsed,
            $forgetSnapshotToken
        );
    }

    /**
     * @return array{
     *   payment_intent_id:string,
     *   order_number:string,
     *   shipping_address:string,
     *   shipping_lat:?string,
     *   shipping_lng:?string,
     *   payment_method:string,
     *   use_wallet:bool,
     *   use_points:bool,
     *   checkout_token:string
     * }
     */
    private function collectConfirmInput(): array
    {
        return [
            'payment_intent_id' => trim((string) ($_POST['payment_intent_id'] ?? $_POST['paypal_order_id'] ?? '')),
            'order_number' => trim((string) ($_POST['order_number'] ?? '')),
            'shipping_address' => trim((string) ($_POST['shipping_address'] ?? '')),
            'shipping_lat' => $this->normalizeCoordinate((string) ($_POST['shipping_lat'] ?? '')),
            'shipping_lng' => $this->normalizeCoordinate((string) ($_POST['shipping_lng'] ?? '')),
            'payment_method' => trim((string) ($_POST['payment_method'] ?? 'credit_card')),
            'use_wallet' => $this->toBool($_POST['use_wallet'] ?? '1'),
            'use_points' => $this->toBool($_POST['use_points'] ?? '0'),
            'checkout_token' => trim((string) ($_POST['checkout_token'] ?? '')),
        ];
    }

    /**
     * @param array{
     *   payment_intent_id:string,
     *   shipping_address:string,
     *   checkout_token:string
     * } $confirm
     */
    private function validateConfirmInput(array $confirm): bool
    {
        if ($confirm['payment_intent_id'] === '') {
            $msg = Config::get('messages.payment.missing_info');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return false;
        }
        $tok = trim((string) ($confirm['checkout_token'] ?? ''));
        if ($tok === '' && $confirm['shipping_address'] === '') {
            $msg = Config::get('messages.payment.shipping_required');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return false;
        }
        return true;
    }

    /**
     * @return array{status:string,amount:float,currency:string,id:string}|null
     */
    private function ensurePaymentSucceeded(string $paymentMethod, string $paymentIntentId): ?array
    {
        if ($paymentMethod !== PaymentProvider::PAYPAL) {
            $paymentService = $this->getStripePaymentService();
            if ($paymentService === null) {
                $msg = Config::get('messages.payment.stripe_config_error');
                $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 500);
                return null;
            }
            try {
                $intent = $paymentService->getPaymentIntent($paymentIntentId);
                if (($intent['status'] ?? '') !== 'succeeded') {
                    $msg = Config::get('messages.payment.not_completed');
                    $this->json(['success' => false, 'error' => $msg, 'message' => $msg, 'status' => (string) ($intent['status'] ?? '')], 400);
                    return null;
                }

                return [
                    'status' => (string) $intent['status'],
                    'amount' => ((float) ($intent['amount'] ?? 0)) / 100.0,
                    'currency' => strtoupper((string) ($intent['currency'] ?? '')),
                    'id' => (string) ($intent['id'] ?? $paymentIntentId),
                ];
            } catch (\Throwable $e) {
                error_log('Payment confirm getPaymentIntent: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
                $msg = Config::get('messages.payment.verify_failed');
                $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
                return null;
            }
        }

        $paypal = $this->getPayPalService();
        if ($paypal === null) {
            $msg = Config::get('messages.payment.verify_failed');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 500);
            return null;
        }
        try {
            $verified = $paypal->verifyOrder($paymentIntentId);
            if (($verified['status'] ?? '') !== 'COMPLETED') {
                $msg = Config::get('messages.payment.not_completed');
                $this->json(['success' => false, 'error' => $msg, 'message' => $msg, 'status' => (string) ($verified['status'] ?? '')], 400);
                return null;
            }
            return $verified;
        } catch (\Throwable $e) {
            error_log('PayPal verify order failed: ' . $e->getMessage() . ' [order=' . $paymentIntentId . ']');
            $msg = Config::get('messages.payment.verify_failed');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg], 400);
            return null;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $cartItems
     * @return array<int, array{id:mixed,name:mixed,qty:mixed,price:mixed}>
     */
    private function buildOrderItemsFromCart(array $cartItems): array
    {
        $itemsForOrder = [];
        foreach ($cartItems as $row) {
            $itemsForOrder[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'qty' => $row['qty'],
                'price' => $row['price'],
            ];
        }

        return $itemsForOrder;
    }

    private function normalizeCheckoutAddress(string $s): string
    {
        $s = trim((string) preg_replace('/\s+/u', ' ', $s));

        return $s;
    }

    /**
     * 建立付款授權前計算總額；Lalamove 會寫入 $_SESSION['checkout_lalamove'] 供 confirm 對齊運費。
     *
     * @return array{subtotal: float, shipping_fee: float, total: float}
     */
    private function computeCheckoutTotalsWithShipping(
        array $cartItems,
        string $shippingAddress,
        ?string $shippingLat = null,
        ?string $shippingLng = null
    ): array {
        $subtotal = round($this->cartModel->calculateSubtotal($cartItems), 2);
        $norm = $this->normalizeCheckoutAddress($shippingAddress);

        $svc = LalamoveCheckoutService::fromConfigOrNull();
        if ($svc === null) {
            $msg = (string) Config::get('messages.payment.lalamove_not_configured');
            throw new InvalidArgumentException($msg !== '' ? $msg : 'Lalamove not configured');
        }
        if (mb_strlen($norm) < 8) {
            $msg = (string) Config::get('messages.payment.lalamove_method_requires_address');
            throw new InvalidArgumentException($msg !== '' ? $msg : 'Address required');
        }
        $totalQty = $this->sumCartItemQuantity($cartItems);
        try {
            $coords = ($shippingLat !== null && $shippingLng !== null)
                ? ['lat' => $shippingLat, 'lng' => $shippingLng]
                : null;
            $quote = $svc->quoteDelivery($norm, $totalQty, $coords);
        } catch (\RuntimeException $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
        $fee = $quote['fee'];
        $shippingFingerprint = $this->buildShippingFingerprint($norm, $shippingLat, $shippingLng);
        $_SESSION['checkout_lalamove'] = [
            'fee' => $fee,
            'addr_hash' => $shippingFingerprint,
            'cart_sig' => $this->checkoutCartSignature($cartItems),
            'norm_address' => $norm,
            'quote_lat' => $shippingLat,
            'quote_lng' => $shippingLng,
        ];

        return [
            'subtotal' => $subtotal,
            'shipping_fee' => $fee,
            'total' => round($subtotal + $fee, 2),
        ];
    }

    /**
     * 確認訂單時與建立付款時相同運費（session + 地址 + 購物車簽名）。
     *
     * @return array{subtotal: float, shipping_fee: float, total: float}
     */
    private function totalsForOrderConfirmation(
        array $cartItems,
        string $shippingAddress,
        ?string $shippingLat = null,
        ?string $shippingLng = null
    ): array {
        $subtotal = round($this->cartModel->calculateSubtotal($cartItems), 2);
        $norm = $this->normalizeCheckoutAddress($shippingAddress);
        $sess = $_SESSION['checkout_lalamove'] ?? null;
        $cartSig = $this->checkoutCartSignature($cartItems);
        if (!is_array($sess) || !isset($sess['fee'], $sess['addr_hash'], $sess['cart_sig'])) {
            $msg = (string) Config::get('messages.payment.lalamove_session_stale');
            throw new InvalidArgumentException($msg !== '' ? $msg : 'Checkout outdated');
        }
        if ((string) $sess['cart_sig'] !== $cartSig) {
            $msg = (string) Config::get('messages.payment.lalamove_checkout_cart_changed');
            if ($msg === '') {
                $msg = (string) Config::get('messages.payment.lalamove_checkout_outdated');
            }
            throw new InvalidArgumentException($msg !== '' ? $msg : 'Checkout outdated');
        }

        $storedNorm = isset($sess['norm_address']) ? (string) $sess['norm_address'] : '';
        if ($storedNorm !== '' && $storedNorm !== $norm) {
            $msg = (string) Config::get('messages.payment.lalamove_checkout_shipping_mismatch');
            if ($msg === '') {
                $msg = (string) Config::get('messages.payment.lalamove_checkout_outdated');
            }
            throw new InvalidArgumentException($msg !== '' ? $msg : 'Checkout outdated');
        }

        $storeHash = (string) $sess['addr_hash'];
        $hashFromPost = $this->buildShippingFingerprint($norm, $shippingLat, $shippingLng);
        $hashOk = ($storeHash === $hashFromPost);

        // 報價 API 曾帶座標寫入 addr_hash，但 Stripe/PayPal 建立 intent 時 POST 常未帶座標 → 會變成 coord:none 永遠對不上
        if (
            !$hashOk
            && $shippingLat === null
            && $shippingLng === null
            && isset($sess['quote_lat'], $sess['quote_lng'])
            && $sess['quote_lat'] !== null
            && $sess['quote_lng'] !== null
            && (string) $sess['quote_lat'] !== ''
            && (string) $sess['quote_lng'] !== ''
        ) {
            $hashOk = $storeHash === $this->buildShippingFingerprint(
                $norm,
                (string) $sess['quote_lat'],
                (string) $sess['quote_lng']
            );
        }

        // 舊版 session 僅有 addr_hash + cart_sig（無 norm_address）：維持原本嚴格指紋比對
        if (!$hashOk && $storedNorm === '') {
            $hashOk = ($storeHash === $hashFromPost);
        }

        if ($hashOk) {
            $fee = (float) $sess['fee'];

            return [
                'subtotal' => $subtotal,
                'shipping_fee' => $fee,
                'total' => round($subtotal + $fee, 2),
            ];
        }
        $msg = (string) Config::get('messages.payment.lalamove_checkout_shipping_mismatch');
        if ($msg === '') {
            $msg = (string) Config::get('messages.payment.lalamove_checkout_outdated');
        }
        if ($msg === '') {
            $msg = (string) Config::get('messages.payment.lalamove_session_stale');
        }
        throw new InvalidArgumentException($msg !== '' ? $msg : 'Checkout outdated');

    }

    private function sumCartItemQuantity(array $cartItems): int
    {
        $q = 0;
        foreach ($cartItems as $row) {
            $q += (int) ($row['qty'] ?? 0);
        }

        return max(1, $q);
    }

    private function checkoutCartSignature(array $cartItems): string
    {
        $subtotal = round($this->cartModel->calculateSubtotal($cartItems), 2);
        $qty = $this->sumCartItemQuantity($cartItems);

        return hash('sha256', (string) $subtotal . '|' . (string) $qty);
    }

    private function clearCheckoutLalamoveSession(): void
    {
        unset($_SESSION['checkout_lalamove']);
    }

    /** 將座標正規化為固定 6 位小數字串，避免比較誤差。 */
    private function normalizeCoordinate(string $value): ?string
    {
        return CheckoutFingerprintService::normalizeCoordinate($value);
    }

    private function buildShippingFingerprint(string $address, ?string $lat = null, ?string $lng = null): string
    {
        return CheckoutFingerprintService::buildShippingFingerprint($address, $lat, $lng);
    }

    /**
     * 將常見真值字串/數值轉為布林值。
     *
     * @param mixed $value
     */
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

    private function getPayPalService(): ?PayPalService
    {
        if ($this->payPalService !== null) {
            return $this->payPalService;
        }
        try {
            $this->payPalService = new PayPalService();
            return $this->payPalService;
        } catch (\Throwable $e) {
            error_log('PayPal service init failed: ' . $e->getMessage());
            return null;
        }
    }

    private function validateVerifiedPaymentAgainstContext(array $verified, array $ctx): bool
    {
        $expectedAmount = round((float) ($ctx['payable_amount'] ?? 0), 2);
        $actualAmount = round((float) ($verified['amount'] ?? 0), 2);
        if (abs($actualAmount - $expectedAmount) > 0.01) {
            return false;
        }
        $expectedCurrency = strtoupper((string) ($ctx['currency'] ?? ''));
        $actualCurrency = strtoupper((string) ($verified['currency'] ?? ''));
        if ($expectedCurrency === '' || $actualCurrency === '') {
            return false;
        }
        return $expectedCurrency === $actualCurrency;
    }

    private function getCurrencyCode(): string
    {
        $currency = Config::get('currency', []);
        if (!is_array($currency)) {
            return Config::defaultCurrencyCode();
        }

        $code = strtoupper(trim((string) ($currency['code'] ?? Config::defaultCurrencyCode())));
        return $code !== '' ? $code : Config::defaultCurrencyCode();
    }

    private function getStripeCurrencyCode(): string
    {
        return strtolower($this->getCurrencyCode());
    }
}
