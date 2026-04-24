<?php

namespace App\Services;

use App\Core\PaymentProvider;

/** 在 Session 儲存與讀取結帳上下文快照。 */
class CheckoutContextService
{
    public function store(string $orderNumber, array $context): void
    {
        if ($orderNumber === '') {
            return;
        }
        if (!isset($_SESSION['checkout_context']) || !is_array($_SESSION['checkout_context'])) {
            $_SESSION['checkout_context'] = [];
        }
        $_SESSION['checkout_context'][$orderNumber] = $context;
    }

    public function load(string $orderNumber): ?array
    {
        if ($orderNumber === '') {
            return null;
        }
        $all = $_SESSION['checkout_context'] ?? null;
        if (!is_array($all)) {
            return null;
        }
        $ctx = $all[$orderNumber] ?? null;
        return is_array($ctx) ? $ctx : null;
    }

    public function updateProviderReference(string $orderNumber, string $providerReference): void
    {
        if ($orderNumber === '' || $providerReference === '') {
            return;
        }
        $ctx = $this->load($orderNumber);
        if ($ctx === null) {
            return;
        }
        $ctx['provider_reference'] = $providerReference;
        $this->store($orderNumber, $ctx);
    }

    public function validate(array $ctx, int $userId, string $paymentMethod): bool
    {
        if ((int) ($ctx['user_id'] ?? 0) !== $userId) {
            return false;
        }
        $expiresAt = (int) ($ctx['expires_at'] ?? 0);
        if ($expiresAt <= 0 || time() > $expiresAt) {
            return false;
        }
        $provider = (string) ($ctx['provider'] ?? '');
        if ($paymentMethod === PaymentProvider::PAYPAL) {
            return $provider === PaymentProvider::PAYPAL;
        }
        return $provider === PaymentProvider::STRIPE;
    }
}
