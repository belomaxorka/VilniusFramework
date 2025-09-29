<?php declare(strict_types=1);

use Core\TemplateEngine;

beforeEach(function () {
    // Создаем временную директорию для тестов
    $this->testTemplateDir = sys_get_temp_dir() . '/torrentpier_templates_comparison_test';
    $this->testCacheDir = sys_get_temp_dir() . '/torrentpier_cache_comparison_test';
    
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

test('can render template with equality operator', function () {
    $templateContent = '{% if age == 18 %}You are 18!{% endif %}';
    $templateFile = $this->testTemplateDir . '/equality.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('equality.tpl', ['age' => 18]);
    expect($result1)->toBe('You are 18!');
    
    $result2 = $engine->render('equality.tpl', ['age' => 20]);
    expect($result2)->toBe('');
});

test('can render template with inequality operator', function () {
    $templateContent = '{% if age != 18 %}You are not 18!{% endif %}';
    $templateFile = $this->testTemplateDir . '/inequality.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('inequality.tpl', ['age' => 20]);
    expect($result1)->toBe('You are not 18!');
    
    $result2 = $engine->render('inequality.tpl', ['age' => 18]);
    expect($result2)->toBe('');
});

test('can render template with greater than operator', function () {
    $templateContent = '{% if age > 18 %}You are an adult!{% endif %}';
    $templateFile = $this->testTemplateDir . '/greater.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('greater.tpl', ['age' => 20]);
    expect($result1)->toBe('You are an adult!');
    
    $result2 = $engine->render('greater.tpl', ['age' => 18]);
    expect($result2)->toBe('');
    
    $result3 = $engine->render('greater.tpl', ['age' => 16]);
    expect($result3)->toBe('');
});

test('can render template with less than operator', function () {
    $templateContent = '{% if age < 18 %}You are a minor!{% endif %}';
    $templateFile = $this->testTemplateDir . '/less.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('less.tpl', ['age' => 16]);
    expect($result1)->toBe('You are a minor!');
    
    $result2 = $engine->render('less.tpl', ['age' => 18]);
    expect($result2)->toBe('');
    
    $result3 = $engine->render('less.tpl', ['age' => 20]);
    expect($result3)->toBe('');
});

test('can render template with greater than or equal operator', function () {
    $templateContent = '{% if age >= 18 %}You can vote!{% endif %}';
    $templateFile = $this->testTemplateDir . '/greater_equal.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('greater_equal.tpl', ['age' => 18]);
    expect($result1)->toBe('You can vote!');
    
    $result2 = $engine->render('greater_equal.tpl', ['age' => 20]);
    expect($result2)->toBe('You can vote!');
    
    $result3 = $engine->render('greater_equal.tpl', ['age' => 16]);
    expect($result3)->toBe('');
});

test('can render template with less than or equal operator', function () {
    $templateContent = '{% if age <= 12 %}Child ticket!{% endif %}';
    $templateFile = $this->testTemplateDir . '/less_equal.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('less_equal.tpl', ['age' => 12]);
    expect($result1)->toBe('Child ticket!');
    
    $result2 = $engine->render('less_equal.tpl', ['age' => 10]);
    expect($result2)->toBe('Child ticket!');
    
    $result3 = $engine->render('less_equal.tpl', ['age' => 15]);
    expect($result3)->toBe('');
});

test('can render template with string comparison', function () {
    $templateContent = '{% if status == "active" %}System is active!{% endif %}';
    $templateFile = $this->testTemplateDir . '/string_comparison.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('string_comparison.tpl', ['status' => 'active']);
    expect($result1)->toBe('System is active!');
    
    $result2 = $engine->render('string_comparison.tpl', ['status' => 'inactive']);
    expect($result2)->toBe('');
});

test('can render template with object property comparison', function () {
    $templateContent = '{% if user.age >= 18 %}Welcome, {{ user.name }}!{% endif %}';
    $templateFile = $this->testTemplateDir . '/object_comparison.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('object_comparison.tpl', [
        'user' => ['name' => 'John', 'age' => 20]
    ]);
    expect($result1)->toBe('Welcome, John!');
    
    $result2 = $engine->render('object_comparison.tpl', [
        'user' => ['name' => 'Jane', 'age' => 16]
    ]);
    expect($result2)->toBe('');
});

test('can render template with array element comparison', function () {
    $templateContent = '{% if scores[0] > 80 %}Excellent!{% endif %}';
    $templateFile = $this->testTemplateDir . '/array_comparison.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('array_comparison.tpl', ['scores' => [85, 70, 90]]);
    expect($result1)->toBe('Excellent!');
    
    $result2 = $engine->render('array_comparison.tpl', ['scores' => [75, 70, 90]]);
    expect($result2)->toBe('');
});

test('can render template with multiple comparisons', function () {
    $templateContent = '{% if age >= 18 and age < 65 %}Working age!{% endif %}';
    $templateFile = $this->testTemplateDir . '/multiple_comparison.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('multiple_comparison.tpl', ['age' => 25]);
    expect($result1)->toBe('Working age!');
    
    $result2 = $engine->render('multiple_comparison.tpl', ['age' => 16]);
    expect($result2)->toBe('');
    
    $result3 = $engine->render('multiple_comparison.tpl', ['age' => 70]);
    expect($result3)->toBe('');
});

test('can render template with comparison in loop', function () {
    $templateContent = '{% for score in scores %}{% if score > 80 %}{{ score }} - Excellent!{% endif %}{% endfor %}';
    $templateFile = $this->testTemplateDir . '/loop_comparison.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result = $engine->render('loop_comparison.tpl', ['scores' => [85, 70, 90, 75]]);
    expect($result)->toBe('85 - Excellent!90 - Excellent!');
});
