<?php

require_once __DIR__ . '/core/bootstrap.php';

use Core\TemplateEngine;

// Очистка кэша
$cacheDir = __DIR__ . '/storage/cache/templates';
array_map('unlink', glob("$cacheDir/*"));

$templateDir = __DIR__ . '/resources/views';
$engine = new TemplateEngine($templateDir, $cacheDir);

echo "=== Тест 1: Not operator ===\n";
$template1 = '{% if not (age < 18) %}Adult{% endif %}';
file_put_contents($templateDir . '/test_not.twig', $template1);
try {
    $result1 = $engine->render('test_not.twig', ['age' => 20]);
    $compiled1 = file_get_contents($cacheDir . '/' . md5($templateDir . '/test_not.twig') . '.php');
    echo "Шаблон: $template1\n";
    echo "Скомпилировано:\n$compiled1\n";
    echo "Результат: '$result1'\n";
} catch (Exception $e) {
    echo "Шаблон: $template1\n";
    $cacheFile = $cacheDir . '/' . md5($templateDir . '/test_not.twig') . '.php';
    if (file_exists($cacheFile)) {
        $compiled1 = file_get_contents($cacheFile);
        echo "Скомпилировано:\n$compiled1\n";
    }
    echo "ОШИБКА: " . $e->getMessage() . "\n";
}

echo "\n=== Тест 2: Mixed and/or ===\n";
$template2 = '{% if age >= 18 and (status == "active" or role == "admin") %}Access{% endif %}';
file_put_contents($templateDir . '/test_mixed.twig', $template2);
try {
    $result2 = $engine->render('test_mixed.twig', ['age' => 20, 'status' => 'active', 'role' => 'user']);
    $compiled2 = file_get_contents($cacheDir . '/' . md5($templateDir . '/test_mixed.twig') . '.php');
    echo "Шаблон: $template2\n";
    echo "Скомпилировано:\n$compiled2\n";
    echo "Результат: '$result2'\n";
} catch (Exception $e) {
    echo "Шаблон: $template2\n";
    $cacheFile = $cacheDir . '/' . md5($templateDir . '/test_mixed.twig') . '.php';
    if (file_exists($cacheFile)) {
        $compiled2 = file_get_contents($cacheFile);
        echo "Скомпилировано:\n$compiled2\n";
    }
    echo "ОШИБКА: " . $e->getMessage() . "\n";
}

