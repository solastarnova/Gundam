<?php

namespace App\Core;

class View
{
    private string $basePath;
    private string $baseUrl;

    public function __construct()
    {
        $this->basePath = __DIR__ . '/../../views';
        $config = require __DIR__ . '/../../config/app.php';
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
        $data['baseUrl'] = $this->baseUrl ?: '';
        $data['asset'] = fn (string $path) => $this->asset($path);
        $data['url'] = fn (string $path = '') => $this->url($path);
        $content = $this->render($view, $data);
        $data['content'] = $content;
        echo $this->render('layouts/' . $layout, $data);
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
