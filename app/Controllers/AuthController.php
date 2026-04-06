<?php

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Models\UserModel;
use App\Services\FirebaseAdminAuth;
use App\Services\FirebaseWebConfig;
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
            $this->redirect($this->getRedirectAfterAuth($_GET['redirect'] ?? null));
            return;
        }

        $errors = $this->consumeFlash('login_errors', []);
        $old = $this->consumeFlash('login_old', []);
        $message = $this->consumeFlash('login_message', null);
        $redirect = $this->getRedirectTarget();
        $fb = $this->firebaseAuthBundle('signin');
        $fb['firebase_redirect_param'] = $redirect;
        $this->render('auth/login', array_merge([
            'title' => $this->titleWithSite('auth_login'),
            'redirect' => $redirect,
            'errors' => $errors,
            'old' => $old,
            'message' => $message,
            'head_extra_css' => [],
        ], $fb));
    }

    public function login(): void
    {
        $email = trim($_POST['e-mail'] ?? '');
        $password = $_POST['password'] ?? '';

        if (isset($_SESSION['email'])) {
            $this->redirect($this->getRedirectAfterAuth($_POST['redirect'] ?? null));
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

        $this->redirect($this->getRedirectAfterAuth($_POST['redirect'] ?? null));
    }

    public function showRegister(): void
    {
        if (isset($_SESSION['email'])) {
            $this->redirect($this->getRedirectAfterAuth($_GET['redirect'] ?? null));
            return;
        }
        $errors = $this->consumeFlash('register_errors', []);
        $old = $this->consumeFlash('register_old', []);
        $status = $this->consumeFlash('register_status', null);

        $redirect = $this->getRedirectTarget();
        $fb = $this->firebaseAuthBundle('signup');
        $fb['firebase_redirect_param'] = (string) ($old['redirect'] ?? $redirect);
        $this->render('auth/register', array_merge([
            'title' => $this->titleWithSite('auth_register'),
            'errors' => $errors,
            'old' => $old,
            'status' => $status,
            'redirect' => $redirect,
            'head_extra_css' => [],
        ], $fb));
    }

    public function sendRegistrationCode(): void
    {
        if (isset($_SESSION['email'])) {
            $this->redirect($this->getRedirectAfterAuth(null));
            return;
        }
        if ($this->mail === null) {
            $this->flash('register_errors', ['general' => Config::get('messages.auth.mail_not_configured')]);
            $this->redirect($this->view->url('register'));
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['e-mail'] ?? '');
        $redirect = trim((string) ($_POST['redirect'] ?? ''));

        $errors = [];
        $oldInput = ['name' => $name, 'email' => $email, 'redirect' => $redirect];
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
            $this->redirect($this->getRedirectAfterAuth($_POST['redirect'] ?? null));
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['e-mail'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $code = trim($_POST['verification_code'] ?? '');
        $redirect = trim((string) ($_POST['redirect'] ?? ''));

        $errors = [];
        $oldInput = ['name' => $name, 'email' => $email, 'redirect' => $redirect];

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

        try {
            $userId = (int) $this->userModel->create($name, $email, $password);
        } catch (\Throwable $e) {
            $userId = 0;
        }

        if ($userId <= 0) {
            $this->flash('register_errors', ['general' => Config::get('messages.auth.register_failed')]);
            $this->flash('register_old', $oldInput);
            $this->redirect($this->view->url('register'));
            return;
        }

        unset($_SESSION['register_verification_code'], $_SESSION['register_verification_email'], $_SESSION['register_verification_expires']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['email'] = $email;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;

        $this->flash('login_message', Config::get('messages.auth.register_success'));
        $this->redirect($this->getRedirectAfterAuth($_POST['redirect'] ?? null));
    }

    public function firebaseAuth(): void
    {
        $intent = trim((string) ($_POST['intent'] ?? ''));
        $idToken = trim((string) ($_POST['id_token'] ?? ''));
        $redirectPost = trim((string) ($_POST['redirect'] ?? ''));
        $loginUrl = $this->view->url('login');
        $registerUrl = $this->view->url('register');

        if (isset($_SESSION['email'])) {
            $this->redirect($this->getRedirectAfterAuth($redirectPost !== '' ? $redirectPost : null));

            return;
        }

        if (!FirebaseAdminAuth::isConfigured()) {
            $this->flashFirebaseAuthError($intent, (string) Config::get('messages.auth.firebase_not_configured'));
            $this->redirect($intent === 'signup' ? $registerUrl : $loginUrl);

            return;
        }

        if (!in_array($intent, ['signin', 'signup'], true)) {
            $this->redirect($loginUrl);

            return;
        }

        $profile = FirebaseAdminAuth::verifyIdToken($idToken);
        if ($profile === null) {
            $this->flashFirebaseAuthError($intent, (string) Config::get('messages.auth.firebase_invalid'));
            $this->redirect($intent === 'signup' ? $registerUrl : $loginUrl);

            return;
        }

        if (!$profile->email_verified) {
            $this->flashFirebaseAuthError($intent, (string) Config::get('messages.auth.firebase_email_unverified'));
            $this->redirect($intent === 'signup' ? $registerUrl : $loginUrl);

            return;
        }

        $uid = $profile->uid;
        $email = $profile->email;
        $name = $profile->name !== '' ? $profile->name : (strstr($email, '@', true) ?: $email);

        $byUid = $this->userModel->findByFirebaseUid($uid);
        if ($byUid !== null) {
            if (!$this->firebaseEmailMatchesRow($email, $byUid)) {
                $this->flashFirebaseAuthError($intent, (string) Config::get('messages.auth.firebase_invalid'));
                $this->redirect($intent === 'signup' ? $registerUrl : $loginUrl);

                return;
            }
            if (($byUid['status'] ?? 'active') === 'disabled') {
                $this->flash('login_errors', ['general' => Config::get('messages.auth.account_disabled')]);
                $this->redirect($loginUrl);

                return;
            }
            $this->startUserSessionFromUser($byUid);
            $this->redirect($this->getRedirectAfterAuth($redirectPost !== '' ? $redirectPost : null));

            return;
        }

        $byEmail = $this->userModel->findByEmail($email);

        if ($byEmail !== null) {
            if ($intent === 'signup') {
                $this->flash('register_errors', ['general' => Config::get('messages.auth.firebase_already_registered')]);
                $this->redirect($registerUrl);

                return;
            }

            $storedUid = trim((string) ($byEmail['firebase_uid'] ?? ''));
            if ($storedUid !== '' && $storedUid !== $uid) {
                $this->flash('login_errors', ['general' => Config::get('messages.auth.firebase_account_mismatch')]);
                $this->redirect($loginUrl);

                return;
            }

            if ($storedUid === '') {
                if (!$this->userModel->linkFirebaseUid((int) $byEmail['id'], $uid)) {
                    $this->flash('login_errors', ['general' => Config::get('messages.auth.firebase_invalid')]);
                    $this->redirect($loginUrl);

                    return;
                }
            }

            if (($byEmail['status'] ?? 'active') === 'disabled') {
                $this->flash('login_errors', ['general' => Config::get('messages.auth.account_disabled')]);
                $this->redirect($loginUrl);

                return;
            }

            $byEmail = $this->userModel->findByEmail($email) ?? $byEmail;
            $this->startUserSessionFromUser($byEmail);
            $this->redirect($this->getRedirectAfterAuth($redirectPost !== '' ? $redirectPost : null));

            return;
        }

        if ($intent === 'signin') {
            $this->flash('login_errors', ['general' => Config::get('messages.auth.firebase_no_local_account')]);
            $this->redirect($loginUrl);

            return;
        }

        try {
            $userId = (int) $this->userModel->createWithFirebaseUid($name, $email, $uid);
        } catch (\Throwable) {
            $userId = 0;
        }

        if ($userId <= 0) {
            $this->flash('register_errors', ['general' => Config::get('messages.auth.register_failed')]);
            $this->redirect($registerUrl);

            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['email'] = $email;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;

        $this->flash('login_message', Config::get('messages.auth.register_success'));
        $this->redirect($this->getRedirectAfterAuth($redirectPost !== '' ? $redirectPost : null));
    }

    public function logout(): void
    {
        $redirect = $this->sanitizeRedirect($_GET['redirect'] ?? null)
            ?? $this->sanitizeRedirect($_SERVER['HTTP_REFERER'] ?? null)
            ?? null;

        unset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['user_name']);
        unset($_SESSION['auth_redirect']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $target = ($redirect !== null && $redirect !== '') ? $redirect : $this->view->url('');
        if ($this->isAuthPath($target)) {
            $target = $this->view->url('');
        }

        $this->redirect($target);
    }

    /**
     * @return array<string, mixed>
     */
    private function firebaseAuthBundle(string $intent): array
    {
        $web = $this->firebaseWebConfig();
        $enabled = $web !== null && FirebaseAdminAuth::isConfigured();
        $out = [
            'firebase_auth_enabled' => $enabled,
            'firebase_web_config' => $web,
            'firebase_auth_intent' => $intent,
            'firebase_redirect_param' => '',
            'foot_script_srcs' => [],
            'foot_extra_js' => [],
            'firebase_enable_facebook' => false,
        ];
        if (!$enabled) {
            return $out;
        }
        $out['foot_script_srcs'] = [
            'https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js',
            'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js',
        ];
        $out['foot_extra_js'] = ['js/auth-firebase.js'];
        $out['firebase_enable_facebook'] = filter_var(
            getenv('FIREBASE_ENABLE_FACEBOOK') ?: '',
            FILTER_VALIDATE_BOOLEAN
        );

        return $out;
    }

    /** @return array<string, string>|null */
    private function firebaseWebConfig(): ?array
    {
        return FirebaseWebConfig::forJavaScript();
    }

    private function flashFirebaseAuthError(string $intent, string $message): void
    {
        if ($intent === 'signup') {
            $this->flash('register_errors', ['general' => $message]);
        } else {
            $this->flash('login_errors', ['general' => $message]);
        }
    }

    private function firebaseEmailMatchesRow(string $tokenEmail, array $row): bool
    {
        return strcasecmp($tokenEmail, (string) ($row['email'] ?? '')) === 0;
    }

    /** @param array{id: int|string, email: string, name?: string} $user */
    private function startUserSessionFromUser(array $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'] ?? '';
    }

    private function getRedirectTarget(): string
    {
        $candidate = $this->sanitizeRedirect($_GET['redirect'] ?? null);

        if (!$candidate) {
            $candidate = $_SESSION['auth_redirect'] ?? null;
        }

        if (!$candidate) {
            $candidate = $this->sanitizeRedirect($_SERVER['HTTP_REFERER'] ?? null);
        }

        $candidate = $candidate ?: $this->view->url('');

        if ($this->isAuthPath($candidate)) {
            $candidate = $this->view->url('');
        }

        $_SESSION['auth_redirect'] = $candidate;

        return $candidate;
    }

    private function getRedirectAfterAuth(?string $requested): string
    {
        $redirect = $this->sanitizeRedirect($requested) ?? ($_SESSION['auth_redirect'] ?? null) ?? null;

        if ($redirect === null || $redirect === '') {
            $redirect = $this->view->url('');
        }

        if ($this->isAuthPath($redirect)) {
            $redirect = $this->view->url('');
        }

        unset($_SESSION['auth_redirect']);

        return $redirect !== '' ? $redirect : $this->view->url('');
    }

    private function isAuthPath(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? $url;

        if ($this->baseUrl !== '' && strpos($path, $this->baseUrl) === 0) {
            $path = substr($path, strlen($this->baseUrl)) ?: '/';
        }

        if ($path === '' || $path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }

        $norm = rtrim($path, '/') ?: '/';
        if (in_array($norm, ['/login', '/register'], true)) {
            return true;
        }

        return preg_match('#^/forgot#', $norm) === 1;
    }
}
