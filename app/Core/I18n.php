<?php

namespace App\Core;

/**
 * 提供語系相關輔助（例如 HTML lang 的 BCP 47 轉換）。
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
