<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\AddressModel;
use App\Models\UserModel;
use App\Services\LalamoveCheckoutService;
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
        $defaultShippingAddress = (string) Config::get('default_shipping_region', __m('checkout.default_shipping_region'));
        if ($isLoggedIn) {
            $defaultAddr = $this->addressModel->getDefaultAddress($userId);
            if ($defaultAddr) {
                $defaultShippingAddress = AddressModel::formatAddressAsOneLine($defaultAddr);
            }
        }
        $stripePublishableKey = getenv('STRIPE_PUBLISHABLE_KEY') ?: '';
        $paypalClientId = getenv('PAYPAL_CLIENT_ID') ?: '';
        $walletBalance = 0.0;
        $pointsBalance = 0;
        if ($isLoggedIn) {
            $walletBalance = (new WalletService())->getBalance($userId);
            $pointsBalance = (new UserModel())->getPointsBalance($userId);
        }
        $this->render('order/checkout', [
            'title' => $this->titleWithSite('checkout'),
            'head_extra_css' => [],
            'isLoggedIn' => $isLoggedIn,
            'loginUrl' => $this->view->url('login') . '?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/checkout'),
            'stripePublishableKey' => $stripePublishableKey,
            'paypalClientId' => $paypalClientId,
            'mapClientConfig' => $this->getMapClientConfig(),
            'defaultShippingAddress' => $defaultShippingAddress,
            'walletBalance' => $walletBalance,
            'pointsBalance' => $pointsBalance,
            'lalamoveCheckoutEnabled' => LalamoveCheckoutService::isCheckoutConfigured(),
        ]);
    }
}
