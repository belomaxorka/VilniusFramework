#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * Queue Worker - Обрабатывает задачи из очереди
 *
 * Использование:
 *   php bin/queue-work.php [queue] [--max-jobs=N] [--memory=128] [--timeout=60] [--sleep=3]
 *
 * Примеры:
 *   php bin/queue-work.php                    # обрабатывает default очередь
 *   php bin/queue-work.php logs               # обрабатывает logs очередь
 *   php bin/queue-work.php logs --max-jobs=100 --memory=256
 */

// Загружаем autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Определяем константы
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_DIR', BASE_PATH . '/config');
define('LOG_DIR', BASE_PATH . '/storage/logs');
define('CACHE_DIR', BASE_PATH . '/storage/cache');

// Инициализируем приложение
Core\Config::init();
Core\Database::init();
Core\Logger::init();
Core\Queue\QueueManager::init();

// Парсим аргументы командной строки
$options = getopt('', ['max-jobs:', 'memory:', 'timeout:', 'sleep:']);
$queue = $argv[1] ?? 'default';

$maxJobs = isset($options['max-jobs']) ? (int)$options['max-jobs'] : 0;
$memory = isset($options['memory']) ? (int)$options['memory'] : 128;
$timeout = isset($options['timeout']) ? (int)$options['timeout'] : 60;
$sleep = isset($options['sleep']) ? (int)$options['sleep'] : 3;

// Создаем и запускаем worker
$worker = new Core\Queue\Worker();

try {
    $worker->work($queue, $maxJobs, $memory, $timeout, $sleep);
} catch (\Exception $e) {
    echo "Worker error: " . $e->getMessage() . "\n";
    exit(1);
}
