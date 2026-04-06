<?php

namespace App\Services;

/**
 * Firebase 網頁應用程式設定（與 Admin 服務帳戶分開，供前端 compat SDK 使用）。
 */
class FirebaseWebConfig
{
    /** @return array<string, string>|null */
    public static function forJavaScript(): ?array
    {
        $apiKey = trim((string) (getenv('FIREBASE_WEB_API_KEY') ?: ''));
        $authDomain = trim((string) (getenv('FIREBASE_WEB_AUTH_DOMAIN') ?: ''));
        $projectId = trim((string) (getenv('FIREBASE_WEB_PROJECT_ID') ?: ''));
        if ($apiKey === '' || $authDomain === '' || $projectId === '') {
            return null;
        }
        $cfg = [
            'apiKey' => $apiKey,
            'authDomain' => $authDomain,
            'projectId' => $projectId,
        ];
        $appId = trim((string) (getenv('FIREBASE_WEB_APP_ID') ?: ''));
        if ($appId !== '') {
            $cfg['appId'] = $appId;
        }
        $ms = trim((string) (getenv('FIREBASE_WEB_MESSAGING_SENDER_ID') ?: ''));
        if ($ms !== '') {
            $cfg['messagingSenderId'] = $ms;
        }
        $bucket = trim((string) (getenv('FIREBASE_WEB_STORAGE_BUCKET') ?: ''));
        if ($bucket !== '') {
            $cfg['storageBucket'] = $bucket;
        }

        return $cfg;
    }
}
