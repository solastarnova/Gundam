<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\AddressModel;
use App\Models\UserModel;
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
            'shippingConfig' => $shippingConfig,
            'defaultShippingAddress' => $defaultShippingAddress,
            'walletBalance' => $walletBalance,
            'pointsBalance' => $pointsBalance,
        ]);
    }

    private function awardPointsForOrder(int $userId, int $orderId, float $orderAmount): void
{
    $userModel = new UserModel();
    
    if ($userModel->hasEarnedPointsForOrder($userId, $orderId)) {
        return;
    }
    
    // 更新用户累计消费金额
    $pdo = Database::getConnection();
    $updateSpent = $pdo->prepare("
        UPDATE users SET total_spent = total_spent + ? WHERE id = ?
    ");
    $updateSpent->execute([$orderAmount, $userId]);
    
    // 刷新会员等级
    $userModel->refreshMembershipLevelBySpent($userId);
    
    // 获取会员信息以计算积分倍数
    $membershipInfo = $userModel->getMembershipInfo($userId);
    $multiplier = $membershipInfo['points_multiplier'] ?? 1;
    
    // 计算应得积分（每消费1元得1分，乘以会员倍数）
    $pointsToAdd = (int) floor($orderAmount * $multiplier);
    $desc = '订单 #' . $orderId . ' 消费获得积分 (x' . $multiplier . ')';
    
    $userModel->addPoints($userId, $pointsToAdd, $orderId, $desc);
}
}
