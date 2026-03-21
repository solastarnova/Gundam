<?php

namespace App\Services;

use App\Core\Config;

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
