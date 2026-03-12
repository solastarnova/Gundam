<?php

namespace App\Controllers\Admin;

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
    
    /**
     * 仪表盘首页
     */
    public function index()
    {
        // 获取统计数据
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
    
    /**
     * 获取订单总数
     */
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
        return $this->orderModel->getRecentOrders(5);
    }

    private function getLowStockProducts(): array
    {
        return $this->productModel->getLowStock(10, 5);
    }
}