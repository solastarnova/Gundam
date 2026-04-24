<?php

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Services\InventoryService;
use App\Services\OrderStatusService;
use App\Services\WalletService;

/**
 * Admin orders: list, detail, status updates.
 * Cancelling paid/completed orders restocks items and credits wallet once.
 */
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

    /**
     * Update order status from admin form.
     * When moving to cancelled from paid/completed, restocks SKUs and credits user wallet once.
     */
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

        $stmt = $this->orderModel->getPdo()->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            $this->setError(Config::get('messages.admin.order_not_found'));
            $this->redirect('/admin/orders');
            return;
        }

        $currentStatus = (string) ($order['status'] ?? '');

        // Admin may set any status in config order_status.allowed (not limited to storefront transitions)
        if (!OrderStatusService::isAllowed($status)) {
            $this->setError(Config::get('messages.admin.order_status_invalid'));
            $this->redirect("/admin/orders/{$id}");
            return;
        }
        if (!OrderStatusService::canTransition($currentStatus, $status)) {
            $this->setError("不合規的狀態轉換：從 {$currentStatus} 到 {$status}");
            $this->redirect("/admin/orders/{$id}");
            return;
        }

        $pdo = $this->orderModel->getPdo();
        
        try {
            $pdo->beginTransaction();

            if (!$this->orderModel->updateStatusByAdmin($id, $status)) {
                throw new \RuntimeException('order_update_failed');
            }

            // Cancel after pay/complete: restock line items and refund wallet (idempotent refund row)
            if (
                $status === OrderStatusService::CANCELLED
                && in_array($currentStatus, [OrderStatusService::PAID, OrderStatusService::COMPLETED], true)
            ) {
                InventoryService::replenishStockForOrder($pdo, $id);

                $checkRefundStmt = $pdo->prepare(
                    "SELECT id FROM user_wallet_transactions WHERE order_id = ? AND type = 'refund' LIMIT 1"
                );
                $checkRefundStmt->execute([$id]);
                $alreadyRefunded = $checkRefundStmt->fetch() !== false;

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

}