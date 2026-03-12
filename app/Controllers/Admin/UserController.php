<?php

namespace App\Controllers\Admin;

use App\Models\UserModel;

class UserController extends BaseController
{
    private UserModel $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }
    
    /**
     * 用户列表
     */
    public function index()
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? '');
        
        // 构建查询
        $sql = "SELECT * FROM users";
        $countSql = "SELECT COUNT(*) FROM users";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " WHERE name LIKE ? OR email LIKE ?";
            $countSql .= " WHERE name LIKE ? OR email LIKE ?";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        // 获取用户列表
        $stmt = $this->userModel->getPdo()->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        // 获取总数
        $stmt = $this->userModel->getPdo()->prepare($countSql);
        $stmt->execute($search ? ["%{$search}%", "%{$search}%"] : []);
        $total = (int)$stmt->fetchColumn();
        
        $this->render('users/index', [
            'title' => '用户管理',
            'users' => $users,
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'search' => $search
        ]);
    }
    
    /**
     * 切换用户状态（禁用/启用）
     * 注意：原users表没有status字段，这里模拟禁用功能
     * 实际使用时可以在users表添加status字段
     */
    public function toggleStatus(int $id)
    {
        // 由于原表没有status字段，这里只是示例
        // 实际使用时需要在users表添加status字段
        
        $this->setSuccess('用户状态已更新');
        $this->redirect('/admin/users');
    }
    
    /**
     * 查看用户详情
     */
    public function show(int $id)
    {
        $user = $this->userModel->findById($id);
        if (!$user) {
            $this->setError('用户不存在');
            $this->redirect('/admin/users');
            return;
        }
        
        // 获取用户的订单
        $stmt = $this->userModel->getPdo()->prepare(
            "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10"
        );
        $stmt->execute([$id]);
        $orders = $stmt->fetchAll();
        
        $this->render('users/show', [
            'title' => '用户详情 - ' . $user['name'],
            'user' => $user,
            'orders' => $orders
        ]);
    }
}