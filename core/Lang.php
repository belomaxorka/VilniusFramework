<?php declare(strict_types=1);

namespace Core;

class Lang
{
    protected static array $messages = [];
    protected static string $currentLang = 'en';
    protected static string $fallbackLang = 'en';

    /**
     * Set the current language and load translations
     *
     * @param string|null $lang Language code (e.g., 'en', 'ru'). Null = auto-detect.
     */
    public static function setLang(?string $lang = null): void
    {
        self::$currentLang = $lang ?? self::detectUserLang();
        self::loadMessages(self::$currentLang);

        if (self::$currentLang !== self::$fallbackLang) {
            self::loadMessages(self::$fallbackLang); // preload fallback
        }
    }

    /**
     * Detect user language from HTTP headers or default
     *
     * @return string
     */
    protected static function detectUserLang(): string
    {
        $autoDetectEnabled = Config::get('language.auto_detect');
        if ($autoDetectEnabled && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $acceptLanguages = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $languages = [];

            preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)\s*(?:;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?/i', $acceptLanguages, $matches);

            if (!empty($matches[1])) {
                foreach ($matches[1] as $i => $lang) {
                    $quality = isset($matches[2][$i]) && $matches[2][$i] !== '' ? (float)$matches[2][$i] : 1.0;
                    $langCode = strtolower(substr($lang, 0, 2));

                    if (preg_match('/^[a-z]{2}$/', $langCode)) {
                        $languages[$langCode] = $quality;
                    }
                }

                // Sort by quality (highest first)
                arsort($languages);

                $supportedLanguages = Config::get('language.supported');
                if (!empty($supportedLanguages)) {
                    $supportedCodes = is_array($supportedLanguages) && isset($supportedLanguages[0])
                        ? $supportedLanguages
                        : array_keys($supportedLanguages);

                    // Return first supported language
                    foreach (array_keys($languages) as $lang) {
                        if (in_array($lang, $supportedCodes, true)) {
                            return $lang;
                        }
                    }
                    
                    // No supported language found in browser preferences
                    return self::$fallbackLang;
                }

                // If no supported languages configured, use first from browser
                if (!empty($languages)) {
                    $firstLang = array_key_first($languages);
                    // But validate it's a proper language code
                    if (preg_match('/^[a-z]{2}$/', $firstLang)) {
                        return $firstLang;
                    }
                }
            }
        }

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
     * Log missing translation key
     *
     * @param string $key Missing key
     */
    protected static function logMissingKey(string $key): void
    {
        if (Config::get('language.log_missing', false)) {
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

    /**
     * Get the current language code
     *
     * @return string
     */
    public static function getCurrentLang(): string
    {
        return self::$currentLang;
    }

    /**
     * Get all loaded languages
     *
     * Returns an array of all language codes that have been loaded.
     *
     * @return array<string> Array of loaded language codes
     */
    public static function getLoadedLanguages(): array
    {
        return array_keys(self::$messages);
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

    public static function all(): array
    {
        return self::$messages[self::$currentLang] ?? [];
    }

    public static function getFallbackLang(): string
    {
        return self::$fallbackLang;
    }

    public static function setFallbackLang(string $lang): void
    {
        self::$fallbackLang = $lang;
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
}
