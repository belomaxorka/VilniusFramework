<?php declare(strict_types=1);

/**
 * Тест интеграции Dump Server с Debug Toolbar
 * 
 * Этот тест показывает как предупреждения о недоступности dump server
 * отображаются в Debug Toolbar.
 * 
 * Использование:
 * 1. УБЕДИТЕСЬ что dump server НЕ запущен!
 * 2. Запустите встроенный веб-сервер: php -S localhost:8000 -t public
 * 3. Откройте в браузере: http://localhost:8000/test-dump-debug-toolbar.php
 * 4. Откройте Debug Toolbar (обычно внизу страницы)
 * 5. Перейдите на вкладку "Logs"
 * 6. Вы увидите предупреждения о недоступности Dump Server
 */

require_once __DIR__ . '/core/bootstrap.php';

\Core\Core::init();

// Убедимся что Debug Toolbar включён
if (!defined('SHOW_DEBUG_TOOLBAR')) {
    define('SHOW_DEBUG_TOOLBAR', true);
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dump Server + Debug Toolbar Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
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
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
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
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #272822;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            background: #fafafa;
            border-radius: 5px;
        }
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: #4CAF50;
            color: white;
            text-align: center;
            line-height: 30px;
            border-radius: 50%;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐛 Dump Server + Debug Toolbar Integration Test</h1>
        
        <div class="info-box">
            <strong>ℹ️ Что происходит на этой странице:</strong><br>
            Мы отправляем данные через <code>server_dump()</code> когда Dump Server недоступен.
            Эти предупреждения логируются через <code>Logger::warning()</code> и появляются в Debug Toolbar.
        </div>

        <?php
        // Проверяем доступность dump server
        $serverAvailable = dump_server_available();
        
        if ($serverAvailable): ?>
            <div class="warning-box">
                <strong>⚠️ Внимание:</strong> Dump Server запущен!<br>
                Для теста fallback механизма остановите его (Ctrl+C в терминале).
            </div>
        <?php else: ?>
            <div class="success-box">
                <strong>✅ Отлично!</strong> Dump Server недоступен - fallback активирован!<br>
                Все dumps будут логироваться в файл и появятся в Debug Toolbar.
            </div>
        <?php endif; ?>

        <h2>📤 Отправка тестовых данных</h2>
        
        <?php
        // Отправляем различные типы данных
        $testData = [
            'page' => 'test-dump-debug-toolbar.php',
            'timestamp' => date('Y-m-d H:i:s'),
            'server_available' => $serverAvailable,
            'random' => rand(1000, 9999),
        ];
        
        server_dump($testData, 'Test Page Data');
        
        $user = [
            'id' => 999,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'roles' => ['viewer'],
        ];
        
        server_dump($user, 'User Data');
        
        $config = [
            'app' => 'Vilnius Framework',
            'debug' => true,
            'version' => '1.0.0',
        ];
        
        server_dump($config, 'Config Data');
        ?>
        
        <div class="step">
            <strong>✅ Отправлено 3 dumps:</strong>
            <ul>
                <li>Test Page Data (array)</li>
                <li>User Data (array)</li>
                <li>Config Data (array)</li>
            </ul>
        </div>

        <h2>🔍 Как посмотреть в Debug Toolbar</h2>
        
        <div class="step">
            <span class="step-number">1</span>
            <strong>Откройте Debug Toolbar</strong><br>
            Обычно он появляется внизу страницы (чёрная полоса с иконками).
        </div>
        
        <div class="step">
            <span class="step-number">2</span>
            <strong>Перейдите на вкладку "Logs"</strong><br>
            Там вы увидите все логи текущего запроса.
        </div>
        
        <div class="step">
            <span class="step-number">3</span>
            <strong>Найдите записи с уровнем WARNING</strong><br>
            Они будут содержать текст: <code>Dump Server unavailable, data logged to file</code>
        </div>
        
        <div class="step">
            <span class="step-number">4</span>
            <strong>Изучите контекст</strong><br>
            Каждая запись содержит:
            <ul>
                <li><code>label</code> - метка dump'а</li>
                <li><code>type</code> - тип данных</li>
                <li><code>file</code> - файл откуда вызвано</li>
                <li><code>line</code> - строка кода</li>
                <li><code>log_file</code> - путь к лог-файлу</li>
            </ul>
        </div>

        <h2>📋 Просмотр лог-файла</h2>
        
        <div class="info-box">
            <strong>Все dumps также сохранены в:</strong><br>
            <code><?php echo STORAGE_DIR . '/logs/dumps.log'; ?></code>
        </div>
        
        <p>Для просмотра через CLI:</p>
        <pre>php vilnius dump:log --tail=10</pre>

        <h2>🎯 Что дальше?</h2>
        
        <div class="step">
            <strong>Попробуйте запустить Dump Server:</strong>
            <pre>php vilnius dump-server</pre>
            
            Затем перезагрузите эту страницу. Dumps пойдут в сервер вместо лога,
            и в Debug Toolbar НЕ будет предупреждений!
        </div>

        <div class="step">
            <strong>Интеграция в ваш код:</strong>
            <pre>// В контроллерах
server_dump($user, 'User Data');

// В моделях
server_dump($this->attributes, 'Model State');

// В middleware
server_dump($request->all(), 'Request Data');</pre>
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

