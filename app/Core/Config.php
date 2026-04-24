<?php

namespace App\Core;

/**
 * 載入應用程式設定與多語系訊息（透過 LanguageLoader 合併）。
 */
class Config
{
    public const DEFAULT_LOCALE = 'zh_HK';
    public const DEFAULT_CURRENCY_CODE = 'HKD';
    public const DEFAULT_PLACEHOLDER_IMAGE = 'images/placeholder.jpg';

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
        $sessionLocale = $_SESSION['language'] ?? $_SESSION['locale'] ?? null;
        if (is_string($sessionLocale) && self::isValidLocale($sessionLocale)) {
            return $sessionLocale;
        }

        $loc = self::all()['locale'] ?? self::DEFAULT_LOCALE;

        return is_string($loc) && self::isValidLocale($loc)
            ? $loc
            : self::DEFAULT_LOCALE;
    }

    public static function defaultLocale(): string
    {
        return self::DEFAULT_LOCALE;
    }

    public static function defaultCurrencyCode(): string
    {
        return self::DEFAULT_CURRENCY_CODE;
    }

    public static function defaultPlaceholderImage(): string
    {
        $v = self::get('placeholder_image', self::DEFAULT_PLACEHOLDER_IMAGE);
        $path = is_string($v) ? trim($v) : '';
        return $path !== '' ? $path : self::DEFAULT_PLACEHOLDER_IMAGE;
    }

    /**
     * 取得指定語系的合併訊息（合併順序見 LanguageLoader::loadMergedBundle）。
     *
     * @return array<string, mixed>
     */
    private static function loadMessagesForLocale(string $locale): array
    {
        $root = dirname(__DIR__, 2);
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $locale)) {
            $locale = self::DEFAULT_LOCALE;
        }

        $loader = new LanguageLoader($root . '/languages');

        return $loader->loadMergedBundle($locale);
    }

    /**
     * 覆寫指定語系的快取訊息（例如測試或自訂載入流程）。
     *
     * @param array<string, mixed> $messages
     */
    public static function setMessagesForLocale(string $locale, array $messages): void
    {
        if (!self::isValidLocale($locale)) {
            return;
        }
        self::$messagesByLocale[$locale] = $messages;
    }

    private static function isValidLocale(string $locale): bool
    {
        return $locale !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $locale) === 1;
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
