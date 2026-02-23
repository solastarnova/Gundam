<?php

namespace App\Services;

/**
 * Shipping fee calculation
 */
class ShippingService
{
    /**
     * Calculate shipping fee
     *
     * @param float $subtotal Subtotal amount
     * @param string $shippingMethod Shipping method
     * @return float Shipping fee
     */
    public static function calculateShippingFee(float $subtotal, string $shippingMethod = 'standard'): float
    {
        $config = require __DIR__ . '/../../config/app.php';
        $shippingConfig = $config['shipping'] ?? [];

        $expressFee = (float) ($shippingConfig['express_fee'] ?? 80);
        $standardFee = (float) ($shippingConfig['standard_fee'] ?? 50);
        $freeThreshold = (float) ($shippingConfig['free_threshold'] ?? 500);

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
     * Calculate order total
     *
     * @param float $subtotal Subtotal amount
     * @param string $shippingMethod Shipping method
     * @return float Total amount
     */
    public static function calculateTotal(float $subtotal, string $shippingMethod = 'standard'): float
    {
        $shippingFee = self::calculateShippingFee($subtotal, $shippingMethod);
        return $subtotal + $shippingFee;
    }
}
