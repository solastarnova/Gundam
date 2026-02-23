<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;
use RuntimeException;

class PaymentService
{
    private string $secretKey;
    private string $publishableKey;

    public function __construct()
    {
        $this->secretKey = getenv('STRIPE_SECRET_KEY') ?: '';
        $this->publishableKey = getenv('STRIPE_PUBLISHABLE_KEY') ?: '';

        if (empty($this->secretKey)) {
            throw new RuntimeException('Stripe secret key is not configured. Please set STRIPE_SECRET_KEY in .env file.');
        }

        Stripe::setApiKey($this->secretKey);
    }

    /**
     * Get publishable key
     *
     * @return string
     */
    public function getPublishableKey(): string
    {
        return $this->publishableKey;
    }

    /**
     * Create payment intent
     *
     * @param int $amount Amount in cents
     * @param string $currency Currency code
     * @param array $metadata Metadata
     * @return array Payment intent data
     * @throws RuntimeException
     */
    public function createPaymentIntent(int $amount, string $currency = 'hkd', array $metadata = []): array
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => strtolower($currency),
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
            throw new RuntimeException('支付處理失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get payment intent
     *
     * @param string $paymentIntentId Payment intent ID
     * @return array Payment intent data
     * @throws RuntimeException
     */
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
            throw new RuntimeException('無法取得支付資訊: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Convert HKD to cents
     *
     * @param float $amount Amount in HKD
     * @return int Amount in cents
     */
    public static function convertToCents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
