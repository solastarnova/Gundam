<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;

class AuthController extends Controller
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    public function showLogin(): void
    {
        if (isset($_SESSION['email'])) {
            $this->redirect($this->view->url(''));
            return;
        }
        $redirect = isset($_GET['redirect']) ? (string) $_GET['redirect'] : '';
        $errors = $this->consumeFlash('login_errors', []);
        $old = $this->consumeFlash('login_old', []);
        $this->render('auth/login', [
            'title' => '登入 - ' . $this->getSiteName(),
            'redirect' => $redirect,
            'errors' => $errors,
            'old' => $old,
            'head_extra_css' => [],
        ]);
    }

    public function login(): void
    {
        $email = trim($_POST['e-mail'] ?? '');
        $password = $_POST['password'] ?? '';
        $redirect = trim($_POST['redirect'] ?? $_GET['redirect'] ?? '');

        if (isset($_SESSION['email'])) {
            $this->redirect($redirect !== '' ? $redirect : $this->view->url(''));
            return;
        }

        $errors = [];
        $oldInput = ['email' => $email];
        $emailError = $this->validateEmail($email);
        if ($emailError) {
            $errors['email'] = $emailError;
        }
        if ($password === '') {
            $errors['password'] = '請輸入密碼';
        }

        if (!empty($errors)) {
            $this->flash('login_errors', $errors);
            $this->flash('login_old', $oldInput);
            $this->redirect($this->view->url('login') . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : ''));
            return;
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user || !$this->userModel->verifyPassword($password, $user['password'] ?? '')) {
            $this->flash('login_errors', ['general' => '電郵或密碼不正確']);
            $this->flash('login_old', $oldInput);
            $this->redirect($this->view->url('login') . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : ''));
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $target = $redirect !== '' ? $redirect : $this->view->url('');
        $this->redirect($target);
    }

    public function showRegister(): void
    {
        if (isset($_SESSION['email'])) {
            $this->redirect($this->view->url(''));
            return;
        }
        $errors = $this->consumeFlash('register_errors', []);
        $old = $this->consumeFlash('register_old', []);
        $this->render('auth/register', [
            'title' => '註冊 - ' . $this->getSiteName(),
            'errors' => $errors,
            'old' => $old,
            'head_extra_css' => [],
        ]);
    }

    public function register(): void
    {
        if (isset($_SESSION['email'])) {
            $this->redirect($this->view->url(''));
            return;
        }
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['e-mail'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors = [];
        $oldInput = ['name' => $name, 'email' => $email];
        if ($email === '') {
            $errors['email'] = '請輸入電郵';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '請輸入有效的電郵';
        }
        $minLen = (int) ($this->getConfig()['min_password_length'] ?? 8);
        if ($password === '') {
            $errors['password'] = '請輸入密碼';
        } elseif (strlen($password) < $minLen) {
            $errors['password'] = "密碼至少 {$minLen} 個字元";
        }

        $this->handleValidationErrors($errors, $oldInput, 'register_errors', 'register_old', $this->view->url('register'));

        if ($this->userModel->emailExists($email)) {
            $this->flash('register_errors', ['email' => '該電郵已被註冊']);
            $this->flash('register_old', $oldInput);
            $this->redirect($this->view->url('register'));
            return;
        }

        $userId = $this->userModel->create($name, $email, $password);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['email'] = $email;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $this->redirect($this->view->url(''));
    }

    public function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['user_name']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $this->redirect($this->view->url(''));
    }

    public function showForgotPassword(): void
    {
        if (isset($_SESSION['email'])) {
            $this->redirect($this->view->url(''));
            return;
        }
        $error = $this->consumeFlash('resetpw_error', '');
        $this->render('auth/forgot_password', [
            'title' => '忘記密碼 - ' . $this->getSiteName(),
            'error' => $error,
            'head_extra_css' => [],
        ]);
    }

    public function forgotPasswordSubmit(): void
    {
        if (isset($_SESSION['email'])) {
            $this->redirect($this->view->url(''));
            return;
        }
        $email = trim($_POST['recipientEmail'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($email === '' || $password === '') {
            $this->flash('resetpw_error', '請填寫電郵與新密碼');
            $this->redirect($this->view->url('resetpw'));
            return;
        }
        $minLen = (int) ($this->getConfig()['min_password_length'] ?? 8);
        if (strlen($password) < $minLen) {
            $this->flash('resetpw_error', "新密碼至少 {$minLen} 個字元");
            $this->redirect($this->view->url('resetpw'));
            return;
        }
        $captcha = trim($_POST['captcha'] ?? '');
        if ($captcha === '' || !isset($_SESSION['yzm']) || strcasecmp($captcha, (string) $_SESSION['yzm']) !== 0) {
            $this->flash('resetpw_error', '驗證碼錯誤');
            $this->redirect($this->view->url('resetpw'));
            return;
        }
        unset($_SESSION['yzm']);
        $this->userModel->updatePasswordByEmail($email, $password);
        $this->flash('resetpw_error', '');
        $this->redirect($this->view->url('login'));
    }
}
