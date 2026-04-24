<?php

namespace App\Services;

use App\Core\Config;
use InvalidArgumentException;
use RuntimeException;

/**
 * 封裝 Lalamove REST API v3 呼叫（HMAC-SHA256 簽章）。
 *
 * @see https://developers.lalamove.com/
 */
class LalamoveService
{
    private const SANDBOX_BASE = 'https://rest.sandbox.lalamove.com';
    private const PRODUCTION_BASE = 'https://rest.lalamove.com';

    private string $apiKey;
    private string $apiSecret;
    private string $baseUrl;
    private string $market;
    private string $language;

    public function __construct(
        string $apiKey,
        string $apiSecret,
        bool $sandbox = true,
        string $market = 'HK',
        string $language = Config::DEFAULT_LOCALE
    ) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->baseUrl = $sandbox ? self::SANDBOX_BASE : self::PRODUCTION_BASE;
        $this->market = $market;
        $this->language = $language;
    }

    public static function fromConfig(): self
    {
        /** @var array<string, mixed> $cfg */
        $cfg = Config::get('lalamove', []);
        $key = isset($cfg['api_key']) && is_string($cfg['api_key']) ? $cfg['api_key'] : '';
        $secret = isset($cfg['api_secret']) && is_string($cfg['api_secret']) ? $cfg['api_secret'] : '';
        if ($key === '' || $secret === '') {
            throw new RuntimeException(
                'Lalamove 未設定：請在 .env 設定 LALAMOVE_API_KEY、LALAMOVE_API_SECRET'
            );
        }
        $sandbox = isset($cfg['sandbox']) ? (bool) $cfg['sandbox'] : true;
        $market = isset($cfg['market']) && is_string($cfg['market']) && $cfg['market'] !== ''
            ? $cfg['market']
            : 'HK';
        $language = isset($cfg['language']) && is_string($cfg['language']) && $cfg['language'] !== ''
            ? $cfg['language']
            : Config::DEFAULT_LOCALE;

        return new self($key, $secret, $sandbox, $market, $language);
    }

    /**
     * @return array{http_code: int, data: mixed, raw: string}
     */
    public function request(string $method, string $path, ?array $body = null): array
    {
        $method = strtoupper($method);
        if ($path === '' || $path[0] !== '/') {
            throw new InvalidArgumentException('Lalamove path must start with /');
        }

        $bodyString = '';
        if ($body !== null) {
            $encoded = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                throw new InvalidArgumentException('Lalamove request JSON encode failed');
            }
            $bodyString = $encoded;
        }

        $timestamp = (string) (int) (microtime(true) * 1000);
        $rawSignature = $timestamp . "\r\n" . $method . "\r\n" . $path . "\r\n\r\n" . $bodyString;
        $signature = strtolower(hash_hmac('sha256', $rawSignature, $this->apiSecret));
        $authToken = $this->apiKey . ':' . $timestamp . ':' . $signature;

        $headers = [
            'Authorization: hmac ' . $authToken,
            'Market: ' . $this->market,
            'Request-ID: ' . self::makeRequestId(),
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        $url = $this->baseUrl . $path;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyString);
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Lalamove HTTP 請求失敗: ' . $err);
        }
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = null;
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            $data = is_array($decoded) ? $decoded : null;
        }

        return [
            'http_code' => $httpCode,
            'data' => $data,
            'raw' => $raw,
        ];
    }

    /**
     * @return array{http_code: int, data: mixed, raw: string}
     */
    public function getCities(): array
    {
        return $this->request('GET', '/v3/cities');
    }

    /**
     * @param list<array{coordinates: array{lat: string, lng: string}, address: string}> $stops Min 2 stops (pickup + dropoff).
     * @param array<string, mixed> $extra Optional keys merged into data (e.g. specialRequests, isRouteOptimized, item).
     * @return array{http_code: int, data: mixed, raw: string}
     */
    public function createQuotation(
        array $stops,
        string $serviceType,
        ?string $scheduleAt = null,
        array $extra = []
    ): array {
        $data = array_merge([
            'serviceType' => $serviceType,
            'language' => $this->language,
            'stops' => $stops,
        ], $extra);
        if ($scheduleAt !== null && $scheduleAt !== '') {
            $data['scheduleAt'] = $scheduleAt;
        }

        return $this->request('POST', '/v3/quotations', ['data' => $data]);
    }

    /**
     * @return array{http_code: int, data: mixed, raw: string}
     */
    public function getQuotation(string $quotationId): array
    {
        $id = rawurlencode($quotationId);

        return $this->request('GET', '/v3/quotations/' . $id);
    }

    /**
     * @param array{quotationId: string, sender: array<string, mixed>, recipients: list<array<string, mixed>>} $order
     * @return array{http_code: int, data: mixed, raw: string}
     */
    public function placeOrder(array $order): array
    {
        return $this->request('POST', '/v3/orders', ['data' => $order]);
    }

    /**
     * @return array{http_code: int, data: mixed, raw: string}
     */
    public function getOrder(string $orderId): array
    {
        $id = rawurlencode($orderId);

        return $this->request('GET', '/v3/orders/' . $id);
    }

    /**
     * @return array{http_code: int, data: mixed, raw: string}
     */
    public function cancelOrder(string $orderId): array
    {
        $id = rawurlencode($orderId);

        return $this->request('DELETE', '/v3/orders/' . $id);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getMarket(): string
    {
        return $this->market;
    }

    private static function makeRequestId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }
}
