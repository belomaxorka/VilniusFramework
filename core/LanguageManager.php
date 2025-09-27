<?php declare(strict_types=1);

namespace Core;

/**
 * Language Manager
 *
 * Handles language detection, validation and initialization logic
 *
 * @package Core
 */
class LanguageManager
{
    /**
     * Initialize language system
     *
     * @return void
     */
    public static function init(): void
    {
        $defaultLang = Config::get('app.default_language', 'en');
        $fallbackLang = Config::get('app.fallback_language', 'en');

        Lang::setFallbackLang($fallbackLang);

        $language = self::determineLanguage($defaultLang);
        Lang::setLang($language);
    }

    /**
     * Determine which language to use
     *
     * @param string $defaultLang Default language from config
     * @return string|null Language code or null for auto-detection
     */
    protected static function determineLanguage(string $defaultLang): ?string
    {
        // Priority 1: URL parameter
        // if (!empty($_GET['lang']) && self::isValidLanguage($_GET['lang'])) {
        //     $_SESSION['user_lang'] = $_GET['lang'];
        //     return $_GET['lang'];
        // }

        // Priority 2: Session
        // if (!empty($_SESSION['user_lang']) && self::isValidLanguage($_SESSION['user_lang'])) {
        //     return $_SESSION['user_lang'];
        // }

        // Priority 3: Auto-detection or specific default
        if ($defaultLang === 'auto') {
            $detectedLang = self::detectBrowserLanguage();
            if ($detectedLang && self::isValidLanguage($detectedLang)) {
                return $detectedLang;
            }
        } elseif (self::isValidLanguage($defaultLang)) {
            return $defaultLang;
        }

        return null;
    }

    /**
     * Detect browser language from HTTP headers
     *
     * @return string|null Detected language code
     */
    protected static function detectBrowserLanguage(): ?string
    {
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        $acceptLanguages = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $languages = [];

        preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)\s*(?:;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?/i', $acceptLanguages, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $i => $lang) {
                $quality = isset($matches[2][$i]) && $matches[2][$i] !== '' ? (float)$matches[2][$i] : 1.0;
                $languages[strtolower(substr($lang, 0, 2))] = $quality;
            }

            arsort($languages);

            foreach (array_keys($languages) as $lang) {
                if (self::isValidLanguage($lang)) {
                    return $lang;
                }
            }
        }

        return null;
    }

    /**
     * Check if language is valid/supported
     *
     * @param string $lang Language code
     * @return bool
     */
    public static function isValidLanguage(string $lang): bool
    {
        if (!preg_match('/^[a-z]{2}$/', $lang)) {
            return false;
        }

        $supportedLanguages = Config::get('app.supported_languages', ['en']);
        return in_array($lang, $supportedLanguages, true);
    }

    /**
     * Change language on the fly
     *
     * @param string $lang Language code
     * @return bool Success status
     */
    public static function setLanguage(string $lang): bool
    {
        if (!self::isValidLanguage($lang)) {
            return false;
        }

        Lang::setLang($lang);
        // $_SESSION['user_lang'] = $lang;

        return true;
    }

    /**
     * Get current language
     *
     * @return string Current language code
     */
    public static function getCurrentLanguage(): string
    {
        return Lang::getCurrentLang();
    }

    /**
     * Get supported languages
     *
     * @return array<string> Supported language codes
     */
    public static function getSupportedLanguages(): array
    {
        return Config::get('app.supported_languages', ['en']);
    }
}
