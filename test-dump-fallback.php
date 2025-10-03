<?php declare(strict_types=1);

/**
 * Тест Fallback механизма Dump Server
 * 
 * Этот тест проверяет что происходит когда dump server НЕ запущен.
 * Данные должны автоматически логироваться в storage/logs/dumps.log
 * 
 * Использование:
 * 1. УБЕДИТЕСЬ что dump server НЕ запущен!
 * 2. Запустите: php test-dump-fallback.php
 * 3. Проверьте лог: php vilnius dump:log
 */

require_once __DIR__ . '/core/bootstrap.php';

\Core\Core::init();

echo "🧪 Тест Fallback механизма Dump Server\n";
echo str_repeat('─', 60) . "\n\n";

// Проверяем что сервер НЕ доступен
if (dump_server_available()) {
    echo "⚠️  ВНИМАНИЕ: Dump Server ЗАПУЩЕН!\n";
    echo "   Для теста fallback нужно остановить сервер (Ctrl+C)\n";
    echo "   Иначе данные уйдут в сервер, а не в лог файл.\n\n";
    
    $response = readline("Продолжить тест? (y/n): ");
    if (strtolower($response) !== 'y') {
        exit(0);
    }
}

echo "✅ Dump Server недоступен - fallback активирован!\n\n";

// Отправляем тестовые данные
echo "📤 Отправка данных...\n\n";

$testData = [
    'id' => 999,
    'name' => 'Fallback Test',
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => [
        'server' => 'unavailable',
        'logged' => true,
    ]
];

server_dump($testData, 'Fallback Test Data');

$user = [
    'id' => 123,
    'email' => 'test@example.com',
    'roles' => ['admin'],
];

server_dump($user, 'User Data (Logged)');

server_dump('Simple string value', 'String Test');
server_dump(42, 'Integer Test');
server_dump(true, 'Boolean Test');

echo "\n✅ Данные отправлены!\n\n";

// Показываем где посмотреть логи
$logFile = STORAGE_DIR . '/logs/dumps.log';

echo "📋 Данные сохранены в лог-файл:\n";
echo "   {$logFile}\n\n";

echo "📖 Просмотреть логи:\n";
echo "   php vilnius dump:log              # Весь лог\n";
echo "   php vilnius dump:log --tail=5     # Последние 5 записей\n";
echo "   php vilnius dump:log --clear      # Очистить лог\n\n";

// Показываем статистику
if (file_exists($logFile)) {
    $size = filesize($logFile);
    $sizeFormatted = $size > 1024 ? round($size / 1024, 2) . ' KB' : $size . ' B';
    
    echo "📊 Статистика лог-файла:\n";
    echo "   Размер: {$sizeFormatted}\n";
    echo "   Последнее изменение: " . date('Y-m-d H:i:s', filemtime($logFile)) . "\n\n";
    
    echo "💡 Совет: Запустите dump server для real-time отладки:\n";
    echo "   php vilnius dump-server\n";
} else {
    echo "⚠️  Лог-файл не создан. Возможно, ошибка записи?\n";
}

