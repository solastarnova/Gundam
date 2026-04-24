<?php

namespace App\Core;

/**
 * 提供視圖路徑解析、版面渲染與 URL/資源輔助。
 */
class View
{
    private string $basePath;
    private string $baseUrl;

    public function __construct()
    {
        $this->basePath = __DIR__ . '/../../views';
        $config = Config::all();
        $base = rtrim($config['base_url'] ?? '', '/');
        if ($base === '' && isset($_SERVER['SCRIPT_NAME'])) {
            $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            if ($base === '/' || $base === '\\') {
                $base = '';
            }
        }
        $this->baseUrl = $base;
    }

    public function render(string $view, array $data = []): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        $viewFile = $this->basePath . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View file not found: {$viewFile}");
        }
        include $viewFile;
        return ob_get_clean();
    }

    public function renderWithLayout(string $view, array $data = [], string $layout = 'main'): void
    {
        if (!isset($data['html_lang'])) {
            $data['locale'] = Config::locale();
            $data['html_lang'] = 'en';
        }
        if (!isset($data['currency'])) {
            $currency = Config::get('currency', []);
            $data['currency'] = is_array($currency) ? $currency : [];
        }
        $data['baseUrl'] = $this->baseUrl ?: '';
        $data['asset'] = fn (string $path) => $this->asset($path);
        $data['url'] = fn (string $path = '') => $this->url($path);
        $content = $this->render($view, $data);
        $data['content'] = $content;
        $layoutView = str_contains($layout, '/') ? $layout : 'layouts/' . $layout;
        echo $this->render($layoutView, $data);
    }

    public function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $base = $this->baseUrl ?: '';
        return $base === '' ? '/' . $path : $base . '/' . $path;
    }

    public function url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        $base = rtrim($this->baseUrl ?: '', '/');
        if ($path === '') {
            return $base === '' ? '/' : $base . '/';
        }
        return ($base === '' ? '' : $base) . '/' . $path;
    }
}
