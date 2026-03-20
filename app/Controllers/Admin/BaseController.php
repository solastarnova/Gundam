<?php

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\Controller;

class BaseController extends Controller
{
    protected array $adminUser;
    
    public function __construct()
    {
        parent::__construct();

        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($currentPath, '/admin/login') === false) {
            $this->checkAuth();
        }
    }

    private function checkAuth(): void
    {
        if (!isset($_SESSION['admin_id'])) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => Config::get('messages.admin.not_logged_in')]);
                exit;
            }
            $this->redirect('/admin/login');
            exit;
        }
        
        $this->adminUser = [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username']
        ];
    }

    protected function render(string $view, array $data = [], string $layout = 'admin'): void
    {
        $data['admin'] = $this->adminUser;
        $data['baseUrl'] = $this->baseUrl;
        $data['asset'] = fn($path) => $this->view->asset($path);
        $data['url'] = fn($path = '') => $this->view->url($path);
        $data['success'] = $this->consumeFlash('admin_success');
        $data['error'] = $this->consumeFlash('admin_error');
        
        parent::render('admin/' . $view, $data, 'admin/layouts/admin');
    }

    protected function setSuccess(string $message): void
    {
        $this->flash('admin_success', $message);
    }

    protected function setError(string $message): void
    {
        $this->flash('admin_error', $message);
    }
}