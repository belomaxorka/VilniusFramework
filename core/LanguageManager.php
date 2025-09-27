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
            return null; // Let Lang::setLang() handle auto-detection
        } elseif (self::isValidLanguage($defaultLang)) {
            return $defaultLang;
        }

        return null; // Use Lang class fallback
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
