<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Config;

class LanguageController extends Controller
{
    private const SUPPORTED_LOCALES = ['zh_HK', 'en_US'];

    public function switch(): void
    {
        $lang = isset($_GET['lang']) ? trim((string) $_GET['lang']) : '';
        if (in_array($lang, self::SUPPORTED_LOCALES, true)) {
            $_SESSION['language'] = $lang;
            $_SESSION['locale'] = $lang;
        }

        $redirect = isset($_GET['redirect']) ? (string) $_GET['redirect'] : '/';
        $safeRedirect = $this->sanitizeRedirect($redirect) ?? '/';
        $this->redirect($safeRedirect);
    }

    public static function supportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }
}

