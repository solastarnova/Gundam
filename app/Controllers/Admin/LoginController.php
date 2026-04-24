<?php

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\Controller;
use App\Core\I18n;
use App\Models\AdminModel;

/** 處理後台登入頁與管理員驗證流程。 */
class LoginController extends Controller
{
    private AdminModel $adminModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminModel();
    }

    public function index()
    {
        if (isset($_SESSION['admin_id'])) {
            $this->redirect('/admin/dashboard');
            return;
        }
        
        $error = $this->consumeFlash('admin_login_error');
        $csrfToken = bin2hex(random_bytes(32));
        $_SESSION['admin_csrf_token'] = $csrfToken;
        $locale = Config::locale();
        $data = [
            'title' => Config::get('messages.titles.admin_login'),
            'error' => $error,
            'csrf_token' => $csrfToken,
            'locale' => $locale,
            'html_lang' => I18n::toBcp47($locale),
            'asset' => fn (string $path) => $this->view->asset($path),
            'url' => fn (string $path = '') => $this->view->url($path),
        ];
        echo $this->view->render('admin/login', $data);
    }

    public function login()
    {
        $token = $_POST['csrf_token'] ?? '';
        if (
            $token === '' ||
            !isset($_SESSION['admin_csrf_token']) ||
            !hash_equals($_SESSION['admin_csrf_token'], $token)
        ) {
            $this->flash('admin_login_error', Config::get('messages.admin_login.csrf_invalid'));
            $this->redirect('/admin/login');
            return;
        }
        unset($_SESSION['admin_csrf_token']);

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (empty($username) || empty($password)) {
            $this->flash('admin_login_error', Config::get('messages.admin_login.username_password_required'));
            $this->redirect('/admin/login');
            return;
        }
        $admin = $this->adminModel->findByUsername($username);
        
        if (!$admin || !$this->adminModel->verifyPassword($password, $admin['password'])) {
            $this->flash('admin_login_error', Config::get('messages.admin_login.username_password_wrong'));
            $this->redirect('/admin/login');
            return;
        }
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $this->adminModel->updateLastLogin($admin['id']);
        $this->redirect('/admin/dashboard');
    }

    public function logout()
    {
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
        
        $this->redirect('/admin/login');
    }
}