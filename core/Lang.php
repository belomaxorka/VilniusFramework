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
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $detected = strtolower(substr(trim($langs[0]), 0, 2));

            if (preg_match('/^[a-z]{2}$/', $detected)) {
                return $detected;
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
            return;
        }

        if (!preg_match('/^[a-z]{2}$/', $lang)) {
            self::$messages[$lang] = [];
            return;
        }

        $file = ROOT . "/lang/$lang.php";
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
        $currentExists = isset(self::$messages[self::$currentLang][$key]);
        $fallbackExists = isset(self::$messages[self::$fallbackLang][$key]);

        if (!$currentExists && !$fallbackExists) {
            // TODO: Log missing language keys
        }

        $message = self::getNestedValue(self::$messages[self::$currentLang], $key)
            ?? self::getNestedValue(self::$messages[self::$fallbackLang], $key)
            ?? $key;

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

    public static function has(string $key): bool
    {
        return isset(self::$messages[self::$currentLang][$key])
            || isset(self::$messages[self::$fallbackLang][$key]);
    }

    public static function all(): array
    {
        return self::$messages[self::$currentLang] ?? [];
    }

    public static function setFallbackLang(string $lang): void
    {
        self::$fallbackLang = $lang;
    }
}
