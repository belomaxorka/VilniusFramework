<?php declare(strict_types=1);

/**
 * Middleware Configuration
 * 
 * Здесь регистрируются алиасы для middleware,
 * которые можно использовать в роутах
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Middleware Aliases
    |--------------------------------------------------------------------------
    |
    | Здесь вы можете зарегистрировать алиасы для middleware классов.
    | Это позволяет использовать короткие имена вместо полных имен классов.
    |
    */
    'aliases' => [
        'auth' => \Core\Middleware\AuthMiddleware::class,
        'guest' => \Core\Middleware\GuestMiddleware::class,
        'csrf' => \Core\Middleware\CsrfMiddleware::class,
        'throttle' => \Core\Middleware\ThrottleMiddleware::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware, которые применяются ко всем роутам автоматически.
    | Они выполняются в указанном порядке.
    |
    */
    'global' => [
        // Debug Toolbar (только в debug режиме)
        \Core\Middleware\DebugToolbarMiddleware::class,
        
        // Примеры:
        // 'throttle',
    ],

    /*
    |--------------------------------------------------------------------------
    | CSRF Exclusions
    |--------------------------------------------------------------------------
    |
    | URI пути, которые исключены из CSRF проверки.
    | Поддерживаются wildcards (*).
    |
    */
    'csrf_except' => [
        'api/*',           // Все API роуты
        'webhook/*',       // Вебхуки
        // 'callback/*',   // Коллбэки от внешних сервисов
    ],
];

