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
        $defaultLang = Config::get('language.default', 'en');
        $fallbackLang = Config::get('language.fallback', 'en');

        Lang::setFallbackLang($fallbackLang);

        $language = self::determineLanguage($defaultLang);
        Lang::setLang($language);
    }

    /**
     * Determine which language to use
     *
     * Priority order:
     * 1. Explicitly set default language (if valid)
     * 2. Auto-detection from browser (if enabled)
     * 3. Fallback to 'en'
     *
     * @param string $defaultLang Default language from config
     * @return string|null Language code or null for auto-detection
     */
    protected static function determineLanguage(string $defaultLang): ?string
    {
        // Auto-detection enabled
        if ($defaultLang === 'auto' && Config::get('language.auto_detect', true)) {
            return null; // Let Lang::setLang() handle auto-detection
        }
        
        // Use specific default language if valid
        if ($defaultLang !== 'auto' && self::isValidLanguage($defaultLang)) {
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

        $supportedLanguages = array_keys(Config::get('language.supported', ['en' => 'English']));
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
        return array_keys(Config::get('language.supported', ['en' => 'English']));
    }

    /**
     * Get supported languages with names
     *
     * @return array<string, string> Language codes with display names
     */
    public static function getSupportedLanguagesWithNames(): array
    {
        return Config::get('language.supported', ['en' => 'English']);
    }

    /**
     * Get language display name
     *
     * @param string $lang Language code
     * @return string Display name
     */
    public static function getLanguageName(string $lang): string
    {
        $languages = Config::get('language.supported', ['en' => 'English']);
        return $languages[$lang] ?? $lang;
    }

    /**
     * Check if language is RTL (Right-to-Left)
     *
     * @param string|null $lang Language code (null = current language)
     * @return bool
     */
    public static function isRTL(?string $lang = null): bool
    {
        $lang = $lang ?? self::getCurrentLanguage();
        $rtlLanguages = Config::get('language.rtl_languages', []);
        return in_array($lang, $rtlLanguages, true);
    }

    /**
     * Get available language codes
     *
     * @return array<string> Available language codes from lang directory
     */
    public static function getAvailableLanguages(): array
    {
        if (!defined('LANG_DIR') || !is_dir(LANG_DIR)) {
            return [];
        }

        $languages = [];
        $files = glob(LANG_DIR . '/*.php');
        
        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            $langCode = basename($file, '.php');
            if (preg_match('/^[a-z]{2}$/', $langCode)) {
                $languages[] = $langCode;
            }
        }

        return $languages;
    }
}
