<?php declare(strict_types=1);

/**
 * Тест логирования контекста в файл
 */

require_once __DIR__ . '/core/bootstrap.php';

\Core\Core::init();

echo "🧪 Тест логирования контекста\n";
echo str_repeat('─', 60) . "\n\n";

// Убедимся что dump server НЕ доступен
if (dump_server_available()) {
    echo "⚠️  Dump Server запущен! Остановите его для теста.\n";
    exit(1);
}

echo "✅ Dump Server недоступен - тестируем логирование\n\n";

// Отправляем тестовый dump
$testData = [
    'id' => 123,
    'name' => 'Test User',
    'email' => 'test@example.com',
];

server_dump($testData, 'Test User Data');

echo "✅ Dump отправлен!\n\n";

// Читаем последнюю строку из app.log
$appLog = LOG_DIR . '/app.log';
if (file_exists($appLog)) {
    $lines = file($appLog);
    $lastLine = end($lines);
    
    echo "📋 Последняя запись в app.log:\n";
    echo str_repeat('─', 60) . "\n";
    echo $lastLine;
    echo str_repeat('─', 60) . "\n\n";
    
    // Проверяем наличие контекста
    if (strpos($lastLine, 'label=') !== false &&
        strpos($lastLine, 'type=') !== false &&
        strpos($lastLine, 'file=') !== false) {
        echo "✅ Контекст присутствует в логе!\n";
    } else {
        echo "❌ Контекст отсутствует в логе!\n";
    }
} else {
    echo "⚠️  Файл app.log не найден.\n";
}

