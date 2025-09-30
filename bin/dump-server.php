#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * Dump Server - запуск сервера для приема debug данных
 * 
 * Использование:
 *   php bin/dump-server.php
 *   php bin/dump-server.php --host=127.0.0.1 --port=9912
 */

// Загрузка автозагрузчика
require_once __DIR__ . '/../vendor/autoload.php';

use Core\DumpServer;
use Core\Environment;

// Установка окружения
Environment::set(Environment::DEVELOPMENT);

// Парсинг аргументов
$host = '127.0.0.1';
$port = 9912;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--host=')) {
        $host = substr($arg, 7);
    } elseif (str_starts_with($arg, '--port=')) {
        $port = (int) substr($arg, 7);
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Dump Server - receive debug dumps in separate window\n\n";
        echo "Usage:\n";
        echo "  php bin/dump-server.php [options]\n\n";
        echo "Options:\n";
        echo "  --host=HOST    Server host (default: 127.0.0.1)\n";
        echo "  --port=PORT    Server port (default: 9912)\n";
        echo "  --help, -h     Show this help\n\n";
        echo "Example:\n";
        echo "  php bin/dump-server.php --port=9913\n\n";
        exit(0);
    }
}

// Настройка сервера
DumpServer::configure($host, $port);

// Вывод ASCII art
echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║                                                           ║\n";
echo "║              🐛 DEBUG DUMP SERVER 🐛                     ║\n";
echo "║                                                           ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";
echo "\n";

try {
    DumpServer::start();
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
