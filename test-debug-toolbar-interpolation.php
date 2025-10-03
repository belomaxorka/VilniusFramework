<?php declare(strict_types=1);

/**
 * Тест интерполяции в Debug Toolbar
 * 
 * Проверяем что в Debug Toolbar показываются интерполированные сообщения,
 * а не плейсхолдеры {label}, {type} и т.д.
 */

require_once __DIR__ . '/core/bootstrap.php';

\Core\Core::init();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Toolbar - Interpolation Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #ff9800;
            padding-bottom: 10px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 20px 0;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .step {
            margin: 15px 0;
            padding: 15px;
            background: #fafafa;
            border-radius: 5px;
        }
        .check-item {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-left: 3px solid #4CAF50;
        }
        .wrong-item {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-left: 3px solid #f44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎨 Debug Toolbar - Interpolation Test</h1>
        
        <?php
        // Проверяем доступность dump server
        $serverAvailable = dump_server_available();
        
        if ($serverAvailable): ?>
            <div class="warning-box">
                <strong>⚠️ Внимание:</strong> Dump Server запущен!<br>
                Остановите его (Ctrl+C) для теста fallback логирования.
            </div>
        <?php else: ?>
            <div class="success-box">
                <strong>✅ Отлично!</strong> Dump Server недоступен.<br>
                Сейчас отправим dumps и проверим интерполяцию в Debug Toolbar.
            </div>
        <?php endif; ?>

        <h2>📤 Отправка тестовых dumps</h2>
        
        <?php
        // Отправляем несколько dumps с разными данными
        $user = [
            'id' => 999,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];
        
        server_dump($user, 'User Data');
        
        $config = [
            'app' => 'Vilnius',
            'debug' => true,
        ];
        
        server_dump($config, 'App Config');
        
        $posts = [
            ['id' => 1, 'title' => 'First Post'],
            ['id' => 2, 'title' => 'Second Post'],
        ];
        
        server_dump($posts, 'Posts Array');
        ?>
        
        <div class="success-box">
            ✅ Отправлено 3 dumps
        </div>

        <h2>🔍 Как проверить в Debug Toolbar</h2>
        
        <div class="step">
            <strong>Шаг 1:</strong> Откройте Debug Toolbar внизу страницы
        </div>
        
        <div class="step">
            <strong>Шаг 2:</strong> Перейдите на вкладку <strong>"Logs"</strong>
        </div>
        
        <div class="step">
            <strong>Шаг 3:</strong> Найдите записи уровня <code>[WARNING]</code>
        </div>

        <h2>✅ Правильный вывод (должен быть так):</h2>
        
        <div class="check-item">
            <code>[WARNING] Dump Server unavailable, data logged to file: label=<strong>User Data</strong>, type=<strong>array</strong>, file=<strong>test-debug-toolbar-interpolation.php:68</strong>, log=storage/logs/dumps.log</code>
        </div>
        
        <div class="check-item">
            <code>[WARNING] Dump Server unavailable, data logged to file: label=<strong>App Config</strong>, type=<strong>array</strong>, file=<strong>test-debug-toolbar-interpolation.php:76</strong>, log=storage/logs/dumps.log</code>
        </div>
        
        <div class="check-item">
            <code>[WARNING] Dump Server unavailable, data logged to file: label=<strong>Posts Array</strong>, type=<strong>array</strong>, file=<strong>test-debug-toolbar-interpolation.php:84</strong>, log=storage/logs/dumps.log</code>
        </div>

        <h2>❌ Неправильный вывод (НЕ должно быть так):</h2>
        
        <div class="wrong-item">
            <code>[WARNING] Dump Server unavailable, data logged to file: label=<strong>{label}</strong>, type=<strong>{type}</strong>, file=<strong>{file}:{line}</strong>, log=<strong>{log_file}</strong></code>
        </div>
        
        <div class="info-box">
            <strong>💡 Подсказка:</strong><br>
            Если вы видите <code>{label}</code>, <code>{type}</code> и т.д. - значит интерполяция не работает.<br>
            Должны быть <strong>реальные значения</strong>: <code>User Data</code>, <code>array</code>, <code>test-debug-toolbar-interpolation.php:68</code>
        </div>

        <h2>🎯 Что проверяем</h2>
        
        <ul>
            <li>✅ Плейсхолдеры <code>{label}</code>, <code>{type}</code> заменены на реальные значения</li>
            <li>✅ Каждая запись имеет уникальные данные (User Data, App Config, Posts Array)</li>
            <li>✅ Номера строк правильные и разные</li>
            <li>✅ Контекст также доступен отдельно (развернуть запись)</li>
        </ul>

        <div class="success-box">
            <strong>🎉 Если всё правильно:</strong><br>
            Вы увидите 3 WARNING записи с полностью интерполированными сообщениями!
        </div>
    </div>

    <?php
    // Рендерим Debug Toolbar
    if (defined('SHOW_DEBUG_TOOLBAR') && SHOW_DEBUG_TOOLBAR) {
        echo render_debug_toolbar();
    }
    ?>
</body>
</html>

