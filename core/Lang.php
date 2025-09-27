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
                }

                if (!empty($languages)) {
                    return array_key_first($languages);
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
     * @param string $key Translation key
     * @param array $params Associative array of placeholders (['name' => 'John'])
     * @return string
     */
    public static function get(string $key, array $params = []): string
    {
        if (!isset(self::$messages[self::$currentLang][$key]) && !isset(self::$messages[self::$fallbackLang][$key])) {
            // TODO: Log missing language keys
        }

        $message = self::getNestedValue(self::$messages[self::$currentLang], $key)
            ?? self::getNestedValue(self::$messages[self::$fallbackLang], $key)
            ?? $key;

        // Replace placeholders
        if (!empty($params)) {
            $search = array_map(fn($k) => ":$k", array_keys($params));
            $replace = array_values($params);
            $message = str_replace($search, $replace, $message);
        }

        return $message;
    }

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

    public static function has(string $key): bool
    {
        return isset(self::$messages[self::$currentLang][$key])
            || isset(self::$messages[self::$fallbackLang][$key]);
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
}
