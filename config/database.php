<?php declare(strict_types=1);

/**
 * Database Configuration
 *
 * Supported drivers: sqlite, mysql, pgsql
 */

return [
    /**
     * Default database connection
     */
    'default' => env('DB_CONNECTION', 'sqlite'),

    /**
     * Логирование SQL запросов
     * В debug режиме всегда включено для Debug Toolbar
     */
    'log_queries' => env('DB_LOG_QUERIES', true),

    /**
     * Порог медленных запросов (в миллисекундах)
     */
    'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 100),

    /**
     * Database connections
     */
    'connections' => [
        /**
         * SQLite connection
         */
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => STORAGE_DIR . '/database.sqlite',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 30,
            ]
        ],

        /**
         * MySQL connection
         */
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => (int)env('DB_PORT', 3306),
            'database' => env('DB_NAME', 'myapp'),
            'username' => env('DB_USER', 'root'),
            'password' => env('DB_PASS', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ],

        /**
         * PostgreSQL connection
         */
        'postgres' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => (int)env('DB_PORT', 5432),
            'database' => env('DB_NAME', 'myapp'),
            'username' => env('DB_USER', 'postgres'),
            'password' => env('DB_PASS', ''),
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        ]
    ]
];