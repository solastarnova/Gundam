<?php

namespace App\Core;

/** @see docs/CONFIG_AND_MESSAGES.md */
class Config
{
    /** @var array<string, mixed>|null */
    private static ?array $data = null;

    /** @var array<string, mixed>|null */
    private static ?array $messagesData = null;

    public static function all(): array
    {
        if (self::$data === null) {
            self::$data = require dirname(__DIR__, 2) . '/config/app.php';
        }
        return self::$data;
    }

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
