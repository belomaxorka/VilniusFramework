<?php declare(strict_types=1);

use Core\TemplateEngine;

beforeEach(function () {
    // Создаем временную директорию для тестов
    $this->testTemplateDir = sys_get_temp_dir() . '/vilnius_templates_functions_test';
    $this->testCacheDir = sys_get_temp_dir() . '/torrentpier_cache_functions_test';

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

// Тесты для функций

test('can register and call custom function', function () {
    $templateContent = '{! hello() !}';
    $templateFile = $this->testTemplateDir . '/function.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $engine->addFunction('hello', function () {
        return 'Hello, World!';
    });

    $result = $engine->render('function.twig');
    expect($result)->toBe('Hello, World!');
});

test('can call function with string argument', function () {
    $templateContent = '{! greet("John") !}';
    $templateFile = $this->testTemplateDir . '/function_arg.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $engine->addFunction('greet', function ($name) {
        return "Hello, {$name}!";
    });

    $result = $engine->render('function_arg.twig');
    expect($result)->toBe('Hello, John!');
});

test('can call function with variable argument', function () {
    $templateContent = '{! greet(name) !}';
    $templateFile = $this->testTemplateDir . '/function_var.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $engine->addFunction('greet', function ($name) {
        return "Hello, {$name}!";
    });

    $result = $engine->render('function_var.twig', ['name' => 'Jane']);
    expect($result)->toBe('Hello, Jane!');
});

test('can call function with multiple arguments', function () {
    $templateContent = '{! add(5, 3) !}';
    $templateFile = $this->testTemplateDir . '/function_multi.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $engine->addFunction('add', function ($a, $b) {
        return $a + $b;
    });

    $result = $engine->render('function_multi.twig');
    expect($result)->toBe('8');
});

test('can call function with mixed arguments', function () {
    $templateContent = '{! format_name("Mr.", first, last) !}';
    $templateFile = $this->testTemplateDir . '/function_mixed.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $engine->addFunction('format_name', function ($title, $first, $last) {
        return "{$title} {$first} {$last}";
    });

    $result = $engine->render('function_mixed.twig', [
        'first' => 'John',
        'last' => 'Doe'
    ]);
    expect($result)->toBe('Mr. John Doe');
});

test('can use function in escaped output', function () {
    $templateContent = '{{ make_tag() }}';
    $templateFile = $this->testTemplateDir . '/function_escaped.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $engine->addFunction('make_tag', function () {
        return '<script>alert("XSS")</script>';
    });

    $result = $engine->render('function_escaped.twig');
    expect($result)->toBe('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;');
});

test('can use function in unescaped output', function () {
    $templateContent = '{! make_tag() !}';
    $templateFile = $this->testTemplateDir . '/function_unescaped.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $engine->addFunction('make_tag', function () {
        return '<div class="container">Content</div>';
    });

    $result = $engine->render('function_unescaped.twig');
    expect($result)->toBe('<div class="container">Content</div>');
});

test('can nest function calls', function () {
    $templateContent = '{! upper(greet("World")) !}';
    $templateFile = $this->testTemplateDir . '/function_nested.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $engine->addFunction('greet', function ($name) {
        return "Hello, {$name}!";
    });
    $engine->addFunction('upper', function ($text) {
        return strtoupper($text);
    });

    $result = $engine->render('function_nested.twig');
    expect($result)->toBe('HELLO, WORLD!');
});

test('can use function in condition', function () {
    $templateContent = '{% if is_admin() %}Admin Panel{% endif %}';
    $templateFile = $this->testTemplateDir . '/function_condition.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $engine->addFunction('is_admin', function () {
        return true;
    });

    $result = $engine->render('function_condition.twig');
    expect($result)->toBe('Admin Panel');
});

test('can use vite function if it exists', function () {
    // Пропускаем тест если функция vite не существует
    if (!function_exists('vite')) {
        expect(true)->toBeTrue();
        return;
    }

    $templateContent = '{! vite("app") !}';
    $templateFile = $this->testTemplateDir . '/vite.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('vite.twig');

    // Проверяем что результат содержит script или link теги
    expect($result)->toContain('script');
});

test('throws exception for undefined function', function () {
    $templateContent = '{! undefined_function() !}';
    $templateFile = $this->testTemplateDir . '/undefined.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    expect(fn() => $engine->render('undefined.twig'))
        ->toThrow(\InvalidArgumentException::class, "Function 'undefined_function' not found");
});

test('can check if function exists', function () {
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $engine->addFunction('test_func', fn() => 'test');

    expect($engine->hasFunction('test_func'))->toBeTrue();
    expect($engine->hasFunction('non_existent'))->toBeFalse();
});

