<?php declare(strict_types=1);

use Core\TemplateEngine;

beforeEach(function () {
    // Создаем временную директорию для тестов
    $this->testTemplateDir = sys_get_temp_dir() . '/torrentpier_templates_arrays_test';
    $this->testCacheDir = sys_get_temp_dir() . '/torrentpier_cache_arrays_test';
    
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

test('can render template with array element access', function () {
    $templateContent = 'First item: {{ items[0] }}';
    $templateFile = $this->testTemplateDir . '/array.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('array.tpl', [
        'items' => ['apple', 'banana', 'cherry']
    ]);
    
    expect($result)->toBe('First item: apple');
});

test('can render template with array access using string key', function () {
    $templateContent = 'Status: {{ config.status }}';
    $templateFile = $this->testTemplateDir . '/string_key.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('string_key.tpl', [
        'config' => [
            'status' => 'active',
            'version' => '1.0'
        ]
    ]);
    
    expect($result)->toBe('Status: active');
});

test('can render template with nested array access', function () {
    $templateContent = 'City: {{ user.address.city }}';
    $templateFile = $this->testTemplateDir . '/nested.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('nested.tpl', [
        'user' => [
            'name' => 'John',
            'address' => [
                'city' => 'New York',
                'country' => 'USA'
            ]
        ]
    ]);
    
    expect($result)->toBe('City: New York');
});

test('can render template with mixed array access', function () {
    $templateContent = 'Name: {{ users[0].name }}, Email: {{ users[1].email }}';
    $templateFile = $this->testTemplateDir . '/mixed.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('mixed.tpl', [
        'users' => [
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => 'jane@example.com']
        ]
    ]);
    
    expect($result)->toBe('Name: John, Email: jane@example.com');
});

test('can render template with complex nested array access', function () {
    $templateContent = 'User: {{ data.users[0].profile.name }}, Age: {{ data.users[0].profile.age }}';
    $templateFile = $this->testTemplateDir . '/complex.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('complex.tpl', [
        'data' => [
            'users' => [
                [
                    'profile' => [
                        'name' => 'John Doe',
                        'age' => 30
                    ]
                ]
            ]
        ]
    ]);
    
    expect($result)->toBe('User: John Doe, Age: 30');
});

test('can handle undefined array elements gracefully', function () {
    $templateContent = 'First: {{ items[0] }}, Missing: {{ items[5] }}';
    $templateFile = $this->testTemplateDir . '/undefined_array.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('undefined_array.tpl', [
        'items' => ['apple', 'banana']
    ]);
    
    expect($result)->toBe('First: apple, Missing: ');
});

test('can handle undefined array keys gracefully', function () {
    $templateContent = 'Name: {{ user.name }}, Missing: {{ user.missing }}';
    $templateFile = $this->testTemplateDir . '/undefined_key.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('undefined_key.tpl', [
        'user' => ['name' => 'John']
    ]);
    
    expect($result)->toBe('Name: John, Missing: ');
});

test('can render template with numeric array access', function () {
    $templateContent = 'Numbers: {{ numbers[0] }}, {{ numbers[1] }}, {{ numbers[2] }}';
    $templateFile = $this->testTemplateDir . '/numeric.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('numeric.tpl', [
        'numbers' => [10, 20, 30]
    ]);
    
    expect($result)->toBe('Numbers: 10, 20, 30');
});

test('can render template with associative array access', function () {
    $templateContent = 'Name: {{ person.name }}, Age: {{ person.age }}, City: {{ person.city }}';
    $templateFile = $this->testTemplateDir . '/associative.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('associative.tpl', [
        'person' => [
            'name' => 'Alice',
            'age' => 25,
            'city' => 'London'
        ]
    ]);
    
    expect($result)->toBe('Name: Alice, Age: 25, City: London');
});
