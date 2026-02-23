<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;
use App\Models\OrderModel;

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

    public function orderDetail(string $id): void
    {
        $user = $this->requireUser();
        $userId = (int) $_SESSION['user_id'];
        $orderId = (int) $id;
        if ($orderId <= 0) {
            $this->redirect($this->view->url('account/orders'));
            return;
        }
        $order = $this->orderModel->getOrderById($orderId, $userId);
        if (!$order) {
            $this->redirect($this->view->url('account/orders'));
            return;
        }
        $orderItems = $this->orderModel->getOrderItems($orderId);
        $this->render('account/order_detail', [
            'title' => '訂單詳情 - ' . $this->getSiteName(),
            'head_extra_css' => [],
            'order' => $order,
            'orderItems' => $orderItems,
        ]);
    }

    public function settings(): void
    {
        $this->requireUser();
        $this->render('account/settings', [
            'title' => '修改密碼 - ' . $this->getSiteName(),
            'passwordErrors' => $this->consumeFlash('account_password_errors', []),
            'passwordSuccess' => $this->consumeFlash('account_password_success'),
            'head_extra_css' => [],
        ]);
    }

    /** Change password: current_password / new_password / confirm_password; length from config; new must differ from current. */
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

        $minLen = (int) ($this->getConfig()['min_password_length'] ?? 8);
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

    public function getOrders(): void
    {
        $this->setupJsonApi();

        if (!isset($_SESSION['user_id'])) {
            $this->json([]);
            return;
        }
        $userId = (int) $_SESSION['user_id'];
        $orders = $this->orderModel->getUserOrders($userId);
        $this->json($orders);
    }
}
