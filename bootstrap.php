<?php

// Load environment variables from .env
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $_ENV[$name] = $value;
        putenv($name . '=' . $value);
    }
}

// Composer autoloader (optional; Stripe, PHPMailer, etc.)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// PSR-4 style autoload for App\ namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';
    if (strpos($class, $prefix) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    if (file_exists($file)) require_once $file;
});

// View i18n helpers (messages merged from languages/ + config)
if (!function_exists('__m')) {
    /**
     * Resolve messages.view.* (vsprintf when extra args are passed).
     */
    function __m(string $key, mixed ...$args): string
    {
        $msg = \App\Core\Config::get('messages.view.' . $key);
        if (!is_string($msg) || $msg === '') {
            return $key;
        }
        if ($args !== []) {
            return vsprintf($msg, $args);
        }

        return $msg;
    }
}

if (!function_exists('__mu')) {
    /**
     * Resolve messages.ui.* for shared UI strings (vsprintf when args passed).
     */
    function __mu(string $key, mixed ...$args): string
    {
        $msg = \App\Core\Config::get('messages.ui.' . $key);
        if (!is_string($msg) || $msg === '') {
            return $key;
        }
        if ($args !== []) {
            return vsprintf($msg, $args);
        }

        return $msg;
    }
}

// Application timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Hong_Kong');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
