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
            return strtolower(substr($langs[0], 0, 2));
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

        $file = ROOT . "/lang/$lang.php";
        if (is_file($file)) {
            self::$messages[$lang] = include $file;
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
        $message = self::$messages[self::$currentLang][$key]
            ?? self::$messages[self::$fallbackLang][$key]
            ?? $key;

        // Replace placeholders
        foreach ($params as $k => $v) {
            $message = str_replace(":$k", $v, $message);
        }

        return $message;
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
}
