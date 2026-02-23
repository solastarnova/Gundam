<?php

namespace App\Core;

class Controller
{
    protected View $view;
    protected string $baseUrl = '';
    /** @var array<string, mixed> */
    private static ?array $configCache = null;

    public function __construct()
    {
        $this->view = new View();
        $config = $this->getConfig();
        $base = rtrim($config['base_url'] ?? '', '/');
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
        $data['asset'] = fn (string $path) => $this->view->asset($path);
        $data['url'] = fn (string $path = '') => $this->view->url($path);
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
                    $url = $baseUrl . $url;
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

    protected function requireAuthForApi(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => '未登入', 'success' => false], JSON_UNESCAPED_UNICODE);
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

    /** @return array<string, mixed> */
    protected function getConfig(): array
    {
        if (self::$configCache === null) {
            self::$configCache = require __DIR__ . '/../../config/app.php';
        }
        return self::$configCache;
    }

    protected function getSiteName(): string
    {
        return (string) ($this->getConfig()['site_name'] ?? '高達模型商城');
    }

    protected function isLocalEnvironment(): bool
    {
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production';
        return in_array(strtolower($env), ['local', 'development', 'dev'], true);
    }

    protected function validateEmail(string $email): ?string
    {
        if ($email === '') {
            return '請輸入電郵地址';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '請輸入有效的電郵地址';
        }
        return null;
    }

    protected function validatePassword(string $password, ?int $minLength = null): ?string
    {
        if ($password === '') {
            return '請輸入密碼';
        }
        $minLength = $minLength ?? (int) ($this->getConfig()['min_password_length'] ?? 8);
        if (strlen($password) < $minLength) {
            return "密碼至少需 {$minLength} 個字元";
        }
        return null;
    }

    protected function validatePasswordConfirmation(string $password, string $passwordConfirmation): ?string
    {
        if ($passwordConfirmation === '') {
            return '請再次輸入密碼';
        }
        if ($password !== $passwordConfirmation) {
            return '兩次輸入的密碼不一致';
        }
        return null;
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
