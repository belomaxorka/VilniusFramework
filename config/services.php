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
        \Core\Router::class => \Core\Router::class,
        \Core\Database::class => \Core\Database::class,
        \Core\TemplateEngine::class => \Core\TemplateEngine::class,
        \Core\Session::class => \Core\Session::class,
        \Core\Logger::class => \Core\Logger::class,
        \Core\Cache\CacheManager::class => function ($container) {
            $config = \Core\Config::get('cache');
            return new \Core\Cache\CacheManager($config);
        },
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
        // Примеры интерфейсов и их реализаций:
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
        'router' => \Core\Router::class,
        'db' => \Core\Database::class,
        'view' => \Core\TemplateEngine::class,
        'session' => \Core\Session::class,
        'logger' => \Core\Logger::class,
        'cache' => \Core\Cache\CacheManager::class,
    ],
];
