<?php

namespace App\Services;

use App\Core\Config;

/**
 * 管理訂單狀態白名單與狀態轉移規則。
 */
class OrderStatusService
{
    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const SHIPPED = 'shipped';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    public static function allowed(): array
    {
        $allowed = Config::get('order_status.allowed', []);
        return is_array($allowed) ? array_values($allowed) : [];
    }

    public static function default(): string
    {
        $default = (string) Config::get('order_status.default', 'pending');
        return $default !== '' ? $default : 'pending';
    }

    public static function isAllowed(string $status): bool
    {
        return in_array($status, self::allowed(), true);
    }

    public static function transitions(): array
    {
        $transitions = Config::get('order_status.transitions', []);
        return is_array($transitions) ? $transitions : [];
    }

    public static function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }
        $matrix = self::transitions();
        $allowedNext = $matrix[$from] ?? [];
        return is_array($allowedNext) && in_array($to, $allowedNext, true);
    }
}
