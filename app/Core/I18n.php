<?php

namespace App\Core;

/**
 * Locale helpers (BCP 47 for HTML lang, etc.).
 */
final class I18n
{
    public static function toBcp47(string $locale): string
    {
        $locale = trim($locale);
        if ($locale === '') {
            return 'zh-HK';
        }

        return str_replace('_', '-', $locale);
    }

    private function __construct()
    {
    }
}
