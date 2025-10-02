<?php declare(strict_types=1);

use Core\Env;

/**
 * Cache Configuration
 *
 * Поддерживаемые драйверы: array, file, apcu, redis, memcached
 */

return [
    /**
     * Драйвер кэша по умолчанию
     */
    'default' => Env::get('CACHE_DRIVER', 'file'),

    /**
     * Настройки хранилищ кэша
     */
    'stores' => [
        /**
         * Array Driver - хранение в памяти (только для текущего запроса)
         */
        'array' => [
            'driver' => 'array',
            'prefix' => 'vilnius_',
        ],

        /**
         * File Driver - хранение в файлах
         */
        'file' => [
            'driver' => 'file',
            'path' => CACHE_DIR . '/data',
            'prefix' => 'vilnius_',
            'ttl' => 3600, // 1 час по умолчанию
        ],

        /**
         * APCu Driver - хранение в APCu (in-memory)
         */
        'apcu' => [
            'driver' => 'apcu',
            'prefix' => 'vilnius_',
            'ttl' => 3600,
        ],

        /**
         * Redis Driver - хранение в Redis
         */
        'redis' => [
            'driver' => 'redis',
            'host' => Env::get('REDIS_HOST', '127.0.0.1'),
            'port' => (int) Env::get('REDIS_PORT', 6379),
            'password' => Env::get('REDIS_PASSWORD'),
            'database' => (int) Env::get('REDIS_CACHE_DB', 1),
            'timeout' => 2.5,
            'prefix' => 'vilnius_cache_',
            'ttl' => 3600,
        ],

        /**
         * Memcached Driver - хранение в Memcached
         */
        'memcached' => [
            'driver' => 'memcached',
            'servers' => [
                [
                    'host' => Env::get('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => (int) Env::get('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
            'prefix' => 'vilnius_',
            'ttl' => 3600,
            'options' => [
                // Дополнительные опции Memcached
            ],
        ],
    ],

    /**
     * Префикс для всех ключей кэша (общий)
     */
    'prefix' => Env::get('CACHE_PREFIX', 'vilnius'),
];