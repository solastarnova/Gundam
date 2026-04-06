<?php

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Services\OrderStatusService;
use App\Services\WalletService;

class OrderController extends BaseController
{
    private OrderModel $orderModel;
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new OrderModel();
        $this->userModel = new UserModel();
    }
    
    public function index()
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = (int) Config::get('admin.list_per_page', 15);
        $status = $_GET['status'] ?? '';

        $result = $this->orderModel->getListForAdmin(['status' => $status], $page, $limit);
        $stats = $this->orderModel->getAllOrderStats();

        $this->render('orders/index', [
            'title' => Config::get('messages.titles.admin_orders'),
            'orders' => $result['rows'],
            'page' => $page,
            'total' => $result['total'],
            'limit' => $limit,
            'status' => $status,
            'stats' => $stats,
        ]);
    }

    public function detail(int $id)
    {
        $id = (int) $id;
        $stmt = $this->orderModel->getPdo()->prepare(
            "SELECT o.*, u.name as user_name, u.email 
             FROM orders o 
             LEFT JOIN users u ON o.user_id = u.id 
             WHERE o.id = ?"
        );
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            $this->setError(Config::get('messages.admin.order_not_found'));
            $this->redirect('/admin/orders');
            return;
        }
        $stmt = $this->orderModel->getPdo()->prepare(
            "SELECT * FROM order_items WHERE order_id = ?"
        );
        $stmt->execute([$id]);
        $items = $stmt->fetchAll();
        
        $this->render('orders/detail', [
            'title' => sprintf(
                (string) Config::get('messages.titles.admin_order_detail'),
                $order['order_number'] ?? ''
            ),
            'order' => $order,
            'items' => $items
        ]);
    }
    
    public function updateStatus(int $id)
    {
        if (!$this->requireAdminCsrf()) {
            $this->setError(Config::get('messages.admin_login.csrf_invalid'));
            $this->redirect("/admin/orders/{$id}");
            return;
        }

        $status = strtolower(trim((string) ($_POST['status'] ?? '')));
        if ($status === '') {
            $this->setError(Config::get('messages.admin.order_status_required'));
            $this->redirect("/admin/orders/{$id}");
            return;
        }

        // 获取当前订单信息
        $stmt = $this->orderModel->getPdo()->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            $this->setError(Config::get('messages.admin.order_not_found'));
            $this->redirect('/admin/orders');
            return;
        }

        $currentStatus = (string) ($order['status'] ?? '');

        // 后台可直接设置为任意合法状态（不受前台状态机限制）
        if (!OrderStatusService::isAllowed($status)) {
            $this->setError(Config::get('messages.admin.order_status_invalid'));
            $this->redirect("/admin/orders/{$id}");
            return;
        }

        $pdo = $this->orderModel->getPdo();
        
        try {
            $pdo->beginTransaction();

            // 更新订单状态
            if (!$this->orderModel->updateStatusByAdmin($id, $status)) {
                throw new \RuntimeException('order_update_failed');
            }

            // 处理取消订单：已付款/已完成订单取消时，执行补货与退款
            if ($status === 'cancelled' && in_array($currentStatus, ['paid', 'completed'], true)) {
                // 补货
                $this->replenishStock($id);

                // 防止重复退款（例如重复提交取消）
                $checkRefundStmt = $pdo->prepare(
                    "SELECT id FROM user_wallet_transactions WHERE order_id = ? AND type = 'refund' LIMIT 1"
                );
                $checkRefundStmt->execute([$id]);
                $alreadyRefunded = $checkRefundStmt->fetch() !== false;

                // 退款到钱包
                $userId = (int) $order['user_id'];
                $amount = (float) $order['total_amount'];

                if ($amount > 0 && !$alreadyRefunded) {
                    WalletService::addCreditWithinTransaction($pdo, $userId, $amount, 'refund', $id, '订单取消退款');
                }
            }

            $pdo->commit();
            $this->setSuccess(Config::get('messages.admin.order_status_updated'));
            
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->setError('状态更新失败: ' . $e->getMessage());
        }
        
        $this->redirect("/admin/orders/{$id}");
    }

    private function replenishStock(int $orderId): void
    {
        $stmt = $this->orderModel->getPdo()->prepare("
            SELECT item_id, quantity FROM order_items WHERE order_id = ?
        ");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();
        
        foreach ($items as $item) {
            $updateStmt = $this->orderModel->getPdo()->prepare("
                UPDATE items SET stock_quantity = stock_quantity + ? WHERE id = ?
            ");
            $updateStmt->execute([$item['quantity'], $item['item_id']]);
        }
    }
}