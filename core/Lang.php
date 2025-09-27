<?php declare(strict_types=1);

namespace Core;

class Lang
{
    protected static array $messages = [];
    protected static string $currentLang = 'en';

    public static function setLang(string $lang): void
    {
        self::$currentLang = $lang;
        self::loadMessages($lang);
    }

    protected static function loadMessages(string $lang): void
    {
        $file = __DIR__ . "/lang/{$lang}.php";
        if (file_exists($file)) {
            self::$messages = include $file;
        } else {
            self::$messages = [];
        }
    }

    public static function get(string $key, string $default = '')
    {
        return self::$messages[$key] ?? $default;
    }
}

