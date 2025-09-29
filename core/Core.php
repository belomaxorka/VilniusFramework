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

        // Настраиваем логгер для дебага
        $logFile = LOG_DIR . '/debug.log';
        if (!is_dir(LOG_DIR)) {
            mkdir(LOG_DIR, 0755, true);
        }

        Logger::addHandler(new Logger\FileHandler($logFile));
        Logger::setMinLevel(Environment::getConfig()['log_level']);
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

    private static function initializeTemplateEngine(): void
    {
        // Инициализация шаблонизатора будет происходить по требованию
        // через статический метод TemplateEngine::getInstance()
    }
}
