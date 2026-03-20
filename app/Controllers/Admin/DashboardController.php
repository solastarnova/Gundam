<?php

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Models\Product;

class DashboardController extends BaseController
{
    private OrderModel $orderModel;
    private UserModel $userModel;
    private Product $productModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new OrderModel();
        $this->userModel = new UserModel();
        $this->productModel = new Product();
    }
    
    public function index()
    {
        $stats = [
            'total_orders' => $this->getTotalOrders(),
            'total_users' => $this->getTotalUsers(),
            'total_products' => $this->getTotalProducts(),
            'recent_orders' => $this->getRecentOrders(),
            'low_stock_products' => $this->getLowStockProducts()
        ];
        
        $this->render('dashboard', [
            'title' => '仪表盘',
            'stats' => $stats
        ]);
    }
    
    private function getTotalOrders(): int
    {
        return $this->orderModel->getTotalCount();
    }

    private function getTotalUsers(): int
    {
        return $this->userModel->getTotalCount();
    }

    private function getTotalProducts(): int
    {
        return $this->productModel->getTotalCount();
    }

    private function getRecentOrders(): array
    {
        $limit = (int) Config::get('admin.dashboard_recent_orders', 5);
        return $this->orderModel->getRecentOrders($limit);
    }

    private function getLowStockProducts(): array
    {
        $threshold = (int) Config::get('admin.low_stock_threshold', 10);
        $limit = (int) Config::get('admin.low_stock_limit', 5);
        return $this->productModel->getLowStock($threshold, $limit);
    }
}