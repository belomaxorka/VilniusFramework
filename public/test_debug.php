<?php declare(strict_types=1);

// Подключаем автозагрузчик
require_once __DIR__ . '/../vendor/autoload.php';

// Определяем константы
define('ROOT', dirname(__DIR__));
define('CONFIG_DIR', ROOT . '/config');
define('LOG_DIR', ROOT . '/storage/logs');

// Инициализируем систему
\Core\Core::init();

// Устанавливаем development окружение
\Core\Environment::set(\Core\Environment::DEVELOPMENT);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Bar Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2196f3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Debug Bar Test Page</h1>
        
        <div class="info">
            <strong>Debug Bar активен!</strong><br>
            Посмотрите вниз страницы - там должен быть дебаг-бар с информацией о производительности.
        </div>

        <h2>Тестируем дебаг-функции:</h2>
        
        <?php
        // Тестируем dump
        dump(['test' => 'data', 'number' => 42], 'Test Data');
        
        // Тестируем collect
        collect(['user' => 'John', 'role' => 'admin'], 'User Info');
        
        // Тестируем benchmark
        $result = benchmark(function() {
            usleep(100000); // 100ms
            return 'Benchmark completed!';
        }, 'Test Operation');
        
        // Добавляем секции в дебаг-бар
        debug_bar_section('Application Info', [
            'name' => 'My App',
            'version' => '1.0.0',
            'environment' => 'development'
        ], '📱');
        
        debug_bar_section('Server Info', [
            'php_version' => PHP_VERSION,
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit')
        ], '🖥️');
        
        // Симулируем SQL запросы
        debug_bar_query('SELECT * FROM users WHERE active = ?', 0.045, [1]);
        debug_bar_query('INSERT INTO logs (message) VALUES (?)', 0.023, ['Page loaded']);
        
        // Добавляем логи
        debug_bar_log('info', 'Page loaded successfully');
        debug_bar_log('debug', 'Debug bar initialized', ['timestamp' => time()]);
        
        // Добавляем метрики производительности
        debug_bar_performance('Page Load', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 'Total page load time');
        ?>
        
        <p>Страница загружена! Проверьте дебаг-бар внизу страницы.</p>
        
        <h3>Доступные функции:</h3>
        <ul>
            <li><code>dump($var, $label)</code> - выводит переменную</li>
            <li><code>dd($var, $label)</code> - выводит и останавливает выполнение</li>
            <li><code>collect($var, $label)</code> - собирает данные</li>
            <li><code>dump_all()</code> - показывает все собранные данные</li>
            <li><code>benchmark($callback, $label)</code> - измеряет время выполнения</li>
            <li><code>debug_bar_section($name, $data, $icon)</code> - добавляет секцию</li>
            <li><code>debug_bar_query($sql, $time, $bindings)</code> - добавляет SQL запрос</li>
            <li><code>debug_bar_log($level, $message, $context)</code> - добавляет лог</li>
        </ul>
    </div>
</body>
</html>
