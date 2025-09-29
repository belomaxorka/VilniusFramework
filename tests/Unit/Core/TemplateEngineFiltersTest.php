<?php declare(strict_types=1);

use Core\TemplateEngine;

beforeEach(function () {
    // Создаем временную директорию для тестов
    $this->testTemplateDir = sys_get_temp_dir() . '/torrentpier_templates_filters_test';
    $this->testCacheDir = sys_get_temp_dir() . '/torrentpier_cache_filters_test';
    
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

test('can render template with simple filters', function () {
    $templateContent = '{{ name|upper }} - {{ name|lower }} - {{ name|capitalize }}';
    $templateFile = $this->testTemplateDir . '/filters.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('filters.tpl', ['name' => 'john']);
    
    expect($result)->toBe('JOHN - john - John');
});

test('can render template with filter chains', function () {
    $templateContent = '{{ name|upper|trim }}';
    $templateFile = $this->testTemplateDir . '/chain.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('chain.tpl', ['name' => '  john  ']);
    
    expect($result)->toBe('JOHN');
});

test('can render template with filters with parameters', function () {
    $templateContent = '{{ price|number_format(2) }} - {{ date|date("Y-m-d") }}';
    $templateFile = $this->testTemplateDir . '/params.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('params.tpl', [
        'price' => 123.456,
        'date' => 1640995200 // 2022-01-01 timestamp
    ]);
    
    expect($result)->toBe('123.46 - 2022-01-01');
});

test('can render template with length filter', function () {
    $templateContent = '{{ text|length }}';
    $templateFile = $this->testTemplateDir . '/length.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('length.tpl', ['text' => 'Hello World']);
    
    expect($result)->toBe('11');
});

test('can render template with substr filter', function () {
    $templateContent = '{{ text|substr(0, 5) }}';
    $templateFile = $this->testTemplateDir . '/substr.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('substr.tpl', ['text' => 'Hello World']);
    
    expect($result)->toBe('Hello');
});

test('can render template with nl2br filter', function () {
    $templateContent = '{! text|nl2br !}';
    $templateFile = $this->testTemplateDir . '/nl2br.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('nl2br.tpl', ['text' => "Line 1\nLine 2"]);
    
    expect($result)->toBe("Line 1<br />\nLine 2");
});

test('can add custom filter', function () {
    $templateContent = '{{ name|custom }}';
    $templateFile = $this->testTemplateDir . '/custom.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $engine->addFilter('custom', function($value) {
        return 'Custom: ' . $value;
    });
    
    $result = $engine->render('custom.tpl', ['name' => 'test']);
    
    expect($result)->toBe('Custom: test');
});

test('can get list of available filters', function () {
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $filters = $engine->getFilters();
    
    expect($filters)->toContain('upper', 'lower', 'capitalize', 'trim', 'length');
    expect($filters)->toBeArray();
});

test('can handle unknown filter gracefully', function () {
    $templateContent = '{{ name|unknown }}';
    $templateFile = $this->testTemplateDir . '/unknown.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    // Неизвестный фильтр должен вызывать ошибку
    expect(fn() => $engine->render('unknown.tpl', ['name' => 'test']))
        ->toThrow(Error::class);
});

test('can render template with multiple filters in different variables', function () {
    $templateContent = '{{ name|upper }} and {{ title|lower }} and {{ price|number_format(2) }}';
    $templateFile = $this->testTemplateDir . '/multiple.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('multiple.tpl', [
        'name' => 'john',
        'title' => 'HELLO WORLD',
        'price' => 99.99
    ]);
    
    expect($result)->toBe('JOHN and hello world and 99.99');
});
