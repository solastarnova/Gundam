<?php

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Models\OrderModel;
use App\Services\OrderStatusService;
use App\Services\WalletService;

class OrderController extends BaseController
{
    private OrderModel $orderModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new OrderModel();
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
        $id = (int) $id;

        if (!$this->requireAdminCsrf()) {
            $this->setError(Config::get('messages.admin_login.csrf_invalid'));
            $this->redirect("/admin/orders/{$id}");
            return;
        }
        $status = trim((string) ($_POST['status'] ?? ''));

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
        $shouldReplenishStock = $status === 'cancelled' && $currentStatus !== 'cancelled';
        $shouldRefundWallet = $currentStatus === 'paid' && $status === 'cancelled';

        if (!OrderStatusService::canTransition($currentStatus, $status)) {
            $this->setError(Config::get('messages.admin.order_status_invalid'));
            $this->redirect("/admin/orders/{$id}");
            return;
        }

        $userId = (int) ($order['user_id'] ?? 0);
        $amount = (float) ($order['total_amount'] ?? 0);
        $desc = sprintf(
            '訂單 #%s 取消退款入錢包',
            $order['order_number'] ?? (string) $order['id']
        );

        $pdo = $this->orderModel->getPdo();
        $pdo->beginTransaction();
        try {
            if (!$this->orderModel->updateStatusByAdmin($id, $status)) {
                throw new \RuntimeException('order_update_failed');
            }

            if ($shouldReplenishStock) {
                $this->orderModel->replenishStockForOrder($id);
            }

            if ($shouldRefundWallet && $userId > 0) {
                WalletService::addCreditWithinTransaction(
                    $pdo,
                    $userId,
                    $amount,
                    'refund',
                    (int) $order['id'],
                    $desc
                );
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof \RuntimeException && $e->getMessage() === 'order_update_failed') {
                $this->setError(Config::get('messages.admin.order_status_invalid'));
            } elseif ($shouldRefundWallet) {
                $this->setError(Config::get('messages.admin.order_wallet_refund_failed'));
            } else {
                $this->setError(Config::get('messages.admin.order_stock_refund_failed'));
            }
            $this->redirect("/admin/orders/{$id}");
            return;
        }

        $this->setSuccess(Config::get('messages.admin.order_status_updated'));
        $this->redirect("/admin/orders/{$id}");
    }
}