<?php declare(strict_types=1);

namespace Core;

final class App
{
    public static function init(): void
    {
        // Load configuration files
        Config::load(ROOT . '/config');

        // Initialize language system
        LanguageManager::init();
    }
}
