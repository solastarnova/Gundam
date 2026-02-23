<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AddressModel;
use App\Models\CartModel;
use App\Models\Order;
use App\Models\OrderModel;

class OrderController extends Controller
{
    private CartModel $cartModel;
    private OrderModel $orderModel;
    private AddressModel $addressModel;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel = new CartModel();
        $this->orderModel = new OrderModel();
        $this->addressModel = new AddressModel();
    }

    public function checkout(): void
    {
        $user = $this->requireUser();
        $userId = (int) $user['id'];
        $config = $this->getConfig();
        $shippingConfig = $config['shipping'] ?? [];
        $defaultAddr = $this->addressModel->getDefaultAddress($userId);
        $defaultShippingAddress = $defaultAddr ? AddressModel::formatAddressAsOneLine($defaultAddr) : (string) ($config['default_shipping_region'] ?? '香港');
        $stripePublishableKey = getenv('STRIPE_PUBLISHABLE_KEY') ?: '';
        $paypalClientId = getenv('PAYPAL_CLIENT_ID') ?: '';
        $this->render('order/checkout', [
            'title' => '結帳付款 - ' . $this->getSiteName(),
            'head_extra_css' => [],
            'stripePublishableKey' => $stripePublishableKey,
            'paypalClientId' => $paypalClientId,
            'shippingConfig' => $shippingConfig,
            'defaultShippingAddress' => $defaultShippingAddress,
        ]);
    }

    /** Checkout: create order from cart and clear cart. */
    public function process(): void
    {
        $this->setupJsonApi();

        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'message' => '請先登入'], 401);
            return;
        }
        $userId = (int) $_SESSION['user_id'];

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $paymentMethod = isset($input['payment_method']) ? trim((string) $input['payment_method']) : 'credit';
        if ($paymentMethod !== 'paypal') {
            $paymentMethod = 'credit';
        }
        $shippingAddress = isset($input['shipping_address']) ? trim((string) $input['shipping_address']) : '';
        if ($shippingAddress === '') {
            $shippingAddress = (string) ($this->getConfig()['default_shipping_region'] ?? '香港');
        }

        $rows = $this->cartModel->getCartItems($userId);
        $items = [];
        $total = 0.0;
        foreach ($rows as $row) {
            $items[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'qty' => $row['qty'],
                'price' => $row['price'],
            ];
            $total += $row['price'] * $row['qty'];
        }

        if (count($items) === 0) {
            $this->json(['success' => false, 'message' => '購物車是空的'], 400);
            return;
        }

        try {
            $order = new Order();
            $orderId = $order->create($userId, ['total' => $total, 'items' => $items], $paymentMethod, $shippingAddress);
            $this->cartModel->clearCart($userId);

            $orderRow = $this->orderModel->getOrderById($orderId, $userId);
            $orderNumber = $orderRow['order_number'] ?? '';

            $this->json(['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => '建立訂單失敗，請稍後再試'], 500);
        }
    }
}
