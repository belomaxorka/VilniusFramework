<?php declare(strict_types=1);

namespace Core;

/**
 * Multilingual System
 * 
 * Handles translations, language detection, and localization.
 * 
 * @package Core
 */
class Lang
{
    protected static array $messages = [];
    protected static string $currentLang = 'en';
    protected static string $fallbackLang = 'en';

    /**
     * Initialize language system from configuration
     *
     * @return void
     */
    public static function init(): void
    {
        $defaultLang = Config::get('language.default', 'en');
        $fallbackLang = Config::get('language.fallback', 'en');

        self::setFallbackLang($fallbackLang);

        // Determine language: use specific or auto-detect
        if ($defaultLang === 'auto') {
            self::setLang(null); // Auto-detection
        } elseif (self::isValidLanguage($defaultLang)) {
            self::setLang($defaultLang);
        } else {
            self::setLang(null); // Fallback to auto-detection
        }
    }

    /**
     * Set the current language and load translations
     *
     * @param string|null $lang Language code (e.g., 'en', 'ru'). Null = auto-detect.
     * @param bool $validate Validate language against supported list
     * @return bool Success status
     */
    public static function setLang(?string $lang = null, bool $validate = false): bool
    {
        $targetLang = $lang ?? self::detectUserLang();

        // Validate if requested
        if ($validate && !self::isValidLanguage($targetLang)) {
            return false;
        }

        self::$currentLang = $targetLang;
        self::loadMessages(self::$currentLang);

        // Preload fallback language
        if (self::$currentLang !== self::$fallbackLang) {
            self::loadMessages(self::$fallbackLang);
        }

        return true;
    }

    /**
     * Detect user language from HTTP headers or default
     *
     * @return string
     */
    protected static function detectUserLang(): string
    {
        $autoDetectEnabled = Config::get('language.auto_detect', false);
        
        if (!$autoDetectEnabled || empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return self::$fallbackLang;
        }

        $acceptLanguages = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $languages = [];

        // Parse Accept-Language header
        preg_match_all(
            '/([a-z]{1,8}(?:-[a-z]{1,8})?)\s*(?:;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?/i',
            $acceptLanguages,
            $matches
        );

        if (empty($matches[1])) {
            return self::$fallbackLang;
        }

        // Extract language codes with quality scores
        foreach ($matches[1] as $i => $lang) {
            $quality = isset($matches[2][$i]) && $matches[2][$i] !== '' 
                ? (float)$matches[2][$i] 
                : 1.0;
            $langCode = strtolower(substr($lang, 0, 2));

            if (preg_match('/^[a-z]{2}$/', $langCode)) {
                $languages[$langCode] = $quality;
            }
        }

        // Sort by quality (highest first)
        arsort($languages);

        // Find first supported language
        $supportedLanguages = self::getSupportedLanguages();
        foreach (array_keys($languages) as $lang) {
            if (in_array($lang, $supportedLanguages, true)) {
                return $lang;
            }
        }

        // No supported language found
        return self::$fallbackLang;
    }

    /**
     * Load translation messages from a file
     *
     * @param string $lang Language code
     */
    protected static function loadMessages(string $lang): void
    {
        if (isset(self::$messages[$lang])) {
            return; // already loaded
        }

        if (!preg_match('/^[a-z]{2}$/', $lang)) {
            self::$messages[$lang] = [];
            return;
        }

        $file = LANG_DIR . "/$lang.php";
        if (is_file($file)) {
            $messages = include $file;
            self::$messages[$lang] = is_array($messages) ? $messages : [];
        } else {
            self::$messages[$lang] = [];
        }
    }

    /**
     * Get a translation by key with optional placeholders
     *
     * @param string $key Translation key (supports nested: 'user.profile.title')
     * @param array $params Associative array of placeholders (['name' => 'John'])
     * @return string
     */
    public static function get(string $key, array $params = []): string
    {
        $currentValue = self::getNestedValue(self::$messages[self::$currentLang] ?? [], $key);
        $fallbackValue = self::getNestedValue(self::$messages[self::$fallbackLang] ?? [], $key);
        
        if ($currentValue === null && $fallbackValue === null) {
            self::logMissingKey($key);
        }

        $message = $currentValue ?? $fallbackValue ?? $key;

        // Ensure we return a string (not array)
        if (!is_string($message)) {
            return $key;
        }

        // Replace placeholders
        if (!empty($params)) {
            $search = array_map(fn($k) => ":$k", array_keys($params));
            $replace = array_values($params);
            $message = str_replace($search, $replace, $message);
        }

        return $message;
    }

    /**
     * Check if a translation key exists (supports nested keys)
     *
     * @param string $key Translation key (e.g., 'user.profile.title')
     * @return bool
     */
    public static function has(string $key): bool
    {
        return self::getNestedValue(self::$messages[self::$currentLang] ?? [], $key) !== null
            || self::getNestedValue(self::$messages[self::$fallbackLang] ?? [], $key) !== null;
    }

    /**
     * Get all messages for current language
     *
     * @return array
     */
    public static function all(): array
    {
        return self::$messages[self::$currentLang] ?? [];
    }

    /**
     * Get current language code
     *
     * @return string
     */
    public static function getCurrentLang(): string
    {
        return self::$currentLang;
    }

    /**
     * Get fallback language code
     *
     * @return string
     */
    public static function getFallbackLang(): string
    {
        return self::$fallbackLang;
    }

    /**
     * Set fallback language
     *
     * @param string $lang Language code
     * @return void
     */
    public static function setFallbackLang(string $lang): void
    {
        self::$fallbackLang = $lang;
    }

    /**
     * Get all loaded languages
     *
     * @return array<string> Array of loaded language codes
     */
    public static function getLoadedLanguages(): array
    {
        return array_keys(self::$messages);
    }

    /**
     * Get all messages for a specific language
     *
     * @param string|null $lang Language code (null = current language)
     * @return array
     */
    public static function getMessages(?string $lang = null): array
    {
        $lang = $lang ?? self::$currentLang;
        return self::$messages[$lang] ?? [];
    }

    /**
     * Add or override translations at runtime
     *
     * @param string $lang Language code
     * @param array $messages Messages to add/override
     * @return void
     */
    public static function addMessages(string $lang, array $messages): void
    {
        if (!isset(self::$messages[$lang])) {
            self::$messages[$lang] = [];
        }
        
        self::$messages[$lang] = array_merge(self::$messages[$lang], $messages);
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

        $supportedLanguages = self::getSupportedLanguages();
        return in_array($lang, $supportedLanguages, true);
    }

    /**
     * Get supported language codes
     *
     * @return array<string> Supported language codes
     */
    public static function getSupportedLanguages(): array
    {
        $supported = Config::get('language.supported', ['en' => 'English']);
        return array_keys($supported);
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
     * Get available language codes from lang directory
     *
     * @return array<string> Available language codes
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

    /**
     * Check if language is RTL (Right-to-Left)
     *
     * @param string|null $lang Language code (null = current language)
     * @return bool
     */
    public static function isRTL(?string $lang = null): bool
    {
        $lang = $lang ?? self::$currentLang;
        $rtlLanguages = Config::get('language.rtl_languages', []);
        return in_array($lang, $rtlLanguages, true);
    }

    /**
     * Reset language state (useful for testing)
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$messages = [];
        self::$currentLang = 'en';
        self::$fallbackLang = 'en';
    }

    /**
     * Log missing translation key
     *
     * @param string $key Missing key
     */
    protected static function logMissingKey(string $key): void
    {
        if (!Config::get('language.log_missing', false)) {
            return;
        }

        $message = sprintf(
            'Missing translation key "%s" for language "%s" (fallback: "%s")',
            $key,
            self::$currentLang,
            self::$fallbackLang
        );
        
        if (class_exists('\\Core\\Logger')) {
            \Core\Logger::warning($message, ['context' => 'language']);
        } else {
            error_log($message);
        }
    }

    /**
     * Get a value from nested array using dot notation
     *
     * @param array $array Source array
     * @param string $key Key in dot notation (e.g., 'user.profile.title')
     * @return mixed|null
     */
    private static function getNestedValue(array $array, string $key)
    {
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return null;
            }
            $value = $value[$k];
        }

        return $value;
    }
}