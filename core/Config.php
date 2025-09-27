<?php

namespace Core;

class Config
{
    protected static array $items = [];

    public static function load(string $path): void
    {
        foreach (glob($path . '/*.php') as $file) {
            $key = basename($file, '.php');
            self::$items[$key] = require $file;
        }
    }

    public static function get(string $key, $default = null)
    {
        if (str_contains($key, '.')) {
            $parts = explode('.', $key);
            $value = self::$items;

            foreach ($parts as $part) {
                if (is_array($value) && array_key_exists($part, $value)) {
                    $value = $value[$part];
                } else {
                    return $default;
                }
            }

            return $value;
        }

        return self::$items[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        self::$items[$key] = $value;
    }

    public static function all(): array
    {
        return self::$items;
    }
}

