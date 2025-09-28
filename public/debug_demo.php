<?php declare(strict_types=1);

// Подключаем автозагрузчик
require_once __DIR__ . '/../vendor/autoload.php';

// Определяем константы
define('ROOT', dirname(__DIR__));
define('CONFIG_DIR', ROOT . '/config');
define('LOG_DIR', ROOT . '/storage/logs');

// Инициализируем систему
\Core\Core::init();

// Устанавливаем development окружение для демонстрации
\Core\Environment::set(\Core\Environment::DEVELOPMENT);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Bar Demo</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        .demo-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .demo-section h3 {
            margin-top: 0;
            color: #007bff;
        }
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            margin: 10px 0;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Debug Bar Demo</h1>
        
        <div class="demo-section">
            <h3>1. Основные дебаг-функции</h3>
            <p>Попробуйте эти функции - они появятся в дебаг-баре внизу страницы:</p>
            
            <button class="btn" onclick="testDump()">Test dump()</button>
            <button class="btn" onclick="testDd()">Test dd() - остановит выполнение</button>
            <button class="btn" onclick="testCollect()">Test collect()</button>
            <button class="btn" onclick="testBenchmark()">Test benchmark()</button>
            
            <div class="code">
                // Примеры использования:<br>
                dump(['name' => 'John', 'age' => 30], 'User Data');<br>
                collect($config, 'Configuration');<br>
                benchmark(function() { return expensiveOperation(); }, 'Database Query');
            </div>
        </div>

        <div class="demo-section">
            <h3>2. Дебаг-бар секции</h3>
            <p>Добавьте кастомные секции в дебаг-бар:</p>
            
            <button class="btn" onclick="addCustomSection()">Add Custom Section</button>
            <button class="btn" onclick="addUserInfo()">Add User Info</button>
            <button class="btn" onclick="addConfigData()">Add Config Data</button>
            
            <div class="code">
                debug_bar_section('Custom Data', $data, '📊');<br>
                debug_bar_section('User Info', ['id' => 1, 'name' => 'John'], '👤');
            </div>
        </div>

        <div class="demo-section">
            <h3>3. SQL запросы</h3>
            <p>Симулируем SQL запросы для дебаг-бара:</p>
            
            <button class="btn" onclick="simulateQueries()">Simulate SQL Queries</button>
            
            <div class="code">
                debug_bar_query('SELECT * FROM users WHERE id = ?', 0.045, [1]);<br>
                debug_bar_query('INSERT INTO logs (message) VALUES (?)', 0.023, ['User logged in']);
            </div>
        </div>

        <div class="demo-section">
            <h3>4. Логи</h3>
            <p>Добавьте логи в дебаг-бар:</p>
            
            <button class="btn" onclick="addLogs()">Add Log Messages</button>
            
            <div class="code">
                debug_bar_log('info', 'User logged in successfully');<br>
                debug_bar_log('warning', 'High memory usage detected', ['memory' => '128MB']);<br>
                debug_bar_log('error', 'Database connection failed', ['error' => 'Connection timeout']);
            </div>
        </div>

        <div class="demo-section">
            <h3>5. Производительность</h3>
            <p>Отслеживайте производительность:</p>
            
            <button class="btn" onclick="addPerformance()">Add Performance Metrics</button>
            
            <div class="code">
                $start = microtime(true);<br>
                // ... выполнение кода ...<br>
                debug_bar_performance('Database Query', microtime(true) - $start);
            </div>
        </div>

        <div class="demo-section">
            <h3>6. Информация о дебаг-баре</h3>
            <p>Дебаг-бар показывает:</p>
            <ul>
                <li>⏱️ Время выполнения страницы</li>
                <li>💾 Использование памяти</li>
                <li>📊 Количество SQL запросов</li>
                <li>📝 Количество логов</li>
                <li>🔧 Кастомные секции</li>
                <li>⚡ Метрики производительности</li>
            </ul>
            <p><strong>Кликните на дебаг-бар внизу страницы, чтобы развернуть детали!</strong></p>
        </div>
    </div>

    <script>
        function testDump() {
            fetch('?action=dump', {method: 'POST'});
            location.reload();
        }
        
        function testDd() {
            fetch('?action=dd', {method: 'POST'});
            location.reload();
        }
        
        function testCollect() {
            fetch('?action=collect', {method: 'POST'});
            location.reload();
        }
        
        function testBenchmark() {
            fetch('?action=benchmark', {method: 'POST'});
            location.reload();
        }
        
        function addCustomSection() {
            fetch('?action=custom_section', {method: 'POST'});
            location.reload();
        }
        
        function addUserInfo() {
            fetch('?action=user_info', {method: 'POST'});
            location.reload();
        }
        
        function addConfigData() {
            fetch('?action=config_data', {method: 'POST'});
            location.reload();
        }
        
        function simulateQueries() {
            fetch('?action=queries', {method: 'POST'});
            location.reload();
        }
        
        function addLogs() {
            fetch('?action=logs', {method: 'POST'});
            location.reload();
        }
        
        function addPerformance() {
            fetch('?action=performance', {method: 'POST'});
            location.reload();
        }
    </script>

<?php
// Обработка AJAX запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'dump':
            dump(['message' => 'Hello from dump()!', 'timestamp' => date('Y-m-d H:i:s')], 'Test Dump');
            break;
            
        case 'dd':
            dd(['message' => 'This will stop execution!', 'data' => [1, 2, 3]], 'Test DD');
            break;
            
        case 'collect':
            collect(['collected' => 'data', 'time' => microtime(true)], 'Collected Data');
            dump_all();
            break;
            
        case 'benchmark':
            $result = benchmark(function() {
                usleep(100000); // 100ms
                return 'Benchmark completed!';
            }, 'Test Benchmark');
            break;
            
        case 'custom_section':
            debug_bar_section('Custom Section', [
                'title' => 'My Custom Data',
                'items' => ['item1', 'item2', 'item3'],
                'timestamp' => time()
            ], '📊');
            break;
            
        case 'user_info':
            debug_bar_section('User Info', [
                'id' => 123,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'role' => 'admin',
                'last_login' => '2024-01-15 10:30:00'
            ], '👤');
            break;
            
        case 'config_data':
            debug_bar_section('Configuration', [
                'app_name' => 'My App',
                'version' => '1.0.0',
                'debug' => true,
                'environment' => 'development',
                'database' => [
                    'host' => 'localhost',
                    'port' => 3306,
                    'name' => 'myapp'
                ]
            ], '⚙️');
            break;
            
        case 'queries':
            debug_bar_query('SELECT * FROM users WHERE active = ?', 0.045, [1]);
            debug_bar_query('INSERT INTO logs (message, level) VALUES (?, ?)', 0.023, ['User action', 'info']);
            debug_bar_query('UPDATE users SET last_login = NOW() WHERE id = ?', 0.034, [123]);
            debug_bar_query('SELECT COUNT(*) FROM orders WHERE user_id = ?', 0.056, [123]);
            break;
            
        case 'logs':
            debug_bar_log('info', 'User logged in successfully', ['user_id' => 123]);
            debug_bar_log('warning', 'High memory usage detected', ['memory' => '128MB', 'limit' => '256MB']);
            debug_bar_log('error', 'Database connection failed', ['error' => 'Connection timeout', 'retries' => 3]);
            debug_bar_log('debug', 'Cache cleared successfully', ['cache_type' => 'user_sessions']);
            break;
            
        case 'performance':
            // Симулируем различные операции
            $start = microtime(true);
            usleep(50000); // 50ms
            debug_bar_performance('Database Connection', microtime(true) - $start, 'Time to establish DB connection');
            
            $start = microtime(true);
            usleep(30000); // 30ms
            debug_bar_performance('Cache Lookup', microtime(true) - $start, 'Time to check cache');
            
            $start = microtime(true);
            usleep(80000); // 80ms
            debug_bar_performance('File Processing', microtime(true) - $start, 'Time to process uploaded file');
            break;
    }
    
    exit; // Останавливаем выполнение для AJAX запросов
}

// Добавляем функцию debug_bar_performance если её нет
if (!function_exists('debug_bar_performance')) {
    function debug_bar_performance(string $name, float $time, ?string $description = null): void
    {
        if (Environment::isDebug()) {
            \Core\DebugBar::addPerformance($name, $time, $description);
        }
    }
}
?>
</body>
</html>
