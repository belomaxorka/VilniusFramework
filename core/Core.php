<?php declare(strict_types=1);

namespace Core;

final class Core
{
    public static function init(): void
    {
        self::initEnvironment();
        self::initConfigLoader();
        self::initDebugSystem();
        self::initializeLang();
        self::initializeDatabase();
        self::initializeEmailer();
    }

    private static function initEnvironment(): void
    {
        Env::load(ROOT . '/.env', true);
    }

    private static function initDebugSystem(): void
    {
        ErrorHandler::register();
        Debug::registerShutdownHandler();
        Logger::init();
    }

    private static function initConfigLoader(): void
    {
        $environment = Env::get('APP_ENV', 'production');
        $cachePath = STORAGE_DIR . '/cache/config.php';

        // Try to load from cache first (in production only)
        if ($environment === 'production' && Config::loadCached($cachePath)) {
            return;
        }

        // Load from files
        Config::load(CONFIG_DIR, $environment);

        // Cache for next time (in production only)
        if ($environment === 'production') {
            Config::cache($cachePath);
        }
    }

    private static function initializeLang(): void
    {
        Lang::init();
    }

    private static function initializeDatabase(): void
    {
        Database::init();
    }

    private static function initializeEmailer(): void
    {
        Emailer::init();
    }
}
