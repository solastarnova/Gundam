<?php

namespace App\Services;

use App\Core\Config;
use RuntimeException;

/**
 * 結帳用：地址轉座標後呼叫 Lalamove POST /v3/quotations 取得運費。
 */
class LalamoveCheckoutService
{
    private LalamoveService $lalamove;
    private AddressGeocoderService $geocoder;

    public function __construct(LalamoveService $lalamove, ?AddressGeocoderService $geocoder = null)
    {
        $this->lalamove = $lalamove;
        $this->geocoder = $geocoder ?? new AddressGeocoderService();
    }

    public static function fromConfigOrNull(): ?self
    {
        if (!self::isCheckoutConfigured()) {
            return null;
        }
        try {
            return new self(LalamoveService::fromConfig());
        } catch (\Throwable) {
            return null;
        }
    }

    public static function isCheckoutConfigured(): bool
    {
        $c = Config::get('lalamove', []);
        if (!is_array($c)) {
            return false;
        }
        $key = trim((string) ($c['api_key'] ?? ''));
        $secret = trim((string) ($c['api_secret'] ?? ''));
        $lat = trim((string) ($c['pickup_lat'] ?? ''));
        $lng = trim((string) ($c['pickup_lng'] ?? ''));
        $addr = trim((string) ($c['pickup_address'] ?? ''));

        return $key !== '' && $secret !== '' && $lat !== '' && $lng !== '' && $addr !== '';
    }

    /**
     * @param int $totalItemQuantity 購物車總件數（用於 Lalamove item.quantity）
     * @return array{fee: float, currency: string, quotation_id: string|null, raw_http: int}
     */
    public function quoteDelivery(string $dropoffAddressOneLine, int $totalItemQuantity = 1, ?array $coordinates = null): array
    {
        $coords = $this->resolveDropoffCoordinates($dropoffAddressOneLine, $coordinates);

        $cfg = Config::get('lalamove', []);
        if (!is_array($cfg)) {
            $cfg = [];
        }
        $pickLat = (string) ($cfg['pickup_lat'] ?? '');
        $pickLng = (string) ($cfg['pickup_lng'] ?? '');
        $pickAddr = (string) ($cfg['pickup_address'] ?? '');
        $serviceType = (string) ($cfg['checkout_service_type'] ?? 'MOTORCYCLE');

        $stops = [
            [
                'coordinates' => ['lat' => $pickLat, 'lng' => $pickLng],
                'address' => $pickAddr,
            ],
            [
                'coordinates' => ['lat' => $coords['lat'], 'lng' => $coords['lng']],
                'address' => trim($dropoffAddressOneLine),
            ],
        ];

        $q = max(1, min(999, $totalItemQuantity));
        $item = [
            'quantity' => (string) $q,
            'weight' => '1_KG_TO_5_KG',
            'categories' => ['ELECTRONICS'],
            'handlingInstructions' => ['KEEP_UPRIGHT'],
        ];

        $res = $this->lalamove->createQuotation($stops, $serviceType, null, ['item' => $item]);
        $http = $res['http_code'];
        if ($http < 200 || $http >= 300) {
            $msg = Config::get('messages.payment.lalamove_quote_failed');
            throw new RuntimeException(is_string($msg) ? $msg : 'Lalamove quotation failed');
        }
        $data = $res['data'];
        if (!is_array($data) || !isset($data['data']) || !is_array($data['data'])) {
            throw new RuntimeException((string) Config::get('messages.payment.lalamove_quote_failed'));
        }
        $d = $data['data'];
        $breakdown = $d['priceBreakdown'] ?? null;
        if (!is_array($breakdown) || !isset($breakdown['total'])) {
            throw new RuntimeException((string) Config::get('messages.payment.lalamove_quote_failed'));
        }
        $total = (float) $breakdown['total'];
        $currency = isset($breakdown['currency']) ? (string) $breakdown['currency'] : Config::defaultCurrencyCode();
        $qid = isset($d['quotationId']) && is_string($d['quotationId']) ? $d['quotationId'] : null;

        return [
            'fee' => max(0.0, $total),
            'currency' => $currency,
            'quotation_id' => $qid,
            'raw_http' => $http,
        ];
    }

    private function resolveDropoffCoordinates(string $dropoffAddressOneLine, ?array $coordinates = null): array
    {
        $lat = null;
        $lng = null;
        if (is_array($coordinates)) {
            $lat = $coordinates['lat'] ?? null;
            $lng = $coordinates['lng'] ?? null;
        }

        if ($this->isValidCoordinatePair($lat, $lng)) {
            return [
                'lat' => (string) $lat,
                'lng' => (string) $lng,
            ];
        }

        $geocoded = $this->geocoder->geocodeHongKong($dropoffAddressOneLine);
        if ($geocoded === null) {
            throw new RuntimeException((string) Config::get('messages.payment.lalamove_geocode_failed'));
        }
        return $geocoded;
    }

    private function isValidCoordinatePair($lat, $lng): bool
    {
        if ($lat === null || $lng === null || $lat === '' || $lng === '') {
            return false;
        }
        return is_numeric((string) $lat) && is_numeric((string) $lng);
    }
}
