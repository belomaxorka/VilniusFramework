<?php declare(strict_types=1);

namespace Core;

final class Core
{
    public static function init(): void
    {
        HelperLoader::loadHelper('basic');
        self::initEnvironment();
        self::initDebugSystem();
        self::initConfigLoader();
        self::initializeLangManager();
        self::initializeDatabase();
        self::initializeTemplateEngine();
    }

    private static function initEnvironment(): void
    {
        Env::load(ROOT . '/.env', true);
    }

    private static function initDebugSystem(): void
    {
        // Загружаем дебаг хелпер
        HelperLoader::loadHelper('debug');

        // Регистрируем обработчик ошибок
        ErrorHandler::register();

        // Регистрируем shutdown handler для автоматического вывода debug данных
        Debug::registerShutdownHandler();

        // Убеждаемся, что директория для логов существует
        if (!is_dir(LOG_DIR)) {
            mkdir(LOG_DIR, 0755, true);
        }

        // Инициализируем логгер из конфигурации
        // Он автоматически загрузит все настроенные драйверы
        Logger::init();
    }

    private static function initConfigLoader(): void
    {
        $environment = Env::get('APP_ENV', 'production');
        $cachePath = STORAGE_DIR . '/cache/config.php';

        // В production используем кэш для производительности
        if ($environment === 'production' && Config::isCached($cachePath)) {
            Config::loadCached($cachePath);
        } else {
            // В dev/testing загружаем напрямую с поддержкой окружения
            Config::load(CONFIG_DIR, $environment);

            // В production создаем/обновляем кэш после загрузки
            if ($environment === 'production') {
                // Убедимся, что директория для кэша существует
                $cacheDir = dirname($cachePath);
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0755, true);
                }
                Config::cache($cachePath);
            }
        }
    }

    private static function initializeLangManager(): void
    {
        Lang::init();
    }

    private static function initializeDatabase(): void
    {
        Database::init();
    }

    private static function initializeTemplateEngine(): void
    {
        // Инициализация шаблонизатора будет происходить по требованию
        // через статический метод TemplateEngine::getInstance()
    }
}
