<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;
use RuntimeException;

/**
 * Stripe PaymentIntent wrapper.
 */
class PaymentService
{
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = getenv('STRIPE_SECRET_KEY') ?: '';

        if (empty($this->secretKey)) {
            throw new RuntimeException('Stripe secret key is not configured. Please set STRIPE_SECRET_KEY in .env file.');
        }

        Stripe::setApiKey($this->secretKey);
    }

    public function createPaymentIntent(int $amount, string $currency = 'hkd', array $metadata = []): array
    {
        try {
            $normalizedCurrency = strtolower(trim($currency));
            if ($normalizedCurrency === '') {
                $normalizedCurrency = 'hkd';
            }
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => $normalizedCurrency,
                'payment_method_types' => ['card'],
                'metadata' => $metadata,
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
            ];
        } catch (ApiErrorException $e) {
            error_log('Stripe PaymentIntent creation failed: ' . $e->getMessage());
            throw new RuntimeException('Stripe PaymentIntent creation failed', 0, $e);
        }
    }

    public function getPaymentIntent(string $paymentIntentId): array
    {
        try {
            $pi = PaymentIntent::retrieve($paymentIntentId);
            return [
                'success' => true,
                'id' => $pi->id,
                'status' => $pi->status,
                'amount' => $pi->amount,
                'currency' => $pi->currency,
                'client_secret' => $pi->client_secret,
                'metadata' => $pi->metadata->toArray(),
            ];
        } catch (ApiErrorException $e) {
            error_log('Stripe PaymentIntent retrieval failed: ' . $e->getMessage());
            throw new RuntimeException('Stripe PaymentIntent retrieval failed', 0, $e);
        }
    }

    public static function convertToCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

}
