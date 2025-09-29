<?php declare(strict_types=1);

use Core\TemplateEngine;

beforeEach(function () {
    // Создаем временную директорию для тестов
    $this->testTemplateDir = sys_get_temp_dir() . '/torrentpier_templates_test';
    $this->testCacheDir = sys_get_temp_dir() . '/torrentpier_cache_test';
    
    if (!is_dir($this->testTemplateDir)) {
        mkdir($this->testTemplateDir, 0755, true);
    }
    
    if (!is_dir($this->testCacheDir)) {
        mkdir($this->testCacheDir, 0755, true);
    }
});

afterEach(function () {
    // Очищаем временные директории
    if (is_dir($this->testTemplateDir)) {
        $files = glob($this->testTemplateDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->testTemplateDir);
    }
    
    if (is_dir($this->testCacheDir)) {
        $files = glob($this->testCacheDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->testCacheDir);
    }
});

test('can create template engine instance', function () {
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    expect($engine)->toBeInstanceOf(TemplateEngine::class);
});

test('can assign variables', function () {
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $engine->assign('name', 'John');
    $engine->assign('age', 25);
    
    expect($engine)->toBeInstanceOf(TemplateEngine::class);
});

test('can assign multiple variables', function () {
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $engine->assignMultiple([
        'name' => 'Jane',
        'age' => 30,
        'city' => 'New York'
    ]);
    
    expect($engine)->toBeInstanceOf(TemplateEngine::class);
});

test('can render simple template', function () {
    $templateContent = 'Hello {{ name }}!';
    $templateFile = $this->testTemplateDir . '/test.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('test.tpl', ['name' => 'World']);
    
    expect($result)->toBe('Hello World!');
});

test('can render template with conditions', function () {
    $templateContent = '{% if show_message %}Hello {{ name }}!{% endif %}';
    $templateFile = $this->testTemplateDir . '/conditional.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('conditional.tpl', ['show_message' => true, 'name' => 'John']);
    expect($result1)->toBe('Hello John!');
    
    $result2 = $engine->render('conditional.tpl', ['show_message' => false, 'name' => 'John']);
    expect($result2)->toBe('');
});

test('can render template with loops', function () {
    $templateContent = '{% for item in items %}{{ item }}{% endfor %}';
    $templateFile = $this->testTemplateDir . '/loop.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('loop.tpl', ['items' => ['a', 'b', 'c']]);
    
    expect($result)->toBe('abc');
});

test('can handle unescaped variables', function () {
    $templateContent = '{! html_content !}';
    $templateFile = $this->testTemplateDir . '/unescaped.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('unescaped.tpl', ['html_content' => '<b>Bold</b>']);
    
    expect($result)->toBe('<b>Bold</b>');
});

test('can enable and disable cache', function () {
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $engine->setCacheEnabled(false);
    expect($engine)->toBeInstanceOf(TemplateEngine::class);
    
    $engine->setCacheEnabled(true);
    expect($engine)->toBeInstanceOf(TemplateEngine::class);
});

test('can clear cache', function () {
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    // Создаем тестовый кэш файл
    $cacheFile = $this->testCacheDir . '/test.php';
    file_put_contents($cacheFile, 'test content');
    
    expect(file_exists($cacheFile))->toBeTrue();
    
    $engine->clearCache();
    
    expect(file_exists($cacheFile))->toBeFalse();
});

test('throws exception for non-existent template', function () {
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    expect(fn() => $engine->render('nonexistent.tpl'))
        ->toThrow(InvalidArgumentException::class, 'Template not found: nonexistent.tpl');
});

test('can get singleton instance', function () {
    // Сбрасываем singleton для теста
    $reflection = new ReflectionClass(TemplateEngine::class);
    $instanceProperty = $reflection->getProperty('instance');
    $instanceProperty->setAccessible(true);
    $instanceProperty->setValue(null);
    
    $instance1 = TemplateEngine::getInstance();
    $instance2 = TemplateEngine::getInstance();
    
    expect($instance1)->toBe($instance2);
    expect($instance1)->toBeInstanceOf(TemplateEngine::class);
});
