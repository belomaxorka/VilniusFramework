<?php declare(strict_types=1);

/**
 * Vite Configuration
 *
 * Настройки интеграции с Vite dev сервером и production build
 */

return [
    /**
     * URL Vite dev сервера
     * 
     * В режиме разработки ассеты загружаются отсюда.
     * Должен соответствовать настройкам в vite.config.js
     * 
     * Примеры:
     * - 'http://localhost:5173'              (по умолчанию)
     * - 'http://torrentpier.loc:5173'        (для локального домена OSPanel)
     * - 'http://192.168.1.100:5173'          (для доступа с других устройств)
     */
    'dev_server_url' => env('VITE_DEV_SERVER_URL', 'http://localhost:5173'),

    /**
     * Путь к манифест-файлу
     * 
     * Относительно корня проекта
     */
    'manifest_path' => 'public/build/.vite/manifest.json',

    /**
     * Путь к hot-файлу
     * 
     * Создается Vite dev сервером при запуске
     */
    'hot_file' => 'public/hot',

    /**
     * Базовый путь к собранным ассетам
     * 
     * Относительно public директории
     */
    'build_path' => '/build',

    /**
     * Точки входа (entry points)
     * 
     * Должны соответствовать настройкам в vite.config.js
     */
    'entries' => [
        'app' => 'resources/js/app.js',
        // 'admin' => 'resources/js/admin.js',
    ],
];

