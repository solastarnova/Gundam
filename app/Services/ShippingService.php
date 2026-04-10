<?php

namespace App\Services;

/**
 * Shipping fee calculation from config (with defaults).
 */
class ShippingService
{
    private const DEFAULT_CONFIG = [
        'express_fee' => 80.0,
        'standard_fee' => 50.0,
        'free_threshold' => 500.0,
    ];

    /**
     * Merged shipping thresholds and fees.
     *
     * @return array{express_fee: float, standard_fee: float, free_threshold: float}
     */
    public static function getConfig(): array
    {
        $raw = \App\Core\Config::get('shipping', []);
        if (!is_array($raw)) {
            $raw = [];
        }

        return [
            'express_fee' => (float) ($raw['express_fee'] ?? self::DEFAULT_CONFIG['express_fee']),
            'standard_fee' => (float) ($raw['standard_fee'] ?? self::DEFAULT_CONFIG['standard_fee']),
            'free_threshold' => (float) ($raw['free_threshold'] ?? self::DEFAULT_CONFIG['free_threshold']),
        ];
    }

    /**
     * @param float $subtotal Cart subtotal before shipping
     * @param string $shippingMethod standard|express
     */
    public static function calculateShippingFee(float $subtotal, string $shippingMethod = 'standard'): float
    {
        $shippingConfig = self::getConfig();
        $expressFee = $shippingConfig['express_fee'];
        $standardFee = $shippingConfig['standard_fee'];
        $freeThreshold = $shippingConfig['free_threshold'];

        if ($shippingMethod === 'express') {
            return $expressFee;
        } elseif ($shippingMethod === 'standard') {
            if ($subtotal >= $freeThreshold) {
                return 0.0;
            }
            return $standardFee;
        }

        return 0.0;
    }

    /**
     * Subtotal plus shipping for the given method.
     */
    public static function calculateTotal(float $subtotal, string $shippingMethod = 'standard'): float
    {
        $shippingFee = self::calculateShippingFee($subtotal, $shippingMethod);
        return $subtotal + $shippingFee;
    }
}
