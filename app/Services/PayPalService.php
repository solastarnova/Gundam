<?php

namespace App\Services;

use RuntimeException;

/**
 * 提供 PayPal 訂單驗證服務（支援 sandbox/live）。
 */
class PayPalService
{
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->clientId = $this->resolveEnv('PAYPAL_CLIENT_ID');
        $this->clientSecret = $this->resolveEnv('PAYPAL_CLIENT_SECRET');
        $mode = strtolower($this->resolveEnv('PAYPAL_MODE', 'sandbox'));
        $this->baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        if ($this->clientId === '' || $this->clientSecret === '') {
            throw new RuntimeException('PayPal credentials are not configured.');
        }
    }

    /**
     * @return array{status:string,amount:float,currency:string,id:string}
     */
    public function verifyOrder(string $orderId): array
    {
        $orderId = trim($orderId);
        if ($orderId === '') {
            throw new RuntimeException('PayPal order id is required.');
        }

        $token = $this->getAccessToken();
        $data = $this->fetchOrderData($orderId, $token);
        $parsed = $this->parseVerifiedOrder($data, $orderId);
        if ($parsed['status'] === 'COMPLETED') {
            return $parsed;
        }

        // If buyer has approved but not captured yet, capture via server-side API.
        if ($parsed['status'] === 'APPROVED') {
            $captured = $this->captureOrder($orderId, $token);
            return $this->parseVerifiedOrder($captured, $orderId);
        }

        return $parsed;
    }

    /**
     * @param array<string,mixed> $data
     * @return array{status:string,amount:float,currency:string,id:string}
     */
    private function parseVerifiedOrder(array $data, string $orderId): array
    {
        $status = strtoupper((string) ($data['status'] ?? ''));
        $purchaseUnit = is_array($data['purchase_units'][0] ?? null) ? $data['purchase_units'][0] : [];
        $capture = is_array($purchaseUnit['payments']['captures'][0] ?? null) ? $purchaseUnit['payments']['captures'][0] : null;
        $amountNode = is_array($capture) ? ($capture['amount'] ?? null) : ($purchaseUnit['amount'] ?? null);
        $captureStatus = strtoupper((string) ($capture['status'] ?? ''));

        if ($status !== 'COMPLETED' && $captureStatus === 'COMPLETED') {
            $status = 'COMPLETED';
        }

        $amount = 0.0;
        $currency = '';
        if (is_array($amountNode)) {
            $amount = (float) ($amountNode['value'] ?? 0);
            $currency = strtoupper((string) ($amountNode['currency_code'] ?? ''));
        }

        return [
            'status' => $status,
            'amount' => $amount,
            'currency' => $currency,
            'id' => (string) ($data['id'] ?? $orderId),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function fetchOrderData(string $orderId, string $token): array
    {
        $url = $this->baseUrl . '/v2/checkout/orders/' . rawurlencode($orderId);
        $response = $this->request('GET', $url, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);
        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid PayPal order response.');
        }

        return $data;
    }

    /**
     * @return array<string,mixed>
     */
    private function captureOrder(string $orderId, string $token): array
    {
        $url = $this->baseUrl . '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture';
        $response = $this->request('POST', $url, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ], '{}');
        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid PayPal capture response.');
        }

        return $data;
    }

    private function resolveEnv(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
        if (isset($_ENV[$key]) && is_string($_ENV[$key]) && trim($_ENV[$key]) !== '') {
            return trim($_ENV[$key]);
        }
        if (isset($_SERVER[$key]) && is_string($_SERVER[$key]) && trim($_SERVER[$key]) !== '') {
            return trim($_SERVER[$key]);
        }

        return $default;
    }

    private function getAccessToken(): string
    {
        $url = $this->baseUrl . '/v1/oauth2/token';
        $auth = base64_encode($this->clientId . ':' . $this->clientSecret);
        $response = $this->request('POST', $url, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/x-www-form-urlencoded',
        ], 'grant_type=client_credentials');
        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['access_token'])) {
            throw new RuntimeException('Unable to obtain PayPal access token.');
        }

        return (string) $data['access_token'];
    }

    private function request(string $method, string $url, array $headers, string $body = ''): string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Unable to initialize cURL for PayPal request.');
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        if ($body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $result = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            throw new RuntimeException('PayPal API request failed: ' . $error);
        }
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('PayPal API request returned HTTP ' . $statusCode);
        }

        return (string) $result;
    }
}

