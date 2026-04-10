<?php

namespace App\Core;

/**
 * Loads locale JSON into a nested tree (filename / folder names as keys) or a flat merged message bundle.
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
     * Load one locale as a nested tree: each .json becomes a key (basename); subfolders recurse under folder name.
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
     * Load locale and deep-merge all JSON files into one bundle (same semantics as legacy Config merge).
     * Merge order: common.json, then api/*.json (sorted), mail.json, membership.json, view/*.json (sorted), then any other JSON under the locale (sorted).
     *
     * @return array<string, mixed>
     */
    public function loadMergedBundle(string $lang): array
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $lang)) {
            $lang = 'zh_HK';
        }

        $basePath = $this->langPath . DIRECTORY_SEPARATOR . $lang;
        if (!is_dir($basePath)) {
            $basePath = $this->langPath . DIRECTORY_SEPARATOR . 'zh_HK';
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
     * Deep-merge associative arrays; list arrays and scalars from $over replace $base.
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
