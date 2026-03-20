<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\UserModel;
use App\Models\OrderModel;
use App\Models\Review;
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
        $userId = (int) $_SESSION['user_id'];
        $profile = $this->userModel->findById($userId);
        $this->render('account/home', [
            'title' => '個人資料 - ' . $this->getSiteName(),
            'user_name' => $profile['name'] ?? $_SESSION['user_name'] ?? '',
            'email' => $profile['email'] ?? $_SESSION['email'] ?? '',
            'head_extra_css' => [],
        ]);
    }

    public function orders(): void
    {
        $user = $this->requireUser();
        $userId = (int) $_SESSION['user_id'];
        $orders = $this->orderModel->getUserOrders($userId);
        $stats = $this->orderModel->getUserOrderStats($userId);
        $this->render('account/orders', [
            'title' => '訂單記錄 - ' . $this->getSiteName(),
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
            'title' => '訂單詳情 - ' . $this->getSiteName(),
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
            $this->flash('review_error', '無效的商品');
            $this->redirect($orderId > 0 ? $this->view->url('account/order/' . $orderId) : $this->view->url('account/orders'));
            return;
        }

        $reviewModel = new Review();
        if (!$reviewModel->hasUnreviewedPurchase($userId, $itemId)) {
            $this->flash('review_error', '您尚未購買此商品或已評價過');
            $this->redirect($orderId > 0 ? $this->view->url('account/order/' . $orderId) : $this->view->url('account/orders'));
            return;
        }

        if ($title === '') {
            $title = '用戶評價';
        }
        if ($rating < 1 || $rating > 5) {
            $rating = 5;
        }

        $reviewModel->addReview($userId, $itemId, $title, $content, $rating);
        $this->flash('review_success', '感謝您的評價！');
        $this->redirect($orderId > 0 ? $this->view->url('account/order/' . $orderId) : $this->view->url('account/orders'));
    }

    public function settings(): void
    {
        $user = $this->requireUser();
        $userId = (int) $_SESSION['user_id'];
        $profile = $this->userModel->findById($userId);
        $this->render('account/settings', [
            'title' => '帳戶設定 - ' . $this->getSiteName(),
            'profile' => $profile,
            'passwordErrors' => $this->consumeFlash('account_password_errors', []),
            'passwordSuccess' => $this->consumeFlash('account_password_success'),
            'emailErrors' => $this->consumeFlash('account_email_errors', []),
            'emailSuccess' => $this->consumeFlash('account_email_success'),
            'phoneErrors' => $this->consumeFlash('account_phone_errors', []),
            'phoneSuccess' => $this->consumeFlash('account_phone_success'),
            'head_extra_css' => [],
        ]);
    }

    public function payment(): void
    {
        $this->requireUser();
        $userId = (int) $_SESSION['user_id'];
        $walletBalance = (new WalletService())->getBalance($userId);

        $this->render('account/payment', [
            'title' => '付款方式 - ' . $this->getSiteName(),
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
            $errors['current_password'] = '請輸入目前密碼';
        }

        $minLen = (int) Config::get('min_password_length', 8);
        if ($newPassword === '') {
            $errors['new_password'] = '請輸入新密碼';
        } elseif (strlen($newPassword) < $minLen) {
            $errors['new_password'] = "新密碼至少需 {$minLen} 個字元";
        } elseif ($currentPassword !== '' && hash_equals($currentPassword, $newPassword)) {
            $errors['new_password'] = '新密碼不可與目前密碼相同';
        }

        if ($confirmPassword === '') {
            $errors['confirm_password'] = '請再次輸入新密碼';
        } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
            $errors['confirm_password'] = '兩次輸入的密碼不一致';
        }

        if (!empty($errors)) {
            $this->flash('account_password_errors', $errors);
            $this->redirect($this->view->url('account/settings'));
            return;
        }

        try {
            $errMsg = $this->userModel->changePassword($userId, $currentPassword, $newPassword);
            if ($errMsg !== null) {
                if ($errMsg === '用戶不存在') {
                    $errors['general'] = '找不到帳戶資料，請重新登入後再試。';
                } else {
                    $errors['current_password'] = $errMsg;
                }
                $this->flash('account_password_errors', $errors);
                $this->redirect($this->view->url('account/settings'));
                return;
            }
        } catch (\Throwable $e) {
            $this->flash('account_password_errors', ['general' => '更新密碼時發生錯誤，請稍後再試。']);
            $this->redirect($this->view->url('account/settings'));
            return;
        }

        $this->flash('account_password_success', '密碼已成功更新');
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
            $errors['email'] = '請輸入新電郵';
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '請輸入有效的電郵地址';
        } elseif ($newEmail !== $currentEmail && $this->userModel->emailExists($newEmail)) {
            $errors['email'] = '此電郵已被其他帳號使用';
        }
        if ($currentPassword === '') {
            $errors['current_password'] = '請輸入目前密碼以確認身份';
        } elseif (!$this->userModel->verifyPasswordForUser($userId, $currentPassword)) {
            $errors['current_password'] = '目前密碼不正確';
        }

        if (!empty($errors)) {
            $this->flash('account_email_errors', $errors);
            $this->redirect($this->view->url('account/settings'));
            return;
        }

        if ($this->userModel->updateEmail($userId, $newEmail)) {
            $_SESSION['email'] = $newEmail;
            $this->flash('account_email_success', '電郵已更新');
        } else {
            $this->flash('account_email_errors', ['email' => '更新失敗，此電郵可能已被使用']);
        }
        $this->redirect($this->view->url('account/settings'));
    }

    public function updatePhone(): void
    {
        $this->requireUser();
        $userId = (int) $_SESSION['user_id'];
        $phone = trim((string) ($_POST['phone'] ?? ''));

        $this->userModel->updatePhone($userId, $phone);
        $this->flash('account_phone_success', '手機號碼已更新');
        $this->redirect($this->view->url('account/settings'));
    }

    public function getOrders(): void
    {
        $this->setupJsonApi();

        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => '未登入', 'message' => '未登入', 'orders' => []], 401);
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $orders = $this->orderModel->getUserOrders($userId);
        $this->json(['success' => true, 'orders' => $orders]);
    }
}
