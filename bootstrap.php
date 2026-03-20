<?php

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

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';
    if (strpos($class, $prefix) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    if (file_exists($file)) require_once $file;
});

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Hong_Kong');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
