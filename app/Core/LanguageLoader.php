<?php

namespace App\Core;

/**
 * 載入語系 JSON，支援巢狀樹與扁平合併訊息兩種模式。
 */
class LanguageLoader
{
    protected string $langPath;

    /** @var array<string, array<string, mixed>> */
    protected array $cache = [];

    public function __construct(string $langPath)
    {
        $this->langPath = rtrim($langPath, DIRECTORY_SEPARATOR);
    }

    /**
     * 以巢狀樹模式載入單一語系（檔名為鍵、子資料夾遞迴）。
     *
     * @return array<string, mixed>
     */
    public function load(string $lang): array
    {
        if (isset($this->cache[$lang])) {
            return $this->cache[$lang];
        }

        $basePath = $this->langPath . DIRECTORY_SEPARATOR . $lang;
        if (!is_dir($basePath)) {
            return [];
        }

        $this->cache[$lang] = $this->scanDirectory($basePath);

        return $this->cache[$lang];
    }

    /**
     * 載入語系並深度合併所有 JSON（沿用舊版 Config 合併語義）。
     * 合併順序：common.json、api/*.json（排序）、mail.json、membership.json、view/*.json（排序）、其餘 JSON（排序）。
     *
     * @return array<string, mixed>
     */
    public function loadMergedBundle(string $lang): array
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $lang)) {
            $lang = Config::DEFAULT_LOCALE;
        }

        $basePath = $this->langPath . DIRECTORY_SEPARATOR . $lang;
        if (!is_dir($basePath)) {
            $basePath = $this->langPath . DIRECTORY_SEPARATOR . Config::DEFAULT_LOCALE;
        }

        if (!is_dir($basePath)) {
            return [];
        }

        $paths = $this->collectAllJsonPaths($basePath);
        $ordered = $this->orderPathsForMerge($basePath, $paths);
        $bundle = [];
        foreach ($ordered as $path) {
            $bundle = $this->mergeJsonFileIntoBundle($bundle, $path);
        }

        return $bundle;
    }

    /**
     * @return list<string>
     */
    protected function collectAllJsonPaths(string $basePath): array
    {
        $out = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $basePath,
                \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            if (strtolower($file->getExtension()) !== 'json') {
                continue;
            }
            $out[] = $file->getPathname();
        }

        return $out;
    }

    /**
     * @param list<string> $absolutePaths
     * @return list<string>
     */
    protected function orderPathsForMerge(string $basePath, array $absolutePaths): array
    {
        $baseReal = realpath($basePath);
        if ($baseReal === false) {
            $baseReal = $basePath;
        }
        $baseReal = rtrim(str_replace('\\', '/', $baseReal), '/');

        $groups = [
            'common' => [],
            'api' => [],
            'mail' => [],
            'membership' => [],
            'view' => [],
            'other' => [],
        ];

        foreach ($absolutePaths as $path) {
            $norm = str_replace('\\', '/', $path);
            $real = realpath($path);
            if ($real !== false) {
                $norm = str_replace('\\', '/', $real);
            }

            if (!str_starts_with($norm, $baseReal)) {
                $groups['other'][] = $path;
                continue;
            }

            $rel = ltrim(substr($norm, strlen($baseReal)), '/');
            $parts = $rel === '' ? [] : explode('/', $rel);

            if (count($parts) === 1 && strtolower($parts[0]) === 'common.json') {
                $groups['common'][] = $path;
            } elseif (count($parts) === 1 && strtolower($parts[0]) === 'mail.json') {
                $groups['mail'][] = $path;
            } elseif (count($parts) === 1 && strtolower($parts[0]) === 'membership.json') {
                $groups['membership'][] = $path;
            } elseif (isset($parts[0]) && strtolower($parts[0]) === 'api' && count($parts) === 2 && str_ends_with(strtolower($parts[1]), '.json')) {
                $groups['api'][] = $path;
            } elseif (isset($parts[0]) && strtolower($parts[0]) === 'view' && count($parts) === 2 && str_ends_with(strtolower($parts[1]), '.json')) {
                $groups['view'][] = $path;
            } else {
                $groups['other'][] = $path;
            }
        }

        $sortByBasename = static function (string $a, string $b): int {
            return strcasecmp(basename($a), basename($b));
        };

        usort($groups['api'], $sortByBasename);
        usort($groups['view'], $sortByBasename);
        usort($groups['other'], $sortByBasename);

        return array_merge(
            $groups['common'],
            $groups['api'],
            $groups['mail'],
            $groups['membership'],
            $groups['view'],
            $groups['other']
        );
    }

    /**
     * @param array<string, mixed> $bundle
     * @return array<string, mixed>
     */
    protected function mergeJsonFileIntoBundle(array $bundle, string $path): array
    {
        if (!is_readable($path)) {
            return $bundle;
        }
        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return $bundle;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return $bundle;
        }

        return $this->mergeMessageBundles($bundle, $data);
    }

    /**
     * @param array<string, mixed> $dir
     * @return array<string, mixed>
     */
    protected function scanDirectory(string $dir): array
    {
        $result = [];
        if (!is_dir($dir)) {
            return $result;
        }

        $items = scandir($dir);
        if ($items === false) {
            return $result;
        }

        sort($items, SORT_STRING);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
            $key = pathinfo($item, PATHINFO_FILENAME);

            if (is_dir($fullPath)) {
                $result[$key] = $this->scanDirectory($fullPath);
            } elseif (strtolower((string) pathinfo($item, PATHINFO_EXTENSION)) === 'json') {
                $raw = file_get_contents($fullPath);
                if ($raw === false || $raw === '') {
                    continue;
                }
                $data = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    $result[$key] = $data;
                }
            }
        }

        return $result;
    }

    /**
     * 深度合併關聯陣列；清單陣列與純量值以 $over 覆蓋 $base。
     *
     * @param array<string, mixed> $base
     * @param array<string, mixed> $over
     * @return array<string, mixed>
     */
    protected function mergeMessageBundles(array $base, array $over): array
    {
        foreach ($over as $key => $value) {
            if (
                isset($base[$key])
                && is_array($base[$key])
                && is_array($value)
                && !self::isListArray($base[$key])
                && !self::isListArray($value)
            ) {
                /** @var array<string, mixed> $b */
                $b = $base[$key];
                /** @var array<string, mixed> $v */
                $v = $value;
                $base[$key] = $this->mergeMessageBundles($b, $v);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * @param array<mixed> $a
     */
    protected static function isListArray(array $a): bool
    {
        if ($a === []) {
            return true;
        }

        $i = 0;
        foreach ($a as $k => $_) {
            if ($k !== $i++) {
                return false;
            }
        }

        return true;
    }
}
