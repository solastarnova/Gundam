<?php

namespace App\Controllers;

use App\Core\Config;
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
            'title' => $this->titleWithSite('forgot_password'),
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
            $this->flash('resetpw_errors', ['general' => Config::get('messages.auth.mail_not_configured')]);
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
                $errors['email'] = Config::get('messages.password_reset.email_not_registered');
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
            $this->flash('resetpw_errors', ['general' => Config::get('messages.password_reset.code_generate_failed')]);
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
            $this->flash('resetpw_errors', ['general' => Config::get('messages.auth.mail_send_failed')]);
            $this->flash('resetpw_old', $oldInput);
            $this->redirect($this->view->url('forgot'));
            return;
        }

        $this->clearForgotPasswordSession();
        $_SESSION['forgot_password_email'] = $email;

        $message = Config::get('messages.password_reset.code_sent_body');
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
            $this->flash('resetpw_errors', ['general' => Config::get('messages.password_reset.need_request_code_first')]);
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
            $this->flash('resetpw_errors', ['code' => Config::get('messages.password_reset.verify_code_wrong_expired')]);
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
            $this->flash('resetpw_errors', ['general' => Config::get('messages.password_reset.verify_code_first')]);
            $this->redirect($this->view->url('forgot'));
            return;
        }

        $errors = $this->consumeFlash('reset_errors', []);
        $this->render('auth/reset_password', [
            'title' => $this->titleWithSite('reset_password'),
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
            $this->flash('reset_errors', ['general' => Config::get('messages.password_reset.verify_code_first')]);
            $this->redirect($this->view->url('forgot'));
            return;
        }

        $userId = $_SESSION['forgot_password_user_id'] ?? null;
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if ($userId === null) {
            $this->clearForgotPasswordSession();
            $this->flash('reset_errors', ['general' => Config::get('messages.password_reset.session_expired')]);
            $this->redirect($this->view->url('forgot'));
            return;
        }

        $errors = [];
        $minLen = (int) Config::get('min_password_length', 8);
        if ($password === '') {
            $errors['password'] = Config::get('messages.account.password_new_required');
        } elseif (strlen($password) < $minLen) {
            $errors['password'] = sprintf(Config::get('messages.account.password_new_min'), $minLen);
        }
        if ($passwordConfirm === '') {
            $errors['password_confirm'] = Config::get('messages.account.password_new_confirm_required');
        } elseif ($password !== '' && $password !== $passwordConfirm) {
            $errors['password_confirm'] = Config::get('messages.auth.password_confirm_mismatch');
        }

        if (!empty($errors)) {
            $this->flash('reset_errors', $errors);
            $this->redirect($this->view->url('forgot/reset'));
            return;
        }

        $this->userModel->updatePassword((int) $userId, $password);
        $this->clearVerificationCodeForUser((int) $userId);
        $this->clearForgotPasswordSession();
        $this->flash('login_message', Config::get('messages.password_reset.success_login'));
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

