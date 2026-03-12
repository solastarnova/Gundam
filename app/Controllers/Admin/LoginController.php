<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminModel;

class LoginController extends Controller
{
    private AdminModel $adminModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminModel();
    }
    
    /**
     * 显示登录页面
     */
    public function index()
    {
        // 如果已登录，直接跳转后台
        if (isset($_SESSION['admin_id'])) {
            $this->redirect('/admin/dashboard');
            return;
        }
        
        $error = $this->consumeFlash('admin_login_error');
        
        // 不使用主布局，直接渲染登录页
        $this->render('admin/login', [
            'title' => '后台登录',
            'error' => $error,
            'layout' => false
        ]);
    }
    
    /**
     * 处理登录
     */
    public function login()
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // 验证输入
        if (empty($username) || empty($password)) {
            $this->flash('admin_login_error', '用户名和密码不能为空');
            $this->redirect('/admin/login');
            return;
        }
        
        // 查找管理员
        $admin = $this->adminModel->findByUsername($username);
        
        if (!$admin || !$this->adminModel->verifyPassword($password, $admin['password'])) {
            $this->flash('admin_login_error', '用户名或密码错误');
            $this->redirect('/admin/login');
            return;
        }
        
        // 登录成功
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        
        // 更新最后登录时间
        $this->adminModel->updateLastLogin($admin['id']);
        
        // 跳转到后台首页
        $this->redirect('/admin/dashboard');
    }
    
    /**
     * 登出
     */
    public function logout()
    {
        // 清除所有会话数据
        $_SESSION = [];
        
        // 销毁会话cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // 销毁会话
        session_destroy();
        
        $this->redirect('/admin/login');
    }
}