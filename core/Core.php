<?php declare(strict_types=1);

namespace Core;

use Core\Contracts\ConfigInterface;

final class Core
{
    public static function init(): void
    {
        self::initEnvironment();
        self::initContainer();
        self::initConfigLoader();
        self::initDebugSystem();
        self::initializeLang();
        self::initializeEmailer();
    }

    private static function initEnvironment(): void
    {
        Env::load(ROOT . '/.env', true);
    }

    /**
     * Инициализация контейнера и загрузка сервисов
     */
    private static function initContainer(): void
    {
        $container = Container::getInstance();

        // Загружаем services.php
        $servicesFile = CONFIG_DIR . '/services.php';
        if (!file_exists($servicesFile)) {
            throw new \RuntimeException("Services configuration file not found: {$servicesFile}");
        }

        $services = require $servicesFile;

        // Регистрируем сервисы
        foreach ($services['singletons'] ?? [] as $abstract => $concrete) {
            $container->singleton($abstract, $concrete);
        }

        foreach ($services['bindings'] ?? [] as $abstract => $concrete) {
            $container->bind($abstract, $concrete);
        }

        foreach ($services['aliases'] ?? [] as $alias => $abstract) {
            $container->alias($alias, $abstract);
        }
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

        // Получаем ConfigInterface из контейнера (теперь он уже зарегистрирован!)
        $config = Container::getInstance()->make(ConfigInterface::class);

        // Try to load from cache first (in production only)
        if ($environment === 'production' && $config->loadCached($cachePath)) {
            return;
        }

        // Load from files
        $config->load(CONFIG_DIR, $environment);

        // Cache for next time (in production only)
        if ($environment === 'production') {
            try {
                $config->cache($cachePath);
            } catch (\Exception $e) {
                // Игнорируем ошибки кеширования
            }
        }
    }

    private static function initializeLang(): void
    {
        Lang::init();
    }

    private static function initializeEmailer(): void
    {
        Emailer::init();
    }
}
