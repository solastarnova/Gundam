<?php

namespace App\Services;

/**
 * 統一結帳地址指紋與座標正規化邏輯，避免多處實作漂移。
 */
final class CheckoutFingerprintService
{
    private function __construct()
    {
    }

    public static function normalizeCoordinate(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '' || !is_numeric($trimmed)) {
            return null;
        }
        $f = (float) $trimmed;
        if (abs($f) < 1e-12) {
            return null;
        }

        return number_format($f, 6, '.', '');
    }

    public static function buildShippingFingerprint(string $address, ?string $lat = null, ?string $lng = null): string
    {
        $latN = $lat !== null && $lat !== '' ? self::normalizeCoordinate((string) $lat) : null;
        $lngN = $lng !== null && $lng !== '' ? self::normalizeCoordinate((string) $lng) : null;
        $base = $address;
        if ($latN !== null && $lngN !== null) {
            $lat4 = number_format((float) $latN, 4, '.', '');
            $lng4 = number_format((float) $lngN, 4, '.', '');
            $base .= '|coord:exact:' . $lat4 . ',' . $lng4;
        } else {
            $base .= '|coord:none';
        }

        return hash('sha256', $base);
    }
}
