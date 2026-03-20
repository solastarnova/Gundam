<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\UserModel;
use App\Services\MailService;

class AuthController extends Controller
{
    private UserModel $userModel;

    /** @var MailService|null */
    private ?MailService $mail = null;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        try {
            $this->mail = new MailService();
        } catch (\Throwable $e) {
            $this->mail = null;
        }
    }

    public function showLogin(): void
    {
        if (isset($_SESSION['email'])) {
            $this->redirect($this->view->url(''));
            return;
        }

        $redirect = isset($_GET['redirect']) ? (string) $_GET['redirect'] : '';
        $redirect = $this->sanitizeRedirect($redirect) ?? '';
        $path = parse_url($redirect, PHP_URL_PATH) ?? $redirect;
        if ($path !== '' && (preg_match('#/login/?$#', (string) $path) || preg_match('#/register/?$#', (string) $path))) {
            $redirect = '';
        }

        $errors = $this->consumeFlash('login_errors', []);
        $old = $this->consumeFlash('login_old', []);
        $message = $this->consumeFlash('login_message', null);
        $this->render('auth/login', [
            'title' => '登入 - ' . $this->getSiteName(),
            'redirect' => $redirect,
            'errors' => $errors,
            'old' => $old,
            'message' => $message,
            'head_extra_css' => [],
        ]);
    }

    public function login(): void
    {
        $email = trim($_POST['e-mail'] ?? '');
        $password = $_POST['password'] ?? '';
        $redirect = trim((string) ($_POST['redirect'] ?? $_GET['redirect'] ?? ''));

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
            $errors['password'] = Config::get('messages.auth.password_required');
        }

        if (!empty($errors)) {
            $this->flash('login_errors', $errors);
            $this->flash('login_old', $oldInput);
            $this->redirect($this->view->url('login'));
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user || !$this->userModel->verifyPassword($password, $user['password'] ?? '')) {
            $this->flash('login_errors', ['general' => Config::get('messages.auth.email_password_wrong')]);
            $this->flash('login_old', $oldInput);
            $this->redirect($this->view->url('login'));
        }
        if (($user['status'] ?? 'active') === 'disabled') {
            $this->flash('login_errors', ['general' => Config::get('messages.auth.account_disabled')]);
            $this->flash('login_old', $oldInput);
            $this->redirect($this->view->url('login'));
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];

        $redirect = $redirect !== '' ? (string) ($this->sanitizeRedirect($redirect) ?? '') : '';
        $path = parse_url($redirect, PHP_URL_PATH) ?? $redirect;
        if ($path !== '' && (preg_match('#/login/?$#', (string) $path) || preg_match('#/register/?$#', (string) $path))) {
            $redirect = '';
        }
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
        $status = $this->consumeFlash('register_status', null);
        $this->render('auth/register', [
            'title' => '註冊 - ' . $this->getSiteName(),
            'errors' => $errors,
            'old' => $old,
            'status' => $status,
            'head_extra_css' => [],
        ]);
    }

    public function sendRegistrationCode(): void
    {
        if (isset($_SESSION['email'])) {
            $this->redirect($this->view->url(''));
            return;
        }
        if ($this->mail === null) {
            $this->flash('register_errors', ['general' => Config::get('messages.auth.mail_not_configured')]);
            $this->redirect($this->view->url('register'));
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['e-mail'] ?? '');

        $errors = [];
        $oldInput = ['name' => $name, 'email' => $email];
        if ($name === '') {
            $errors['name'] = Config::get('messages.auth.name_required');
        }
        $emailError = $this->validateEmail($email);
        if ($emailError) {
            $errors['email'] = $emailError;
        } elseif ($this->userModel->emailExists($email)) {
            $errors['email'] = Config::get('messages.auth.email_registered');
        }

        $this->handleValidationErrors($errors, $oldInput, 'register_errors', 'register_old', $this->view->url('register'));

        $code = $this->generateVerificationCode();
        $mailSent = $this->mail->sendRegistrationCode($email, $name ?: $email, $code);

        if (!$mailSent) {
            $this->flash('register_errors', ['general' => Config::get('messages.auth.mail_send_failed')]);
            $this->flash('register_old', $oldInput);
            $this->redirect($this->view->url('register'));
            return;
        }

        $ttlSeconds = (int) Config::get('verification_code.ttl_seconds', 600);
        $_SESSION['register_verification_code'] = hash('sha256', $code);
        $_SESSION['register_verification_email'] = $email;
        $_SESSION['register_verification_expires'] = time() + $ttlSeconds;

        $message = Config::get('messages.auth.register_sent');
        if ($this->isLocalEnvironment()) {
            $message .= ' [測試] 驗證碼：' . $code;
        }
        $this->flash('register_status', $message);
        $this->flash('register_old', $oldInput);
        $this->redirect($this->view->url('register'));
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
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $code = trim($_POST['verification_code'] ?? '');

        $errors = [];
        $oldInput = ['name' => $name, 'email' => $email];

        if ($name === '') {
            $errors['name'] = Config::get('messages.auth.name_required');
        }
        $emailError = $this->validateEmail($email);
        if ($emailError) {
            $errors['email'] = $emailError;
        } elseif ($this->userModel->emailExists($email)) {
            $errors['email'] = Config::get('messages.auth.email_registered');
        }

        $minLen = (int) Config::get('min_password_length', 8);
        if ($password === '') {
            $errors['password'] = Config::get('messages.auth.password_required');
        } elseif (strlen($password) < $minLen) {
            $errors['password'] = sprintf(Config::get('messages.auth.password_min'), $minLen);
        }
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = Config::get('messages.auth.password_confirm_mismatch');
        }

        $codeError = $this->validateVerificationCodeFormat($code);
        if ($codeError) {
            $errors['verification_code'] = $codeError;
        } else {
            $storedCodeHash = $_SESSION['register_verification_code'] ?? null;
            $storedEmail = $_SESSION['register_verification_email'] ?? null;
            $expires = $_SESSION['register_verification_expires'] ?? 0;
            if (!$storedCodeHash || $storedEmail !== $email || time() > $expires) {
                $errors['verification_code'] = Config::get('messages.auth.verification_expired');
            } elseif (hash('sha256', $code) !== $storedCodeHash) {
                $errors['verification_code'] = Config::get('messages.auth.verification_wrong');
            }
        }

        $this->handleValidationErrors($errors, $oldInput, 'register_errors', 'register_old', $this->view->url('register'));

        $userId = $this->userModel->create($name, $email, $password);
        unset($_SESSION['register_verification_code'], $_SESSION['register_verification_email'], $_SESSION['register_verification_expires']);

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
}
