<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\AddressModel;
use App\Services\ShippingService;
use App\Services\WalletService;

class OrderController extends Controller
{
    private AddressModel $addressModel;

    public function __construct()
    {
        parent::__construct();
        $this->addressModel = new AddressModel();
    }

    public function checkout(): void
    {
        $isLoggedIn = isset($_SESSION['user_id']);
        $userId = $isLoggedIn ? (int) $_SESSION['user_id'] : 0;
        $shippingConfig = ShippingService::getConfig();
        $defaultShippingAddress = (string) Config::get('default_shipping_region', '香港');
        if ($isLoggedIn) {
            $defaultAddr = $this->addressModel->getDefaultAddress($userId);
            if ($defaultAddr) {
                $defaultShippingAddress = AddressModel::formatAddressAsOneLine($defaultAddr);
            }
        }
        $stripePublishableKey = getenv('STRIPE_PUBLISHABLE_KEY') ?: '';
        $paypalClientId = getenv('PAYPAL_CLIENT_ID') ?: '';
        $walletBalance = 0.0;
        if ($isLoggedIn) {
            $walletBalance = (new WalletService())->getBalance($userId);
        }
        $this->render('order/checkout', [
            'title' => $this->titleWithSite('checkout'),
            'head_extra_css' => [],
            'isLoggedIn' => $isLoggedIn,
            'loginUrl' => $this->view->url('login') . '?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/checkout'),
            'stripePublishableKey' => $stripePublishableKey,
            'paypalClientId' => $paypalClientId,
            'shippingConfig' => $shippingConfig,
            'defaultShippingAddress' => $defaultShippingAddress,
            'walletBalance' => $walletBalance,
        ]);
    }
}
