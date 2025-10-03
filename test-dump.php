<?php declare(strict_types=1);

/**
 * Тест Dump Server
 * 
 * Использование:
 * 1. Запустите dump server: php vilnius dump-server
 * 2. Запустите этот скрипт: php test-dump.php
 * 3. Смотрите результат в окне dump server
 */

require_once __DIR__ . '/core/bootstrap.php';

\Core\Core::init();

echo "🔄 Отправляю данные на Dump Server...\n\n";

// Проверяем доступность сервера
if (dump_server_available()) {
    echo "✅ Dump Server доступен!\n";
} else {
    echo "❌ Dump Server не доступен!\n";
    echo "   Запустите: php vilnius dump-server\n";
    exit(1);
}

// Отправляем тестовые данные
$testData = [
    'message' => 'Hello from test script!',
    'timestamp' => date('Y-m-d H:i:s'),
    'random' => rand(1000, 9999),
];

server_dump($testData, 'Test Data');

$user = [
    'id' => 123,
    'name' => 'Test User',
    'email' => 'test@example.com',
    'roles' => ['admin', 'editor'],
];

server_dump($user, 'User Object');

echo "\n✅ Данные отправлены!\n";
echo "📺 Проверьте окно Dump Server\n";

