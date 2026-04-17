<?php

namespace App\Services;

use App\Core\Config;

/**
 * OpenStreetMap Nominatim（免費；請遵守 1 req/s，並設定識別用 User-Agent）。
 */
class AddressGeocoderService
{
    private string $userAgent;

    public function __construct(?string $userAgent = null)
    {
        $cfg = Config::get('lalamove', []);
        $this->userAgent = $userAgent ?? (is_array($cfg) ? (string) ($cfg['nominatim_user_agent'] ?? '') : '');
        if ($this->userAgent === '') {
            $this->userAgent = 'GundamShop/1.0';
        }
    }

    /**
     * @return array{lat: string, lng: string}|null
     */
    public function geocodeHongKong(string $addressLine): ?array
    {
        $q = trim($addressLine);
        if ($q === '') {
            return null;
        }
        $query = 'Hong Kong ' . $q;
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q' => $query,
            'format' => 'json',
            'limit' => 1,
        ], '', '&', PHP_QUERY_RFC3986);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_TIMEOUT => 12,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $code !== 200) {
            return null;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || $decoded === []) {
            return null;
        }
        $first = $decoded[0];
        if (!is_array($first)) {
            return null;
        }
        $lat = $first['lat'] ?? null;
        $lng = $first['lon'] ?? ($first['lng'] ?? null);
        if ($lat === null || $lng === null) {
            return null;
        }

        return [
            'lat' => self::formatCoord($lat),
            'lng' => self::formatCoord($lng),
        ];
    }

    /**
     * @param string|float|int $v
     */
    private static function formatCoord($v): string
    {
        $f = is_numeric($v) ? (float) $v : 0.0;

        return rtrim(rtrim(number_format($f, 8, '.', ''), '0'), '.') ?: '0';
    }
}
