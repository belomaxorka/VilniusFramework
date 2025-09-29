<?php declare(strict_types=1);

/**
 * Пример использования шаблонизатора TorrentPier
 * 
 * Этот файл демонстрирует основные возможности шаблонизатора
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Инициализируем ядро приложения
define('ROOT', dirname(__DIR__));
define('CONFIG_DIR', ROOT . '/config');
define('LOG_DIR', ROOT . '/storage/logs');

use Core\Core;
use Core\TemplateEngine;

// Инициализируем приложение
Core::init();

echo "=== Пример использования шаблонизатора TorrentPier ===\n\n";

// Создаем экземпляр шаблонизатора
$template = TemplateEngine::getInstance();

// Пример 1: Простая переменная
echo "1. Простая переменная:\n";
$result = $template->render('welcome.tpl', [
    'title' => 'Тест шаблонизатора',
    'message' => 'Это простой пример использования'
]);
echo $result . "\n\n";

// Пример 2: Условия
echo "2. Условия:\n";
$result = $template->render('welcome.tpl', [
    'title' => 'Тест условий',
    'message' => 'Сообщение отображается',
    'name' => 'Пользователь'
]);
echo $result . "\n\n";

// Пример 3: Циклы
echo "3. Циклы:\n";
$result = $template->render('welcome.tpl', [
    'title' => 'Тест циклов',
    'users' => [
        ['name' => 'Алексей', 'email' => 'alex@example.com'],
        ['name' => 'Мария', 'email' => 'maria@example.com'],
        ['name' => 'Дмитрий', 'email' => 'dmitry@example.com']
    ]
]);
echo $result . "\n\n";

// Пример 4: Использование глобальных функций
echo "4. Использование глобальных функций:\n";
$result = view('welcome.tpl', [
    'title' => 'Глобальные функции',
    'message' => 'Используем функцию view()',
    'name' => 'Тестер'
]);
echo $result . "\n\n";

// Пример 5: Настройка кэширования
echo "5. Настройка кэширования:\n";
$template->setCacheEnabled(true);
$template->setCacheLifetime(1800); // 30 минут
echo "Кэширование включено на 30 минут\n\n";

// Пример 6: Очистка кэша
echo "6. Очистка кэша:\n";
$template->clearCache();
echo "Кэш очищен\n\n";

echo "=== Все примеры выполнены успешно! ===\n";
