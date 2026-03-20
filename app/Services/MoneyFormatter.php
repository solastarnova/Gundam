<?php

namespace App\Services;

use App\Core\Config;

/**
 * 與 Controller::render 注入的 $money() 邏輯一致，供非視圖層（郵件、API 組裝等）使用。
 */
final class MoneyFormatter
{
    public static function format(float $amount): string
    {
        $currency = Config::get('currency', []);
        if (!is_array($currency)) {
            $currency = [];
        }
        $symbol = (string) ($currency['symbol'] ?? 'HK$');
        $decimals = (int) ($currency['decimals'] ?? 2);

        return $symbol . number_format($amount, $decimals, '.', '');
    }
}
