<?php

namespace App\Services;

use App\Core\Config;
use App\Models\CartModel;
use InvalidArgumentException;

/**
 * 管理一次性結帳報價快照（供付款 API 透過 checkout_token 使用）。
 */
class CheckoutSnapshotService
{
    private const SESSION_KEY = 'checkout_snapshots';

    private const TTL_SECONDS = 900;

    public static function normalizeCheckoutAddress(string $address): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $address));
    }

    public static function cartSignature(CartModel $cartModel, array $cartItems): string
    {
        $subtotal = round($cartModel->calculateSubtotal($cartItems), 2);
        $qty = 0;
        foreach ($cartItems as $row) {
            $qty += (int) ($row['qty'] ?? 0);
        }
        $qty = max(1, $qty);

        return hash('sha256', (string) $subtotal . '|' . (string) $qty);
    }

    /**
     * @return array<string, mixed> Snapshot row (also materialized into $_SESSION['checkout_lalamove'])
     */
    public function issueFromQuote(
        int $userId,
        float $fee,
        string $normalizedAddress,
        string $addressOneLine,
        ?string $quoteLat,
        ?string $quoteLng,
        string $cartSig
    ): string {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        $nl = null;
        $ng = null;
        if ($quoteLat !== null && $quoteLat !== '' && $quoteLng !== null && $quoteLng !== '') {
            $nl = $this->normalizeCoordinate((string) $quoteLat);
            $ng = $this->normalizeCoordinate((string) $quoteLng);
        }
        $token = bin2hex(random_bytes(16));
        $snap = [
            'user_id' => $userId,
            'fee' => $fee,
            'norm_address' => $normalizedAddress,
            'address_one_line' => $addressOneLine,
            'quote_lat' => $nl,
            'quote_lng' => $ng,
            'cart_sig' => $cartSig,
            'created_at' => time(),
            'expires_at' => time() + self::TTL_SECONDS,
        ];
        $_SESSION[self::SESSION_KEY][$token] = $snap;
        $this->materializeLalamoveSession($snap);
        $this->pruneSnapshotsForUser($userId, 24);

        return $token;
    }

    /**
     * @param array<int, array<string, mixed>> $cartItems
     *
     * @return array<string, mixed>
     */
    public function assertValidSnapshot(int $userId, string $token, array $cartItems, CartModel $cartModel): array
    {
        $token = trim($token);
        if ($token === '') {
            throw new InvalidArgumentException($this->msg('messages.payment.lalamove_snapshot_invalid', 'Invalid checkout token'));
        }
        $all = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($all) || !isset($all[$token]) || !is_array($all[$token])) {
            throw new InvalidArgumentException($this->msg('messages.payment.lalamove_snapshot_invalid', 'Invalid checkout token'));
        }
        $snap = $all[$token];
        if ((int) ($snap['user_id'] ?? 0) !== $userId) {
            throw new InvalidArgumentException($this->msg('messages.payment.lalamove_snapshot_invalid', 'Invalid checkout token'));
        }
        $expiresAt = (int) ($snap['expires_at'] ?? 0);
        if ($expiresAt <= 0 || time() > $expiresAt) {
            throw new InvalidArgumentException($this->msg('messages.payment.lalamove_snapshot_expired', 'Checkout quote expired'));
        }
        $currentSig = self::cartSignature($cartModel, $cartItems);
        if ((string) ($snap['cart_sig'] ?? '') !== $currentSig) {
            throw new InvalidArgumentException($this->msg('messages.payment.lalamove_checkout_cart_changed', 'Cart updated'));
        }

        return $snap;
    }

    /**
     * @param array<string, mixed> $snap
     */
    public function materializeLalamoveSession(array $snap): void
    {
        $norm = (string) ($snap['norm_address'] ?? '');
        $lat = $snap['quote_lat'] ?? null;
        $lng = $snap['quote_lng'] ?? null;
        if ($lat === null || $lng === null || (string) $lat === '' || (string) $lng === '') {
            $lat = null;
            $lng = null;
        } else {
            $lat = (string) $lat;
            $lng = (string) $lng;
        }
        $fee = (float) ($snap['fee'] ?? 0);
        $addrHash = $this->buildShippingFingerprint($norm, $lat, $lng);
        $_SESSION['checkout_lalamove'] = [
            'fee' => $fee,
            'addr_hash' => $addrHash,
            'cart_sig' => (string) ($snap['cart_sig'] ?? ''),
            'norm_address' => $norm,
            'quote_lat' => $lat,
            'quote_lng' => $lng,
        ];
    }

    public function forget(string $token): void
    {
        $token = trim($token);
        if ($token === '' || !isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            return;
        }
        unset($_SESSION[self::SESSION_KEY][$token]);
    }

    private function msg(string $key, string $fallback): string
    {
        $m = Config::get($key);
        if (is_string($m) && $m !== '') {
            return $m;
        }

        return $fallback;
    }

    private function pruneSnapshotsForUser(int $userId, int $keep): void
    {
        $all = &$_SESSION[self::SESSION_KEY];
        if (!is_array($all)) {
            return;
        }
        $entries = [];
        foreach ($all as $tok => $snap) {
            if (!is_array($snap) || (int) ($snap['user_id'] ?? 0) !== $userId) {
                continue;
            }
            $entries[] = ['t' => (string) $tok, 'ct' => (int) ($snap['created_at'] ?? 0)];
        }
        usort($entries, static fn (array $a, array $b): int => $b['ct'] <=> $a['ct']);
        foreach (array_slice($entries, $keep) as $row) {
            unset($all[$row['t']]);
        }
    }

    private function normalizeCoordinate(string $value): ?string
    {
        return CheckoutFingerprintService::normalizeCoordinate($value);
    }

    private function buildShippingFingerprint(string $address, ?string $lat = null, ?string $lng = null): string
    {
        return CheckoutFingerprintService::buildShippingFingerprint($address, $lat, $lng);
    }
}
