<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\UserModel;
use App\Models\OrderModel;
use App\Models\Review;
use App\Services\FirebaseAdminAuth;
use App\Services\FirebaseWebConfig;
use App\Services\WalletService;

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
    
    $this->userModel->refreshMembershipLevelBySpent($userId);
    
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
        $reviewModel = new Review();
        $canReviewItems = [];
        foreach ($orderItems as $item) {
            $itemId = (int) ($item['item_id'] ?? 0);
            if ($itemId > 0) {
                $canReviewItems[$itemId] = $reviewModel->hasUnreviewedPurchase($userId, $itemId);
            }
        }
        $this->render('account/order_detail', [
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

        $reviewModel = new Review();
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
        $this->render('account/settings', array_merge([
            'title' => $this->titleWithSite('account_settings'),
            'profile' => $profile,
            'passwordErrors' => $this->consumeFlash('account_password_errors', []),
            'passwordSuccess' => $this->consumeFlash('account_password_success'),
            'emailErrors' => $this->consumeFlash('account_email_errors', []),
            'emailSuccess' => $this->consumeFlash('account_email_success'),
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

    public function updatePassword(): void
    {
        $this->requireUser();
        $userId = (int) $_SESSION['user_id'];
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        $errors = [];

        if ($currentPassword === '') {
            $errors['current_password'] = Config::get('messages.account.password_current_required');
        }

        $minLen = (int) Config::get('min_password_length', 8);
        if ($newPassword === '') {
            $errors['new_password'] = Config::get('messages.account.password_new_required');
        } elseif (strlen($newPassword) < $minLen) {
            $errors['new_password'] = sprintf(Config::get('messages.account.password_new_min'), $minLen);
        } elseif ($currentPassword !== '' && hash_equals($currentPassword, $newPassword)) {
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
        } catch (\Throwable $e) {
            $this->flash('account_password_errors', ['general' => Config::get('messages.account.password_update_error')]);
            $this->redirect($this->view->url('account/settings'));
            return;
        }

        $this->flash('account_password_success', Config::get('messages.account.password_update_success'));
        $this->redirect($this->view->url('account/settings'));
    }

    public function updateEmail(): void
    {
        $this->requireUser();
        $userId = (int) $_SESSION['user_id'];
        $newEmail = trim((string) ($_POST['email'] ?? ''));
        $currentPassword = (string) ($_POST['current_password'] ?? '');

        $profile = $this->userModel->findById($userId);
        $currentEmail = $profile['email'] ?? '';
        $errors = [];
        if ($newEmail === '') {
            $errors['email'] = Config::get('messages.account.email_new_required');
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = Config::get('messages.auth.email_invalid');
        } elseif ($newEmail !== $currentEmail && $this->userModel->emailExists($newEmail)) {
            $errors['email'] = Config::get('messages.account.email_taken_other');
        }
        if ($currentPassword === '') {
            $errors['current_password'] = Config::get('messages.account.password_current_for_email');
        } elseif (!$this->userModel->verifyPasswordForUser($userId, $currentPassword)) {
            $errors['current_password'] = Config::get('messages.account.password_current_wrong');
        }

        if (!empty($errors)) {
            $this->flash('account_email_errors', $errors);
            $this->redirect($this->view->url('account/settings'));
            return;
        }

        if ($this->userModel->updateEmail($userId, $newEmail)) {
            $_SESSION['email'] = $newEmail;
            $this->flash('account_email_success', Config::get('messages.account.email_update_success'));
        } else {
            $this->flash('account_email_errors', ['email' => Config::get('messages.account.email_update_failed')]);
        }
        $this->redirect($this->view->url('account/settings'));
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
