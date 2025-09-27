<?php declare(strict_types=1);

namespace Core;

final class App
{
    public static function init(): void
    {
        // Load configuration files
        Config::load(CONFIG_DIR);

        // Initialize language system
        LanguageManager::init();
    }
}
