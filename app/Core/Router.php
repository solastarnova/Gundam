<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $params = [];

    public function add(string $method, string $path, string $controller, string $action): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
        ];
    }

    public function get(string $path, string $controller, string $action): void
    {
        $this->add('GET', $path, $controller, $action);
    }

    public function post(string $path, string $controller, string $action): void
    {
        $this->add('POST', $path, $controller, $action);
    }

    public function dispatch(): void
    {
        $uri = $this->getUri();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchRoute($route['path'], $uri)) {
                $this->callController($route['controller'], $route['action']);
                return;
            }
        }

        http_response_code(404);
        $view = new View();
        $view->renderWithLayout('errors/404', ['title' => '404 - 找不到頁面']);
    }

    private function getUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        // Remove SCRIPT_NAME
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/' && $basePath !== '' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        // Remove base_url
        $config = require __DIR__ . '/../../config/app.php';
        $baseUrl = rtrim($config['base_url'] ?? '', '/');
        if ($baseUrl !== '' && strpos($uri, $baseUrl) === 0) {
            $uri = substr($uri, strlen($baseUrl));
        }

        $uri = $uri ?: '/';
        if (isset($uri[0]) && $uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    private function matchRoute(string $pattern, string $uri): bool
    {
        $pattern = preg_replace('/:(\w+)/', '([^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches);
            $this->params = $matches;
            return true;
        }

        return false;
    }

    private function callController(string $controller, string $action): void
    {
        $controllerClass = "App\\Controllers\\{$controller}";

        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller {$controllerClass} not found");
        }

        $controllerInstance = new $controllerClass();

        if (!method_exists($controllerInstance, $action)) {
            throw new \RuntimeException("Action {$action} not found in {$controllerClass}");
        }

        call_user_func_array([$controllerInstance, $action], $this->params);
    }
}


