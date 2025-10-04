<?php declare(strict_types=1);

/**
 * Service Container Bindings
 *
 * Здесь регистрируются привязки для контейнера зависимостей.
 * Это позволяет внедрять зависимости в конструкторы контроллеров.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Singleton Bindings
    |--------------------------------------------------------------------------
    |
    | Сервисы, которые создаются один раз на весь lifecycle приложения.
    |
    */
    'singletons' => [
        // HTTP Service
        \Core\Contracts\HttpInterface::class => \Core\Services\HttpService::class,
        
        // Config Service (загрузка конфигурации происходит в Core::init())
        \Core\Contracts\ConfigInterface::class => function ($container) {
            return new \Core\Services\ConfigRepository();
        },
        
        // Logger Service (зависит от Config)
        \Core\Contracts\LoggerInterface::class => function ($container) {
            $config = $container->make(\Core\Contracts\ConfigInterface::class);
            $logger = new \Core\Services\LoggerService($config);
            $logger->init();
            return $logger;
        },
        
        // Session Manager (зависит от Http)
        \Core\Contracts\SessionInterface::class => function ($container) {
            $http = $container->make(\Core\Contracts\HttpInterface::class);
            return new \Core\Services\SessionManager($http);
        },
        
        // Database Manager (зависит от Config)
        \Core\Contracts\DatabaseInterface::class => function ($container) {
            $config = $container->make(\Core\Contracts\ConfigInterface::class);
            $dbConfig = $config->get('database', []);
            
            if (empty($dbConfig)) {
                throw new \RuntimeException('Database configuration not found');
            }
            
            return new \Core\Database\DatabaseManager($dbConfig);
        },
        
        // Router (зависит от Container)
        \Core\Router::class => function ($container) {
            $router = new \Core\Router();
            $router->setContainer($container);
            return $router;
        },
        
        // Template Engine (с внедрением логгера)
        \Core\TemplateEngine::class => function ($container) {
            $logger = $container->make(\Core\Contracts\LoggerInterface::class);
            
            return new \Core\TemplateEngine(
                templateDir: RESOURCES_DIR . '/views',
                cacheDir: STORAGE_DIR . '/cache/templates',
                logger: $logger
            );
        },
        
        // Cache Manager
        \Core\Contracts\CacheInterface::class => function ($container) {
            $config = $container->make(\Core\Contracts\ConfigInterface::class);
            $cacheConfig = $config->get('cache', []);
            return new \Core\Cache\CacheManager($cacheConfig);
        },
        
        // Backward compatibility
        \Core\Cache\CacheManager::class => function ($container) {
            return $container->make(\Core\Contracts\CacheInterface::class);
        },
        
        // Emailer
        \Core\Emailer::class => function ($container) {
            $config = $container->make(\Core\Contracts\ConfigInterface::class);
            $mailConfig = $config->get('mail', []);
            return new \Core\Emailer($mailConfig);
        },
        
        // Query Debugger
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
        // Request
        \Core\Request::class => function ($container) {
            return \Core\Request::capture();
        },
        
        // Response
        \Core\Response::class => \Core\Response::class,
        
        // Examples of interface bindings:
        // \App\Contracts\PaymentInterface::class => \App\Services\StripePaymentService::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Aliases
    |--------------------------------------------------------------------------
    |
    | Короткие алиасы для длинных имен классов.
    | Теперь алиасы указывают на интерфейсы, а не на конкретные классы.
    |
    */
    'aliases' => [
        // Core Services (указываем на интерфейсы)
        'http' => \Core\Contracts\HttpInterface::class,
        'config' => \Core\Contracts\ConfigInterface::class,
        'logger' => \Core\Contracts\LoggerInterface::class,
        'log' => \Core\Contracts\LoggerInterface::class,
        'session' => \Core\Contracts\SessionInterface::class,
        'db' => \Core\Contracts\DatabaseInterface::class,
        'database' => \Core\Contracts\DatabaseInterface::class,
        
        // Router
        'router' => \Core\Router::class,
        
        // Template Engine
        'view' => \Core\TemplateEngine::class,
        'template' => \Core\TemplateEngine::class,
        
        // Cache
        'cache' => \Core\Contracts\CacheInterface::class,
        
        // Email
        'email' => \Core\Emailer::class,
        'emailer' => \Core\Emailer::class,
        'mailer' => \Core\Emailer::class,
        
        // Utilities (оставляем для обратной совместимости)
        'cookie' => \Core\Cookie::class,
        'path' => \Core\Path::class,
        'lang' => \Core\Lang::class,
        'debug' => \Core\Debug::class,
        'env' => \Core\Environment::class,
    ],
];
