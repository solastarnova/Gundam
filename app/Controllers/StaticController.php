<?php

namespace App\Controllers;

use App\Core\Controller;

/** 渲染多語系靜態內容頁。 */
class StaticController extends Controller
{
    public function faq(): void
    {
        $this->renderLocalizedStatic('faq', 'static_faq');
    }

    public function about(): void
    {
        $this->renderLocalizedStatic('about', 'static_about');
    }

    public function privacy(): void
    {
        $this->renderLocalizedStatic('privacy', 'static_privacy');
    }

    public function terms(): void
    {
        $this->renderLocalizedStatic('terms', 'static_terms');
    }

    private function renderLocalizedStatic(string $slug, string $titleKey): void
    {
        $locale = $this->resolveStaticLocale();
        $fallbackLocale = 'zh_HK';
        $view = $this->resolveStaticViewPath($locale, $slug, $fallbackLocale);
        $this->render($view, ['title' => $this->titleWithSite($titleKey), 'head_extra_css' => []]);
    }

    private function resolveStaticLocale(): string
    {
        $raw = $_SESSION['language'] ?? $_SESSION['locale'] ?? '';
        if (!is_string($raw) || $raw === '') {
            return 'zh_HK';
        }

        return preg_match('/^[a-zA-Z0-9_]+$/', $raw) === 1 ? $raw : 'zh_HK';
    }

    private function resolveStaticViewPath(string $locale, string $slug, string $fallbackLocale): string
    {
        $localizedPath = 'static/' . $locale . '/' . $slug;
        if ($this->staticViewExists($localizedPath)) {
            return $localizedPath;
        }

        $fallbackPath = 'static/' . $fallbackLocale . '/' . $slug;
        if ($this->staticViewExists($fallbackPath)) {
            return $fallbackPath;
        }

        // Backward compatibility for legacy flat static views.
        return 'static/' . $slug;
    }

    private function staticViewExists(string $view): bool
    {
        $viewFile = dirname(__DIR__, 2) . '/views/' . $view . '.php';
        return is_file($viewFile);
    }
}

