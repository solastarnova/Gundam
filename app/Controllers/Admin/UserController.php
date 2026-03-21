<?php

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Models\OrderModel;
use App\Models\UserModel;

class UserController extends BaseController
{
    private UserModel $userModel;
    private OrderModel $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->orderModel = new OrderModel();
    }

    public function index()
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = (int) Config::get('admin.list_per_page', 15);
        $search = trim($_GET['search'] ?? '');

        $result = $this->userModel->getListForAdmin(['search' => $search], $page, $limit);
        $users = $result['rows'];

        $userIds = array_column($users, 'id');
        $orderCounts = $this->orderModel->getOrderCountByUserIds($userIds);
        foreach ($users as &$user) {
            $user['order_count'] = $orderCounts[(int) $user['id']] ?? 0;
        }
        unset($user);

        $this->render('users/index', [
            'title' => Config::get('messages.titles.admin_users'),
            'users' => $users,
            'page' => $page,
            'total' => $result['total'],
            'limit' => $limit,
            'search' => $search,
        ]);
    }
    
    public function toggleStatus(int $id)
    {
        if (!$this->requireAdminCsrf()) {
            $this->setError(Config::get('messages.admin_login.csrf_invalid'));
            $this->redirect('/admin/users');
            return;
        }

        $id = (int) $id;
        $user = $this->userModel->findById($id);
        if (!$user) {
            $this->setError(Config::get('messages.admin.user_not_found'));
            $this->redirect('/admin/users');
            return;
        }

        $current = $user['status'] ?? 'active';
        $newStatus = $current === 'active' ? 'disabled' : 'active';

        if (!$this->userModel->updateStatus($id, $newStatus)) {
            $this->setError(Config::get('messages.admin.update_failed'));
            $this->redirect('/admin/users');
            return;
        }

        $this->setSuccess($newStatus === 'active' ? Config::get('messages.admin.user_enabled') : Config::get('messages.admin.user_disabled'));
        $this->redirect('/admin/users');
    }

    public function show(int $id)
    {
        $id = (int) $id;
        $user = $this->userModel->findById($id);
        if (!$user) {
            $this->setError(Config::get('messages.admin.user_not_found'));
            $this->redirect('/admin/users');
            return;
        }

        $recentLimit = max(1, (int) Config::get('admin.user_detail_recent_orders', 10));
        $stmt = $this->userModel->getPdo()->prepare(
            "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->bindValue(1, $id, \PDO::PARAM_INT);
        $stmt->bindValue(2, $recentLimit, \PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll();
        
        $this->render('users/show', [
            'title' => sprintf(
                (string) Config::get('messages.titles.admin_user_detail'),
                $user['name'] ?? ''
            ),
            'user' => $user,
            'orders' => $orders
        ]);
    }
}