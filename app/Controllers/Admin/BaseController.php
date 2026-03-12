<?php

namespace App\Controllers\Admin;

use App\Core\Controller;

class BaseController extends Controller
{
    protected array $adminUser;
    
    public function __construct()
    {
        parent::__construct();
        
        // 检查登录状态（排除登录页面）
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($currentPath, '/admin/login') === false) {
            $this->checkAuth();
        }
    }
    
    /**
     * 检查认证
     */
    private function checkAuth(): void
    {
        if (!isset($_SESSION['admin_id'])) {
            // AJAX请求返回JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => '未登录']);
                exit;
            }
            
            // 普通请求重定向
            $this->redirect('/admin/login');
            exit;
        }
        
        $this->adminUser = [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username']
        ];
    }
    
    /**
     * 后台渲染方法
     */
    protected function render(string $view, array $data = [], string $layout = 'admin'): void
    {
        $data['admin'] = $this->adminUser;
        $data['baseUrl'] = $this->baseUrl;
        $data['asset'] = fn($path) => $this->view->asset($path);
        $data['url'] = fn($path = '') => $this->view->url($path);
        
        // 获取闪存消息
        $data['success'] = $this->consumeFlash('admin_success');
        $data['error'] = $this->consumeFlash('admin_error');
        
        parent::render('admin/' . $view, $data, 'admin/layouts/admin');
    }
    
    /**
     * 设置成功消息
     */
    protected function setSuccess(string $message): void
    {
        $this->flash('admin_success', $message);
    }
    
    /**
     * 设置错误消息
     */
    protected function setError(string $message): void
    {
        $this->flash('admin_error', $message);
    }
}