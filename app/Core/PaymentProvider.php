<?php

namespace App\Core;

/**
 * 定義 orders.payment_provider 使用的標準支付提供者代碼。
 */
final class PaymentProvider
{
    public const STRIPE = 'stripe';
    public const PAYPAL = 'paypal';
    public const WALLET = 'wallet';

    private function __construct()
    {
    }
}

