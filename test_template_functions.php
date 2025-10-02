<?php declare(strict_types=1);

// Простой тест для проверки работы функций в шаблонах

require_once __DIR__ . '/vendor/autoload.php';

// Загружаем хелперы
require_once __DIR__ . '/core/helpers/app/vite.php';

use Core\TemplateEngine;

// Создаем временные директории
$testTemplateDir = sys_get_temp_dir() . '/test_templates';
$testCacheDir = sys_get_temp_dir() . '/test_cache';

if (!is_dir($testTemplateDir)) {
    mkdir($testTemplateDir, 0755, true);
}

if (!is_dir($testCacheDir)) {
    mkdir($testCacheDir, 0755, true);
}

echo "=== Тест 1: Простая функция без аргументов ===\n";
$templateContent = '{! hello() !}';
file_put_contents($testTemplateDir . '/test1.twig', $templateContent);

$engine = new TemplateEngine($testTemplateDir, $testCacheDir);
$engine->setCacheEnabled(false); // Отключаем кэш для отладки
$engine->addFunction('hello', function () {
    return 'Hello, World!';
});

try {
    // Показываем скомпилированный код
    $reflection = new ReflectionClass($engine);
    $method = $reflection->getMethod('compileTemplate');
    $method->setAccessible(true);
    $compiled = $method->invoke($engine, $templateContent, 'test1.twig');
    echo "Скомпилированный код:\n";
    echo $compiled . "\n\n";
    
    $result = $engine->render('test1.twig');
    echo "Результат: $result\n";
    echo $result === 'Hello, World!' ? "✓ PASSED\n" : "✗ FAILED\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Тест 2: Функция со строковым аргументом ===\n";
$templateContent = '{! greet("John") !}';
file_put_contents($testTemplateDir . '/test2.twig', $templateContent);

$engine = new TemplateEngine($testTemplateDir, $testCacheDir);
$engine->addFunction('greet', function ($name) {
    return "Hello, {$name}!";
});

try {
    $result = $engine->render('test2.twig');
    echo "Результат: $result\n";
    echo $result === 'Hello, John!' ? "✓ PASSED\n" : "✗ FAILED\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Тест 3: Функция с переменной ===\n";
$templateContent = '{! greet(name) !}';
file_put_contents($testTemplateDir . '/test3.twig', $templateContent);

$engine = new TemplateEngine($testTemplateDir, $testCacheDir);
$engine->addFunction('greet', function ($name) {
    return "Hello, {$name}!";
});

try {
    $result = $engine->render('test3.twig', ['name' => 'Jane']);
    echo "Результат: $result\n";
    echo $result === 'Hello, Jane!' ? "✓ PASSED\n" : "✗ FAILED\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Тест 4: Функция vite() ===\n";
$templateContent = '{! vite("app") !}';
file_put_contents($testTemplateDir . '/test4.twig', $templateContent);

$engine = new TemplateEngine($testTemplateDir, $testCacheDir);

try {
    $result = $engine->render('test4.twig');
    echo "Результат:\n$result\n";
    echo (strpos($result, 'script') !== false || strpos($result, 'link') !== false) ? "✓ PASSED\n" : "✗ FAILED\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}

// Очистка
echo "\n=== Очистка ===\n";
$files = glob($testTemplateDir . '/*');
foreach ($files as $file) {
    unlink($file);
}
rmdir($testTemplateDir);

$files = glob($testCacheDir . '/*');
foreach ($files as $file) {
    unlink($file);
}
rmdir($testCacheDir);

echo "Готово!\n";

