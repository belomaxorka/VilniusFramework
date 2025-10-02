<?php

require_once __DIR__ . '/core/bootstrap.php';

use Core\TemplateEngine;

$testTemplateDir = sys_get_temp_dir() . '/debug_funcs';
$testCacheDir = sys_get_temp_dir() . '/debug_cache';

@mkdir($testTemplateDir, 0755, true);
@mkdir($testCacheDir, 0755, true);

echo "=== Тест 1: Вложенные функции ===\n";
$template1 = '{! upper(greet("World")) !}';
file_put_contents($testTemplateDir . '/test1.twig', $template1);

$engine = new TemplateEngine($testTemplateDir, $testCacheDir);
$engine->setCacheEnabled(false);
$engine->addFunction('greet', fn($name) => "Hello, {$name}!");
$engine->addFunction('upper', fn($text) => strtoupper($text));

$reflection = new ReflectionClass($engine);
$method = $reflection->getMethod('compileTemplate');
$method->setAccessible(true);
$compiled1 = $method->invoke($engine, $template1, 'test1.twig');

echo "Шаблон: $template1\n";
echo "Скомпилировано:\n$compiled1\n\n";

try {
    $result = $engine->render('test1.twig');
    echo "Результат: $result\n";
} catch (Throwable $e) {
    echo "ОШИБКА: " . $e->getMessage() . "\n";
}

echo "\n=== Тест 2: Функция в условии ===\n";
$template2 = '{% if is_admin() %}Admin Panel{% endif %}';
file_put_contents($testTemplateDir . '/test2.twig', $template2);

$engine2 = new TemplateEngine($testTemplateDir, $testCacheDir);
$engine2->setCacheEnabled(false);
$engine2->addFunction('is_admin', fn() => true);

$compiled2 = $method->invoke($engine2, $template2, 'test2.twig');

echo "Шаблон: $template2\n";
echo "Скомпилировано:\n$compiled2\n\n";

try {
    $result = $engine2->render('test2.twig');
    echo "Результат: $result\n";
} catch (Throwable $e) {
    echo "ОШИБКА: " . $e->getMessage() . "\n";
}

// Очистка
@array_map('unlink', glob($testTemplateDir . '/*'));
@rmdir($testTemplateDir);
@array_map('unlink', glob($testCacheDir . '/*'));
@rmdir($testCacheDir);

