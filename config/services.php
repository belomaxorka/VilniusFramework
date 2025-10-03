<?php declare(strict_types=1);

/**
 * Service Container Bindings
 *
 * Здесь вы можете регистрировать привязки для контейнера зависимостей.
 * Это позволяет внедрять зависимости в конструкторы контроллеров.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Singleton Bindings
    |--------------------------------------------------------------------------
    |
    | Сервисы, которые создаются один раз на весь lifecycle приложения.
    | Один экземпляр на все приложение.
    |
    */
    'singletons' => [
        // Core Framework Services
        \Core\Router::class => \Core\Router::class,
        \Core\Database::class => \Core\Database::class,
        \Core\TemplateEngine::class => \Core\TemplateEngine::class,
        \Core\Session::class => \Core\Session::class,
        \Core\Logger::class => \Core\Logger::class,
        
        // Cache Manager with configuration
        \Core\Cache\CacheManager::class => function ($container) {
            $config = \Core\Config::get('cache');
            return new \Core\Cache\CacheManager($config);
        },
        
        // Emailer with configuration
        \Core\Emailer::class => function ($container) {
            $config = \Core\Config::get('mail');
            return new \Core\Emailer($config);
        },
        
        // Configuration & Environment
        \Core\Config::class => \Core\Config::class,
        \Core\Environment::class => \Core\Environment::class,
        \Core\Env::class => \Core\Env::class,
        
        // Utilities
        \Core\Cookie::class => \Core\Cookie::class,
        \Core\Path::class => \Core\Path::class,
        \Core\Lang::class => \Core\Lang::class,
        \Core\Http::class => \Core\Http::class,
        
        // Debug & Profiling
        \Core\DebugToolbar::class => \Core\DebugToolbar::class,
        \Core\Debug::class => \Core\Debug::class,
        \Core\MemoryProfiler::class => \Core\MemoryProfiler::class,
        \Core\QueryDebugger::class => \Core\QueryDebugger::class,
        
        // Validation
        \Core\Validation\RouteParameterValidator::class => \Core\Validation\RouteParameterValidator::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Regular Bindings
    |--------------------------------------------------------------------------
    |
    | Обычные привязки. Новый экземпляр создается при каждом запросе.
    |
    */
    'bindings' => [
        // Core services
        \Core\Request::class => function ($container) {
            return \Core\Request::capture();
        },
        \Core\Response::class => \Core\Response::class,
        
        // Examples of interface bindings:
        // \App\Contracts\PaymentInterface::class => \App\Services\StripePaymentService::class,
        // \App\Contracts\CacheInterface::class => \App\Services\RedisCacheService::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Aliases
    |--------------------------------------------------------------------------
    |
    | Короткие алиасы для длинных имен классов.
    |
    */
    'aliases' => [
        // Core
        'router' => \Core\Router::class,
        'db' => \Core\Database::class,
        'database' => \Core\Database::class,
        
        // Views & Templates
        'view' => \Core\TemplateEngine::class,
        'template' => \Core\TemplateEngine::class,
        
        // Session & Auth
        'session' => \Core\Session::class,
        
        // Logging & Debug
        'logger' => \Core\Logger::class,
        'log' => \Core\Logger::class,
        'debug' => \Core\Debug::class,
        
        // Cache
        'cache' => \Core\Cache\CacheManager::class,
        
        // Email
        'email' => \Core\Emailer::class,
        'emailer' => \Core\Emailer::class,
        'mailer' => \Core\Emailer::class,
        
        // Configuration
        'config' => \Core\Config::class,
        'env' => \Core\Environment::class,
        
        // Utilities
        'cookie' => \Core\Cookie::class,
        'path' => \Core\Path::class,
        'lang' => \Core\Lang::class,
        'http' => \Core\Http::class,
    ],
];
