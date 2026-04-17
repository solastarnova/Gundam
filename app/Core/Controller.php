<?php

namespace App\Core;

use App\Core\Config;
use App\Core\I18n;
use App\Services\FirebaseWebConfig;
use App\Services\MoneyFormatter;

/**
 * Base HTTP controller: layout render, JSON helpers, auth, validation, flash.
 */
class Controller
{
    protected View $view;
    protected string $baseUrl = '';

    public function __construct()
    {
        $this->view = new View();
        $base = rtrim(Config::get('base_url', ''), '/');
        if ($base === '' && isset($_SERVER['SCRIPT_NAME'])) {
            $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            if ($base === '/' || $base === '\\') {
                $base = '';
            }
        }
        $this->baseUrl = $base;
    }

    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $currency = Config::get('currency', []);
        if (!is_array($currency)) {
            $currency = [];
        }
        $data['asset'] = fn (string $path) => $this->view->asset($path);
        $data['url'] = fn (string $path = '') => $this->view->url($path);
        $data['currency'] = $currency;
        $data['money'] = fn (float $amount): string => MoneyFormatter::format($amount);
        $data['locale'] = Config::locale();
        $data['html_lang'] = 'en';

        if (isset($_SESSION['user_id'])) {
            $webCfg = FirebaseWebConfig::forJavaScript();
            if ($webCfg !== null && empty($data['firebase_auth_enabled'])) {
                $data['firebase_web_config'] = $webCfg;
                $firebaseScripts = [
                    'https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js',
                    'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js',
                ];
                $data['foot_script_srcs'] = array_merge(
                    $firebaseScripts,
                    (array) ($data['foot_script_srcs'] ?? [])
                );
            }
            $extra = (array) ($data['foot_extra_js'] ?? []);
            if (!in_array('js/auth-logout.js', $extra, true)) {
                $extra[] = 'js/auth-logout.js';
            }
            $data['foot_extra_js'] = array_values(array_unique($extra));
        }

        $this->view->renderWithLayout($view, $data, $layout);
    }

    protected function setupJsonApi(): void
    {
        ini_set('display_errors', '0');
        header('Content-Type: application/json; charset=utf-8');
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $url, int $statusCode = 302): void
    {
        if (!preg_match('#^https?://#i', $url)) {
            $baseUrl = $this->baseUrl;

            if ($baseUrl !== '') {
                if ($url === '' || $url === '/') {
                    $url = $baseUrl . '/';
                } elseif (isset($url[0]) && $url[0] === '/') {
                    if (strpos($url, $baseUrl) !== 0) {
                        $url = $baseUrl . $url;
                    }
                } else {
                    $url = rtrim($baseUrl, '/') . '/' . $url;
                }
            }
        }

        header("Location: $url", true, $statusCode);
        exit;
    }

    protected function flash(string $key, $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    protected function consumeFlash(string $key, $default = null)
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    protected function requireUser(): array
    {
        if (!isset($_SESSION['user_id'])) {
            $candidate = $this->sanitizeRedirect($_SERVER['REQUEST_URI'] ?? null) ?? $this->view->url('login');
            $this->redirect($this->view->url('login') . '?redirect=' . urlencode($candidate));
        }
        return [
            'id' => (int) $_SESSION['user_id'],
            'email' => $_SESSION['email'] ?? '',
            'name' => $_SESSION['user_name'] ?? '',
        ];
    }

    /**
     * Require logged-in user for JSON endpoints; sends 401 JSON when missing.
     *
     * @return bool True if session has user_id
     */
    protected function requireAuthForApi(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            $msg = (string) Config::get('messages.common.not_logged_in');
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $msg, 'message' => $msg], JSON_UNESCAPED_UNICODE);
            return false;
        }
        return true;
    }

    protected function sanitizeRedirect(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $url)) {
            $parsed = parse_url($url);
            if (!$parsed) {
                return null;
            }
            $path = $parsed['path'] ?? '/';
            $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
            $url = $path . $query;
        } else {
            $url = trim(parse_url($url, PHP_URL_PATH) ?: $url);
            if ($url === '') {
                return null;
            }
        }

        if (isset($url[0]) && $url[0] !== '/') {
            $url = '/' . $url;
        }

        if ($this->baseUrl !== '' && strpos($url, $this->baseUrl) === 0) {
            $url = substr($url, strlen($this->baseUrl)) ?: '/';
            if (isset($url[0]) && $url[0] !== '/') {
                $url = '/' . $url;
            }
        }

        return $url;
    }

    protected function getConfig(): array
    {
        return Config::all();
    }

    protected function getSiteName(): string
    {
        return (string) Config::get('site_name', '高達模型商城');
    }

    protected function titleWithSite(string $key): string
    {
        return sprintf((string) Config::get('messages.titles.' . $key), $this->getSiteName());
    }

    protected function titleFormat(string $key, ...$params): string
    {
        return sprintf((string) Config::get('messages.titles.' . $key), ...$params);
    }

    /**
     * Shared map-related client URLs injected into pages.
     *
     * @return array{
     *   leaflet_css:string,
     *   leaflet_js:string,
     *   nominatim_reverse_url:string,
     *   maptiler_reverse_geocode_url:string,
     *   maptiler_sdk_css:string,
     *   maptiler_sdk_js:string,
     *   maptiler_leaflet_js:string,
     *   maptiler_geocoding_control_js:string,
     *   maptiler_api_key:string
     * }
     */
    protected function getMapClientConfig(): array
    {
        $map = Config::get('map_client', []);
        if (!is_array($map)) {
            $map = [];
        }

        return [
            'leaflet_css' => (string) ($map['leaflet_css'] ?? ''),
            'leaflet_js' => (string) ($map['leaflet_js'] ?? ''),
            'nominatim_reverse_url' => (string) ($map['nominatim_reverse_url'] ?? ''),
            'maptiler_reverse_geocode_url' => (string) ($map['maptiler_reverse_geocode_url'] ?? ''),
            'maptiler_sdk_css' => (string) ($map['maptiler_sdk_css'] ?? ''),
            'maptiler_sdk_js' => (string) ($map['maptiler_sdk_js'] ?? ''),
            'maptiler_leaflet_js' => (string) ($map['maptiler_leaflet_js'] ?? ''),
            'maptiler_geocoding_control_js' => (string) ($map['maptiler_geocoding_control_js'] ?? ''),
            'maptiler_api_key' => isset($_ENV['MAPTILER_API_KEY']) && is_string($_ENV['MAPTILER_API_KEY'])
                ? trim($_ENV['MAPTILER_API_KEY'])
                : '',
        ];
    }

    protected function isLocalEnvironment(): bool
    {
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production';
        return in_array(strtolower($env), ['local', 'development', 'dev'], true);
    }

    protected function validateEmail(string $email): ?string
    {
        if ($email === '') {
            return Config::get('messages.auth.email_required');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Config::get('messages.auth.email_invalid');
        }
        return null;
    }

    protected function validatePassword(string $password, ?int $minLength = null): ?string
    {
        if ($password === '') {
            return Config::get('messages.auth.password_required');
        }
        $minLength = $minLength ?? (int) Config::get('min_password_length', 8);
        if (strlen($password) < $minLength) {
            return sprintf(Config::get('messages.auth.password_min'), $minLength);
        }
        return null;
    }

    protected function validatePasswordConfirmation(string $password, string $passwordConfirmation): ?string
    {
        if ($passwordConfirmation === '') {
            return Config::get('messages.auth.password_confirm_required');
        }
        if ($password !== $passwordConfirmation) {
            return Config::get('messages.auth.password_confirm_mismatch');
        }
        return null;
    }

    protected function validateVerificationCodeFormat(string $code): ?string
    {
        if ($code === '') {
            return Config::get('messages.auth.verification_code_required');
        }
        $codeLength = (int) Config::get('verification_code.length', 6);
        if (!preg_match('/^\d{' . $codeLength . '}$/', $code)) {
            return sprintf(Config::get('messages.auth.verification_code_digits'), $codeLength);
        }
        return null;
    }

    protected function generateVerificationCode(): string
    {
        $codeLength = (int) Config::get('verification_code.length', 6);
        $maxValue = (int) str_repeat('9', $codeLength);
        return str_pad((string) random_int(0, $maxValue), $codeLength, '0', STR_PAD_LEFT);
    }

    protected function handleValidationErrors(array $errors, array $oldInput, string $errorKey, string $oldKey, string $redirectUrl): void
    {
        if (!empty($errors)) {
            $this->flash($errorKey, $errors);
            $this->flash($oldKey, $oldInput);
            $this->redirect($redirectUrl);
        }
    }
}
