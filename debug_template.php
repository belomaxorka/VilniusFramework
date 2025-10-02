<?php declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/helpers/app/vite.php';

use Core\TemplateEngine;

$testTemplateDir = sys_get_temp_dir() . '/debug_test';
$testCacheDir = sys_get_temp_dir() . '/debug_cache';

@mkdir($testTemplateDir, 0755, true);
@mkdir($testCacheDir, 0755, true);

echo "=== Отладка компиляции функций ===\n\n";

// Тест 1: Простейший случай
$template = '{! hello() !}';
file_put_contents($testTemplateDir . '/test.twig', $template);

$engine = new TemplateEngine($testTemplateDir, $testCacheDir);
$engine->setCacheEnabled(false);

// Добавляем функцию
$engine->addFunction('hello', function() {
    return 'WORKS!';
});

echo "Шаблон: $template\n";

// Получаем скомпилированный код через рефлексию
try {
    $reflection = new ReflectionClass($engine);
    $compileMethod = $reflection->getMethod('compileTemplate');
    $compileMethod->setAccessible(true);
    
    $compiled = $compileMethod->invoke($engine, $template, 'test.twig');
    
    echo "\nСкомпилированный PHP код:\n";
    echo str_repeat('-', 50) . "\n";
    echo $compiled . "\n";
    echo str_repeat('-', 50) . "\n\n";
    
    // Теперь пытаемся выполнить
    echo "Попытка рендеринга...\n";
    $result = $engine->render('test.twig');
    echo "Результат: '$result'\n";
    
} catch (Throwable $e) {
    echo "\n❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

// Очистка
@array_map('unlink', glob($testTemplateDir . '/*'));
@array_map('unlink', glob($testCacheDir . '/*'));
@rmdir($testTemplateDir);
@rmdir($testCacheDir);

