<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;
use App\Services\MailService;

class ForgotPasswordController extends Controller
{
    private UserModel $userModel;
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

    public function index(): void
    {
        if (isset($_SESSION['email'])) {
            $this->redirect($this->view->url(''));
            return;
        }

        $errors = $this->consumeFlash('resetpw_errors', []);
        $old = $this->consumeFlash('resetpw_old', []);
        $status = $this->consumeFlash('resetpw_status', null);
        $hasEmail = isset($_SESSION['forgot_password_email']);

        $this->render('auth/forgot_password', [
            'title' => '忘記密碼 - ' . $this->getSiteName(),
            'errors' => $errors,
            'old' => $old,
            'status' => $status,
            'has_email_sent' => $hasEmail,
            'head_extra_css' => [],
        ]);
    }

    public function send(): void
    {
        if (isset($_SESSION['email'])) {
            $this->redirect($this->view->url(''));
            return;
        }
        if ($this->mail === null) {
            $this->flash('resetpw_errors', ['general' => '郵件服務未配置，請聯絡管理員。']);
            $this->redirect($this->view->url('forgot'));
            return;
        }

        $email = trim($_POST['email'] ?? $_POST['recipientEmail'] ?? '');
        $errors = [];
        $oldInput = ['email' => $email];
        $targetUser = null;

        $emailError = $this->validateEmail($email);
        if ($emailError) {
            $errors['email'] = $emailError;
        } else {
            $targetUser = $this->userModel->findByEmail($email);
            if (!$targetUser) {
                $errors['email'] = '此電郵尚未註冊';
            }
        }

        $this->handleValidationErrors(
            $errors,
            $oldInput,
            'resetpw_errors',
            'resetpw_old',
            $this->view->url('forgot')
        );

        $code = $this->createVerificationCodeForUser((int) $targetUser['id']);
        if ($code === null) {
            $this->flash('resetpw_errors', ['general' => '無法生成驗證碼，請稍後再試。']);
            $this->flash('resetpw_old', $oldInput);
            $this->redirect($this->view->url('forgot'));
            return;
        }

        $mailSent = $this->mail->sendVerificationCode(
            $targetUser['email'],
            $targetUser['name'] ?? $targetUser['email'],
            $code
        );

        if (!$mailSent) {
            $this->flash('resetpw_errors', ['general' => '郵件發送失敗，請稍後再試。']);
            $this->flash('resetpw_old', $oldInput);
            $this->redirect($this->view->url('forgot'));
            return;
        }

        $this->clearForgotPasswordSession();
        $_SESSION['forgot_password_email'] = $email;

        $message = '我們已寄送驗證碼至您的電郵，請檢查並輸入驗證碼。';
        if ($this->isLocalEnvironment()) {
            $message .= ' [測試] 驗證碼：' . $code;
        }

        $this->flash('resetpw_status', $message);
        $this->flash('resetpw_old', ['email' => $email]);
        $this->redirect($this->view->url('forgot'));
    }

    public function verifyCode(): void
    {
        if (isset($_SESSION['email'])) {
            $this->redirect($this->view->url(''));
            return;
        }

        $email = $_SESSION['forgot_password_email'] ?? '';
        $code = trim($_POST['code'] ?? '');

        if ($email === '') {
            $this->flash('resetpw_errors', ['general' => '請先申請驗證碼。']);
            $this->redirect($this->view->url('forgot'));
            return;
        }

        $codeError = $this->validateVerificationCodeFormat($code);
        if ($codeError) {
            $this->flash('resetpw_errors', ['code' => $codeError]);
            $this->flash('resetpw_old', ['email' => $email]);
            $this->redirect($this->view->url('forgot'));
            return;
        }

        $user = $this->findUserByVerificationCode($email, $code);
        if (!$user) {
            $this->flash('resetpw_errors', ['code' => '驗證碼錯誤或已過期，請重新申請。']);
            $this->flash('resetpw_old', ['email' => $email]);
            $this->redirect($this->view->url('forgot'));
            return;
        }

        $_SESSION['forgot_password_verified'] = true;
        $_SESSION['forgot_password_user_id'] = (int) $user['id'];
        $this->redirect($this->view->url('forgot/reset'));
    }

    public function showResetForm(): void
    {
        if (isset($_SESSION['email'])) {
            $this->redirect($this->view->url(''));
            return;
        }
        if (!isset($_SESSION['forgot_password_verified']) || !$_SESSION['forgot_password_verified']) {
            $this->flash('resetpw_errors', ['general' => '請先驗證驗證碼。']);
            $this->redirect($this->view->url('forgot'));
            return;
        }

        $errors = $this->consumeFlash('reset_errors', []);
        $this->render('auth/reset_password', [
            'title' => '設定新密碼 - ' . $this->getSiteName(),
            'errors' => $errors,
            'head_extra_css' => [],
        ]);
    }

    public function resetPassword(): void
    {
        if (isset($_SESSION['email'])) {
            $this->redirect($this->view->url(''));
            return;
        }
        if (!isset($_SESSION['forgot_password_verified']) || !$_SESSION['forgot_password_verified']) {
            $this->flash('reset_errors', ['general' => '請先驗證驗證碼。']);
            $this->redirect($this->view->url('forgot'));
            return;
        }

        $userId = $_SESSION['forgot_password_user_id'] ?? null;
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if ($userId === null) {
            $this->clearForgotPasswordSession();
            $this->flash('reset_errors', ['general' => '驗證已過期，請重新申請。']);
            $this->redirect($this->view->url('forgot'));
            return;
        }

        $errors = [];
        $pwError = $this->validatePassword($password);
        if ($pwError !== null) {
            $errors['password'] = str_replace('密碼', '新密碼', $pwError);
        }
        $confirmError = $this->validatePasswordConfirmation($password, $passwordConfirm);
        if ($confirmError !== null) {
            $errors['password_confirm'] = $confirmError;
        }

        if (!empty($errors)) {
            $this->flash('reset_errors', $errors);
            $this->redirect($this->view->url('forgot/reset'));
            return;
        }

        $this->userModel->updatePassword((int) $userId, $password);
        $this->clearVerificationCodeForUser((int) $userId);
        $this->clearForgotPasswordSession();
        $this->flash('login_message', '密碼已重設，請使用新密碼登入。');
        $this->redirect($this->view->url('login'));
    }

    private function clearForgotPasswordSession(): void
    {
        unset(
            $_SESSION['forgot_password_email'],
            $_SESSION['forgot_password_code'],
            $_SESSION['forgot_password_expires'],
            $_SESSION['forgot_password_verified'],
            $_SESSION['forgot_password_user_id']
        );
    }

    private function createVerificationCodeForUser(int $userId): ?string
    {
        $codeLength = (int) \App\Core\Config::get('verification_code.length', 6);
        $ttlSeconds = (int) \App\Core\Config::get('verification_code.ttl_seconds', 600);

        $maxValue = (int) str_repeat('9', $codeLength);
        $code = str_pad((string) random_int(0, $maxValue), $codeLength, '0', STR_PAD_LEFT);
        $codeHash = hash('sha256', $code);

        $expiresAt = (new \DateTimeImmutable('now'))
            ->modify(sprintf('+%d seconds', $ttlSeconds))
            ->format('Y-m-d H:i:s');

        $stmt = $this->userModel->getPdo()->prepare(
            "UPDATE users SET password_reset_hash = ?, password_reset_expires_at = ? WHERE id = ?"
        );
        $stmt->execute([$codeHash, $expiresAt, $userId]);

        return $stmt->rowCount() > 0 ? $code : null;
    }

    private function findUserByVerificationCode(string $email, string $code): ?array
    {
        $codeHash = hash('sha256', $code);
        $stmt = $this->userModel->getPdo()->prepare(
            "SELECT id, name, email, password_reset_expires_at
             FROM users
             WHERE email = ?
               AND password_reset_hash = ?
               AND password_reset_expires_at IS NOT NULL
               AND password_reset_expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([$email, $codeHash]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function clearVerificationCodeForUser(int $userId): void
    {
        $stmt = $this->userModel->getPdo()->prepare(
            "UPDATE users SET password_reset_hash = NULL, password_reset_expires_at = NULL WHERE id = ?"
        );
        $stmt->execute([$userId]);
    }
}

