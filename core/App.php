<?php

namespace Core;

class App
{
    protected static array $config = [];

    public static function init(): void
    {
        // Load configuration files
        foreach (glob(__DIR__ . '/../config/*.php') as $file) {
            $key = basename($file, '.php');
            self::$config[$key] = require $file;
        }
    }

    public static function config(string $key, $default = null)
    {
        return self::$config[$key] ?? $default;
    }
}
