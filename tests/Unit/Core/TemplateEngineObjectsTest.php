<?php declare(strict_types=1);

use Core\TemplateEngine;

beforeEach(function () {
    // Создаем временную директорию для тестов
    $this->testTemplateDir = sys_get_temp_dir() . '/torrentpier_templates_objects_test';
    $this->testCacheDir = sys_get_temp_dir() . '/torrentpier_cache_objects_test';
    
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

test('can render template with object property access', function () {
    $templateContent = 'Hello {{ user.name }}!';
    $templateFile = $this->testTemplateDir . '/object.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('object.tpl', [
        'user' => ['name' => 'John', 'email' => 'john@example.com']
    ]);
    
    expect($result)->toBe('Hello John!');
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

test('can render template with nested object access', function () {
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

test('can render template with mixed array and object access', function () {
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

test('can render template with complex nested access', function () {
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

test('can handle undefined object properties gracefully', function () {
    $templateContent = 'Name: {{ user.name }}, Missing: {{ user.missing }}';
    $templateFile = $this->testTemplateDir . '/undefined.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('undefined.tpl', [
        'user' => ['name' => 'John']
    ]);
    
    expect($result)->toBe('Name: John, Missing: ');
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
