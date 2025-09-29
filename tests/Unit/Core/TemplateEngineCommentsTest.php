<?php declare(strict_types=1);

use Core\TemplateEngine;

beforeEach(function () {
    // Создаем временную директорию для тестов
    $this->testTemplateDir = sys_get_temp_dir() . '/torrentpier_templates_comments_test';
    $this->testCacheDir = sys_get_temp_dir() . '/torrentpier_cache_comments_test';
    
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

test('can render template with single line comment', function () {
    $templateContent = 'Hello {{ name }}! {# This is a comment #}';
    $templateFile = $this->testTemplateDir . '/comment.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('comment.tpl', [
        'name' => 'John'
    ]);
    
    expect($result)->toBe('Hello John! ');
});

test('can render template with multi-line comment', function () {
    $templateContent = "Hello {{ name }}!\n{# This is a\nmulti-line comment\n#}";
    $templateFile = $this->testTemplateDir . '/multiline_comment.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('multiline_comment.tpl', [
        'name' => 'John'
    ]);
    
    expect($result)->toBe("Hello John!\n");
});

test('can render template with multiple comments', function () {
    $templateContent = '{# Header comment #}Hello {{ name }}! {# Middle comment #}Welcome! {# Footer comment #}';
    $templateFile = $this->testTemplateDir . '/multiple_comments.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('multiple_comments.tpl', [
        'name' => 'John'
    ]);
    
    expect($result)->toBe('Hello John! Welcome! ');
});

test('can render template with comment containing special characters', function () {
    $templateContent = 'Hello {{ name }}! {# Comment with {{ variables }} and {% tags %} #}';
    $templateFile = $this->testTemplateDir . '/special_comment.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('special_comment.tpl', [
        'name' => 'John'
    ]);
    
    expect($result)->toBe('Hello John! ');
});

test('can render template with comment inside conditional', function () {
    $templateContent = '{% if show_message %}{# Show message comment #}Hello {{ name }}!{% endif %}';
    $templateFile = $this->testTemplateDir . '/comment_conditional.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('comment_conditional.tpl', [
        'show_message' => true,
        'name' => 'John'
    ]);
    
    expect($result)->toBe('Hello John!');
});

test('can render template with comment inside loop', function () {
    $templateContent = '{% for item in items %}{# Loop comment #}{{ item }}{% endfor %}';
    $templateFile = $this->testTemplateDir . '/comment_loop.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('comment_loop.tpl', [
        'items' => ['a', 'b', 'c']
    ]);
    
    expect($result)->toBe('abc');
});

test('can render template with empty comment', function () {
    $templateContent = 'Hello {{ name }}! {##}';
    $templateFile = $this->testTemplateDir . '/empty_comment.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('empty_comment.tpl', [
        'name' => 'John'
    ]);
    
    expect($result)->toBe('Hello John! ');
});

test('can render template with comment containing quotes', function () {
    $templateContent = 'Hello {{ name }}! {# Comment with "quotes" and \'apostrophes\' #}';
    $templateFile = $this->testTemplateDir . '/quotes_comment.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('quotes_comment.tpl', [
        'name' => 'John'
    ]);
    
    expect($result)->toBe('Hello John! ');
});

test('can render template with comment at the beginning', function () {
    $templateContent = '{# This is a header comment #}Hello {{ name }}!';
    $templateFile = $this->testTemplateDir . '/beginning_comment.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('beginning_comment.tpl', [
        'name' => 'John'
    ]);
    
    expect($result)->toBe('Hello John!');
});

test('can render template with comment at the end', function () {
    $templateContent = 'Hello {{ name }}!{# This is a footer comment #}';
    $templateFile = $this->testTemplateDir . '/end_comment.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('end_comment.tpl', [
        'name' => 'John'
    ]);
    
    expect($result)->toBe('Hello John!');
});
