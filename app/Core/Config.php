<?php

namespace App\Core;

/**
 * Application config and locale-aware messages (merged via App\Core\LanguageLoader).
 */
class Config
{
    /** @var array<string, mixed>|null */
    private static ?array $data = null;

    /** @var array<string, array<string, mixed>> */
    private static array $messagesByLocale = [];

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

    public static function locale(): string
    {
        $loc = self::all()['locale'] ?? 'zh_HK';

        return is_string($loc) && $loc !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $loc) === 1
            ? $loc
            : 'zh_HK';
    }

    /**
     * Merged messages for one locale (see LanguageLoader::loadMergedBundle for merge order).
     *
     * @return array<string, mixed>
     */
    private static function loadMessagesForLocale(string $locale): array
    {
        $root = dirname(__DIR__, 2);
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $locale)) {
            $locale = 'zh_HK';
        }

        $loader = new LanguageLoader($root . '/languages');

        return $loader->loadMergedBundle($locale);
    }

    /**
     * Replace cached messages for a locale (e.g. tests or bootstrap after custom load() tree merge).
     *
     * @param array<string, mixed> $messages
     */
    public static function setMessagesForLocale(string $locale, array $messages): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $locale)) {
            return;
        }
        self::$messagesByLocale[$locale] = $messages;
    }

    private static function getFromMessages(string $subKey, $default = null)
    {
        $locale = self::locale();
        if (!isset(self::$messagesByLocale[$locale])) {
            self::$messagesByLocale[$locale] = self::loadMessagesForLocale($locale);
        }
        $messages = self::$messagesByLocale[$locale];
        if ($subKey === '' || !str_contains($subKey, '.')) {
            return $messages[$subKey] ?? $default;
        }
        $keys = explode('.', $subKey);
        $current = $messages;
        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return $default;
            }
            $current = $current[$k];
        }

        return $current;
    }
}
