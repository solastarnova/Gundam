<?php

namespace App\Controllers\Admin;

use App\Models\OrderModel;

class OrderController extends BaseController
{
    private OrderModel $orderModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new OrderModel();
    }
    
    /**
     * 订单列表
     */
    public function index()
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;
        $status = $_GET['status'] ?? '';
        
        // 构建查询
        $sql = "SELECT o.*, u.name as user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id";
        $countSql = "SELECT COUNT(*) FROM orders";
        $params = [];
        
        if (!empty($status)) {
            $sql .= " WHERE o.status = ?";
            $countSql .= " WHERE status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        // 获取订单列表
        $stmt = $this->orderModel->getPdo()->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
        
        // 获取总数
        $stmt = $this->orderModel->getPdo()->prepare($countSql);
        $stmt->execute($status ? [$status] : []);
        $total = (int)$stmt->fetchColumn();
        
        // 获取各状态订单数量
        $stats = $this->orderModel->getUserOrderStats(0); // 0表示所有用户
        
        $this->render('orders/index', [
            'title' => '订单管理',
            'orders' => $orders,
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'status' => $status,
            'stats' => $stats
        ]);
    }
    
    /**
     * 订单详情
     */
    public function detail(int $id)
    {
        // 获取订单信息
        $stmt = $this->orderModel->getPdo()->prepare(
            "SELECT o.*, u.name as user_name, u.email 
             FROM orders o 
             LEFT JOIN users u ON o.user_id = u.id 
             WHERE o.id = ?"
        );
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            $this->setError('订单不存在');
            $this->redirect('/admin/orders');
            return;
        }
        
        // 获取订单商品
        $stmt = $this->orderModel->getPdo()->prepare(
            "SELECT * FROM order_items WHERE order_id = ?"
        );
        $stmt->execute([$id]);
        $items = $stmt->fetchAll();
        
        $this->render('orders/detail', [
            'title' => '订单详情 #' . $order['order_number'],
            'order' => $order,
            'items' => $items
        ]);
    }
    
    /**
     * 更新订单状态
     */
    public function updateStatus(int $id)
    {
        $status = $_POST['status'] ?? '';
        $allowedStatus = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];
        
        if (!in_array($status, $allowedStatus)) {
            $this->setError('无效的订单状态');
            $this->redirect("/admin/orders/{$id}");
            return;
        }
        
        try {
            $stmt = $this->orderModel->getPdo()->prepare(
                "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?"
            );
            $stmt->execute([$status, $id]);
            
            $this->setSuccess('订单状态已更新');
        } catch (\PDOException $e) {
            error_log('Order status update error: ' . $e->getMessage());
            $this->setError('更新失败，请稍后重试');
        }
        
        $this->redirect("/admin/orders/{$id}");
    }
}