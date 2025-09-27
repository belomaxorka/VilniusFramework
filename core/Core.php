<?php declare(strict_types=1);

namespace Core;

final class Core
{
    public static function init(): void
    {
        self::initConfigLoader();
        self::initializeLangManager();
        self::initializeDatabase();
    }

    private static function initConfigLoader(): void
    {
        Config::load(CONFIG_DIR);
    }

    private static function initializeLangManager(): void
    {
        LanguageManager::init();
    }

    private static function initializeDatabase(): void
    {
        Database::init();
    }
}
