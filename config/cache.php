<?php declare(strict_types=1);

/**
 * Cache Configuration
 *
 * Supported drivers: memory, redis, file
 */

return [
    /**
     * Default cache driver
     */
    'driver' => 'memory',

    /**
     * Redis connection settings
     */
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
    ]
];