<?php

namespace App\Services;

use App\Core\Config;

/**
 * 依設定中的貨幣符號與小數位格式化金額。
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
