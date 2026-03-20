<?php

namespace App\Core;

/**
 * Load config/app.php and config/messages.php once; provide get() / all().
 */
class Config
{
    /** @var array<string, mixed>|null */
    private static ?array $data = null;

    /** @var array<string, mixed>|null */
    private static ?array $messagesData = null;

    /**
     * Get full config array (loaded once).
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$data === null) {
            self::$data = require dirname(__DIR__, 2) . '/config/app.php';
        }
        return self::$data;
    }

    /**
     * Get config by key (dot notation for nested keys).
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (str_starts_with($key, 'messages.')) {
            return self::getFromMessages(substr($key, 9), $default);
        }
        $data = self::all();
        if ($key === '' || !str_contains($key, '.')) {
            return $data[$key] ?? $default;
        }
        $keys = explode('.', $key);
        $current = $data;
        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return $default;
            }
            $current = $current[$k];
        }
        return $current;
    }

    private static function getFromMessages(string $subKey, $default = null)
    {
        if (self::$messagesData === null) {
            self::$messagesData = require dirname(__DIR__, 2) . '/config/messages.php';
        }
        if ($subKey === '' || !str_contains($subKey, '.')) {
            return self::$messagesData[$subKey] ?? $default;
        }
        $keys = explode('.', $subKey);
        $current = self::$messagesData;
        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return $default;
            }
            $current = $current[$k];
        }
        return $current;
    }
}
