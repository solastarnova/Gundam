<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\UserModel;
use App\Models\OrderModel;
use App\Models\ReviewModel;
use App\Services\FirebaseAdminAuth;
use App\Services\FirebaseWebConfig;
use App\Services\WalletService;

/** 處理會員中心頁面與帳號相關操作。 */
class AccountController extends Controller
{
    private UserModel $userModel;
    private OrderModel $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->orderModel = new OrderModel();
    }

    public function home(): void
{
    $user = $this->requireUser();
    $userId = $user['id'];

    $profile = $this->userModel->findById($userId) ?? [];
    $membershipInfo = $this->userModel->getMembershipInfo($userId);

    $this->render('account/home', [
        'title' => $this->titleWithSite('account_home'),
        'profile' => $profile,
        'user_name' => (string) ($profile['name'] ?? ($_SESSION['user_name'] ?? '')),
        'email' => (string) ($profile['email'] ?? ($_SESSION['email'] ?? '')),
        'membership' => $membershipInfo,
    ]);
}

    public function orders(): void
    {
        $user = $this->requireUser();
        $userId = (int) $_SESSION['user_id'];
        $orders = $this->orderModel->getUserOrders($userId);
        $stats = $this->orderModel->getUserOrderStats($userId);
        $this->render('account/orders', [
            'title' => $this->titleWithSite('account_orders'),
            'head_extra_css' => [],
            'orders' => $orders,
            'orderStats' => $stats,
        ]);
    }

    public function orderDetail(int $id): void
    {
        $id = (int) $id;
        $user = $this->requireUser();
        $userId = (int) $_SESSION['user_id'];
        if ($id <= 0) {
            $this->redirect($this->view->url('account/orders'));
            return;
        }
        $order = $this->orderModel->getOrderById($id, $userId);
        if (!$order) {
            $this->redirect($this->view->url('account/orders'));
            return;
        }
        $orderItems = $this->orderModel->getOrderItems($id);
        $reviewModel = new ReviewModel();
        $canReviewItems = [];
        foreach ($orderItems as $item) {
            $itemId = (int) ($item['item_id'] ?? 0);
            if ($itemId > 0) {
                $canReviewItems[$itemId] = $reviewModel->hasUnreviewedPurchase($userId, $itemId);
            }
        }
        $this->render('account/order-detail', [
            'title' => $this->titleWithSite('account_order_detail'),
            'head_extra_css' => [],
            'order' => $order,
            'orderItems' => $orderItems,
            'canReviewItems' => $canReviewItems,
            'reviewSuccess' => $this->consumeFlash('review_success'),
            'reviewError' => $this->consumeFlash('review_error'),
        ]);
    }

    public function submitReview(): void
    {
        $this->requireUser();
        $userId = (int) $_SESSION['user_id'];
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $title = trim((string) ($_POST['review_title'] ?? ''));
        $content = trim((string) ($_POST['review_content'] ?? ''));
        $rating = (int) ($_POST['review_rating'] ?? 0);

        if ($itemId <= 0) {
            $this->flash('review_error', Config::get('messages.account.review_invalid_product'));
            $this->redirect($orderId > 0 ? $this->view->url('account/order/' . $orderId) : $this->view->url('account/orders'));
            return;
        }

        $reviewModel = new ReviewModel();
        if (!$reviewModel->hasUnreviewedPurchase($userId, $itemId)) {
            $this->flash('review_error', Config::get('messages.account.review_not_eligible'));
            $this->redirect($orderId > 0 ? $this->view->url('account/order/' . $orderId) : $this->view->url('account/orders'));
            return;
        }

        if ($title === '') {
            $title = (string) Config::get('messages.account.review_default_title');
        }
        if ($rating < 1 || $rating > 5) {
            $rating = 5;
        }

        $reviewModel->addReview($userId, $itemId, $title, $content, $rating);
        $this->flash('review_success', Config::get('messages.account.review_thanks'));
        $this->redirect($orderId > 0 ? $this->view->url('account/order/' . $orderId) : $this->view->url('account/orders'));
    }

    public function settings(): void
    {
        $user = $this->requireUser();
        $userId = (int) $_SESSION['user_id'];
        $profile = $this->userModel->findById($userId);
        $isPasswordSetupMode = $this->canSetPasswordWithoutOld($profile);
        $this->render('account/settings', array_merge([
            'title' => $this->titleWithSite('account_settings'),
            'profile' => $profile,
            'isPasswordSetupMode' => $isPasswordSetupMode,
            'passwordErrors' => $this->consumeFlash('account_password_errors', []),
            'passwordSuccess' => $this->consumeFlash('account_password_success'),
            'phoneErrors' => $this->consumeFlash('account_phone_errors', []),
            'phoneSuccess' => $this->consumeFlash('account_phone_success'),
            'head_extra_css' => [],
        ], $this->firebaseSocialLinkingBundle()));
    }

    /** @return array<string, mixed> */
    private function firebaseSocialLinkingBundle(): array
    {
        if (FirebaseWebConfig::forJavaScript() === null || !FirebaseAdminAuth::isConfigured()) {
            return [];
        }

        return [
            'firebase_social_linking_enabled' => true,
            'firebase_enable_facebook' => filter_var(
                getenv('FIREBASE_ENABLE_FACEBOOK') ?: '',
                FILTER_VALIDATE_BOOLEAN
            ),
            'foot_extra_js' => ['js/account-firebase-link.js'],
        ];
    }

    public function payment(): void
    {
        $this->requireUser();
        $userId = (int) $_SESSION['user_id'];
        $walletBalance = (new WalletService())->getBalance($userId);

        $this->render('account/payment', [
            'title' => $this->titleWithSite('account_payment'),
            'wallet_balance' => $walletBalance,
            'head_extra_css' => [],
        ]);
    }

    /** 驗證舊密碼後更新目前登入使用者的新密碼。 */
    public function updatePassword(): void
    {
        $this->requireUser();
        $userId = (int) $_SESSION['user_id'];
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $profile = $this->userModel->findById($userId);
        $isPasswordSetupMode = $this->canSetPasswordWithoutOld($profile);

        $errors = [];

        if (!$isPasswordSetupMode && $currentPassword === '') {
            $errors['current_password'] = Config::get('messages.account.password_current_required');
        }

        $minLen = (int) Config::get('min_password_length', 8);
        if ($newPassword === '') {
            $errors['new_password'] = Config::get('messages.account.password_new_required');
        } elseif (strlen($newPassword) < $minLen) {
            $errors['new_password'] = sprintf(Config::get('messages.account.password_new_min'), $minLen);
        } elseif (
            ($currentPassword !== '' && hash_equals($currentPassword, $newPassword))
            || ($isPasswordSetupMode && $this->userModel->verifyPasswordForUser($userId, $newPassword))
        ) {
            $errors['new_password'] = Config::get('messages.account.password_same_as_current');
        }

        if ($confirmPassword === '') {
            $errors['confirm_password'] = Config::get('messages.account.password_new_confirm_required');
        } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
            $errors['confirm_password'] = Config::get('messages.auth.password_confirm_mismatch');
        }

        if (!empty($errors)) {
            $this->flash('account_password_errors', $errors);
            $this->redirect($this->view->url('account/settings'));
            return;
        }

        try {
            if ($isPasswordSetupMode) {
                if (!$this->userModel->updatePassword($userId, $newPassword)) {
                    $errors['general'] = Config::get('messages.account.password_update_error');
                    $this->flash('account_password_errors', $errors);
                    $this->redirect($this->view->url('account/settings'));
                    return;
                }
            } else {
                $errMsg = $this->userModel->changePassword($userId, $currentPassword, $newPassword);
                if ($errMsg !== null) {
                    if ($errMsg === Config::get('messages.account.user_not_found')) {
                        $errors['general'] = Config::get('messages.account.profile_not_found');
                    } else {
                        $errors['current_password'] = $errMsg;
                    }
                    $this->flash('account_password_errors', $errors);
                    $this->redirect($this->view->url('account/settings'));
                    return;
                }
            }
        } catch (\Throwable $e) {
            $this->flash('account_password_errors', ['general' => Config::get('messages.account.password_update_error')]);
            $this->redirect($this->view->url('account/settings'));
            return;
        }

        $this->flash('account_password_success', Config::get('messages.account.password_update_success'));
        $this->redirect($this->view->url('account/settings'));
    }

    private function canSetPasswordWithoutOld(?array $profile): bool
    {
        if (!is_array($profile) || $profile === []) {
            return false;
        }

        $firebaseUid = trim((string) ($profile['firebase_uid'] ?? ''));
        if ($firebaseUid === '') {
            return false;
        }

        if ((string) ($_SESSION['login_type'] ?? '') !== 'firebase') {
            return false;
        }

        if (!array_key_exists('has_set_password', $profile)) {
            return false;
        }

        return (int) $profile['has_set_password'] === 0;
    }

    public function updatePhone(): void
    {
        $this->requireUser();
        $userId = (int) $_SESSION['user_id'];
        $phone = trim((string) ($_POST['phone'] ?? ''));

        $this->userModel->updatePhone($userId, $phone);
        $this->flash('account_phone_success', Config::get('messages.account.phone_update_success'));
        $this->redirect($this->view->url('account/settings'));
    }

    public function getOrders(): void
    {
        $this->setupJsonApi();

        if (!isset($_SESSION['user_id'])) {
            $msg = (string) Config::get('messages.common.not_logged_in');
            $this->json(['success' => false, 'error' => $msg, 'message' => $msg, 'orders' => []], 401);
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $orders = $this->orderModel->getUserOrders($userId);
        $this->json(['success' => true, 'orders' => $orders]);
    }
}
