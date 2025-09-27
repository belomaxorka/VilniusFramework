<?php declare(strict_types=1);

namespace Core;

final class App
{
    public static function init(): void
    {
        // Load configuration files
        self::initConfigLoader();

        // Initialize language system
        self::initializeLangManager();
    }

    private static function initConfigLoader(): void
    {
        Config::load(CONFIG_DIR);
    }

    private static function initializeLangManager(): void
    {
        LanguageManager::init();
    }
}
