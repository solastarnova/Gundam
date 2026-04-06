<?php

namespace App\Services;

use App\Core\Config;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Factory;

/**
 * Firebase Admin：驗證前端送上的 ID Token（第三方登入）。
 */
class FirebaseAdminAuth
{
    public static function credentialsPath(): ?string
    {
        $raw = trim((string) (getenv('FIREBASE_CREDENTIALS') ?: ''));
        if ($raw === '') {
            return null;
        }
        if (is_readable($raw)) {
            return $raw;
        }
        $candidate = dirname(__DIR__, 2) . '/' . ltrim($raw, '/');
        return is_readable($candidate) ? $candidate : null;
    }

    public static function isConfigured(): bool
    {
        return self::credentialsPath() !== null;
    }

    /**
     * @param mixed $firebaseClaim JWT `firebase` 宣告（陣列或物件）
     * @param list<string> $providerIds 與 identities 鍵名一致，例如 facebook.com
     */
    private static function identitiesIncludeTrustedProvider(mixed $firebaseClaim, array $providerIds): bool
    {
        if ($providerIds === []) {
            return false;
        }
        if ($firebaseClaim === null) {
            return false;
        }
        if (is_object($firebaseClaim)) {
            $firebaseClaim = (array) $firebaseClaim;
        }
        if (!is_array($firebaseClaim)) {
            return false;
        }
        $identities = $firebaseClaim['identities'] ?? null;
        if ($identities === null) {
            return false;
        }
        if (is_object($identities)) {
            $identities = (array) $identities;
        }
        if (!is_array($identities)) {
            return false;
        }
        foreach ($providerIds as $id) {
            if ($id !== '' && array_key_exists($id, $identities)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return object{uid: string, email: string, email_verified: bool, name: string}|null
     */
    public static function verifyIdToken(string $idToken): ?object
    {
        $path = self::credentialsPath();
        if ($path === null || $idToken === '') {
            return null;
        }

        try {
            $auth = (new Factory())->withServiceAccount($path)->createAuth();
            $verified = $auth->verifyIdToken($idToken);
        } catch (FailedToVerifyToken|\Throwable) {
            return null;
        }

        $uid = (string) ($verified->claims()->get('sub') ?? '');
        $email = trim((string) ($verified->claims()->get('email') ?? ''));
        $ev = $verified->claims()->get('email_verified');
        $emailVerified = $ev === true || $ev === 1 || $ev === '1' || $ev === 'true';
        $trustedProviders = Config::get('firebase.trust_email_verified_identity_providers', []);
        if (is_array($trustedProviders) && self::identitiesIncludeTrustedProvider(
            $verified->claims()->get('firebase'),
            $trustedProviders
        )) {
            $emailVerified = true;
        }
        $name = trim((string) ($verified->claims()->get('name') ?? ''));

        if ($uid === '' || $email === '') {
            return null;
        }

        return (object) [
            'uid' => $uid,
            'email' => $email,
            'email_verified' => $emailVerified,
            'name' => $name,
        ];
    }
}
