<?php declare(strict_types=1);

use Core\TemplateEngine;

beforeEach(function () {
    // Создаем временную директорию для тестов
    $this->testTemplateDir = sys_get_temp_dir() . '/vilnius_templates_test';
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
    $templateFile = $this->testTemplateDir . '/test.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('test.twig', ['name' => 'World']);

    expect($result)->toBe('Hello World!');
});

test('can render template with conditions', function () {
    $templateContent = '{% if show_message %}Hello {{ name }}!{% endif %}';
    $templateFile = $this->testTemplateDir . '/conditional.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('conditional.twig', ['show_message' => true, 'name' => 'John']);
    expect($result1)->toBe('Hello John!');

    $result2 = $engine->render('conditional.twig', ['show_message' => false, 'name' => 'John']);
    expect($result2)->toBe('');
});

test('can render template with loops', function () {
    $templateContent = '{% for item in items %}{{ item }}{% endfor %}';
    $templateFile = $this->testTemplateDir . '/loop.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('loop.twig', ['items' => ['a', 'b', 'c']]);

    expect($result)->toBe('abc');
});

test('can render template with loop destructuring (key, value)', function () {
    $templateContent = '{% for key, value in items %}{{ key }}:{{ value }},{% endfor %}';
    $templateFile = $this->testTemplateDir . '/loop_destructuring.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('loop_destructuring.twig', [
        'items' => ['name' => 'John', 'age' => '25', 'city' => 'NYC']
    ]);

    expect($result)->toBe('name:John,age:25,city:NYC,');
});

test('can render template with loop destructuring for validation errors', function () {
    $templateContent = '{% for field, errors in validationErrors %}<div>{{ field }}: {{ errors }}</div>{% endfor %}';
    $templateFile = $this->testTemplateDir . '/validation_errors.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('validation_errors.twig', [
        'validationErrors' => [
            'email' => 'Invalid email',
            'password' => 'Too short',
            'username' => 'Already taken'
        ]
    ]);

    expect($result)->toBe('<div>email: Invalid email</div><div>password: Too short</div><div>username: Already taken</div>');
});

test('can handle unescaped variables', function () {
    $templateContent = '{! html_content !}';
    $templateFile = $this->testTemplateDir . '/unescaped.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('unescaped.twig', ['html_content' => '<b>Bold</b>']);

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

    expect(fn() => $engine->render('nonexistent.twig'))
        ->toThrow(InvalidArgumentException::class, 'Template not found: nonexistent.twig');
});

test('can get singleton instance', function () {
    // Сбрасываем singleton для теста
    $reflection = new ReflectionClass(TemplateEngine::class);
    $instanceProperty = $reflection->getProperty('instance');
    $instanceProperty->setAccessible(true);
    $instanceProperty->setValue(null, null);

    $instance1 = TemplateEngine::getInstance();
    $instance2 = TemplateEngine::getInstance();

    expect($instance1)->toBe($instance2);
    expect($instance1)->toBeInstanceOf(TemplateEngine::class);
});

test('loop variable provides index information', function () {
    $templateContent = '{% for item in items %}{{ loop.index }}:{{ item }},{% endfor %}';
    $templateFile = $this->testTemplateDir . '/loop_index.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('loop_index.twig', ['items' => ['a', 'b', 'c']]);

    expect($result)->toBe('1:a,2:b,3:c,');
});

test('loop variable provides index0 (zero-based)', function () {
    $templateContent = '{% for item in items %}{{ loop.index0 }}{% endfor %}';
    $templateFile = $this->testTemplateDir . '/loop_index0.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('loop_index0.twig', ['items' => ['a', 'b', 'c']]);

    expect($result)->toBe('012');
});

test('loop variable provides first and last flags', function () {
    $templateContent = '{% for item in items %}{% if loop.first %}FIRST:{% endif %}{{ item }}{% if loop.last %}:LAST{% else %},{% endif %}{% endfor %}';
    $templateFile = $this->testTemplateDir . '/loop_first_last.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('loop_first_last.twig', ['items' => ['a', 'b', 'c']]);

    expect($result)->toBe('FIRST:a,b,c:LAST');
});

test('loop variable provides length', function () {
    $templateContent = '{% for item in items %}{{ loop.length }}{% endfor %}';
    $templateFile = $this->testTemplateDir . '/loop_length.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('loop_length.twig', ['items' => ['a', 'b', 'c']]);

    expect($result)->toBe('333'); // длина одинакова на каждой итерации
});

test('loop variable provides revindex (reverse index)', function () {
    $templateContent = '{% for item in items %}{{ loop.revindex }}{% endfor %}';
    $templateFile = $this->testTemplateDir . '/loop_revindex.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('loop_revindex.twig', ['items' => ['a', 'b', 'c']]);

    expect($result)->toBe('321');
});

test('nested loops have access to parent loop', function () {
    $templateContent = '{% for row in matrix %}{% for cell in row %}{{ loop.parent.index }}.{{ loop.index }},{% endfor %}|{% endfor %}';
    $templateFile = $this->testTemplateDir . '/loop_parent.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('loop_parent.twig', [
        'matrix' => [
            ['a', 'b'],
            ['c', 'd']
        ]
    ]);

    expect($result)->toBe('1.1,1.2,|2.1,2.2,|');
});

test('loop variable works with destructuring', function () {
    $templateContent = '{% for key, value in items %}{{ loop.index }}:{{ key }}={{ value }},{% endfor %}';
    $templateFile = $this->testTemplateDir . '/loop_destructuring.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('loop_destructuring.twig', [
        'items' => ['a' => '1', 'b' => '2', 'c' => '3']
    ]);

    expect($result)->toBe('1:a=1,2:b=2,3:c=3,');
});

test('can set simple variable', function () {
    $templateContent = '{% set name = "John" %}Hello {{ name }}!';
    $templateFile = $this->testTemplateDir . '/set_simple.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('set_simple.twig');

    expect($result)->toBe('Hello John!');
});

test('can set variable with number', function () {
    $templateContent = '{% set price = 100 %}Price: {{ price }}';
    $templateFile = $this->testTemplateDir . '/set_number.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('set_number.twig');

    expect($result)->toBe('Price: 100');
});

test('can set variable with calculation', function () {
    $templateContent = '{% set total = price * quantity %}Total: {{ total }}';
    $templateFile = $this->testTemplateDir . '/set_calculation.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('set_calculation.twig', ['price' => 10, 'quantity' => 5]);

    expect($result)->toBe('Total: 50');
});

test('can set variable with concatenation', function () {
    $templateContent = '{% set fullName = firstName ~ " " ~ lastName %}Name: {{ fullName }}';
    $templateFile = $this->testTemplateDir . '/set_concat.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('set_concat.twig', ['firstName' => 'John', 'lastName' => 'Doe']);

    expect($result)->toBe('Name: John Doe');
});

test('can set variable with array', function () {
    $templateContent = '{% set items = ["apple", "banana", "orange"] %}{% for item in items %}{{ item }},{% endfor %}';
    $templateFile = $this->testTemplateDir . '/set_array.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('set_array.twig');

    expect($result)->toBe('apple,banana,orange,');
});

test('can set variable from object property', function () {
    $templateContent = '{% set name = user.name %}User: {{ name }}';
    $templateFile = $this->testTemplateDir . '/set_property.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('set_property.twig', ['user' => ['name' => 'Alice']]);

    expect($result)->toBe('User: Alice');
});

test('can use set variable in conditions', function () {
    $templateContent = '{% set isAdmin = true %}{% if isAdmin %}Admin Panel{% endif %}';
    $templateFile = $this->testTemplateDir . '/set_condition.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('set_condition.twig');

    expect($result)->toBe('Admin Panel');
});

test('test is defined works', function () {
    $templateContent = '{% if user is defined %}User exists{% endif %}';
    $templateFile = $this->testTemplateDir . '/test_defined.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('test_defined.twig', ['user' => 'John']);

    expect($result)->toBe('User exists');
});

test('test is not defined works', function () {
    $templateContent = '{% if user is not defined %}No user{% endif %}';
    $templateFile = $this->testTemplateDir . '/test_not_defined.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('test_not_defined.twig', []);

    expect($result)->toBe('No user');
});

test('test is null works', function () {
    $templateContent = '{% if value is null %}Value is null{% endif %}';
    $templateFile = $this->testTemplateDir . '/test_null.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('test_null.twig', ['value' => null]);

    expect($result)->toBe('Value is null');
});

test('test is empty works', function () {
    $templateContent = '{% if items is empty %}No items{% endif %}';
    $templateFile = $this->testTemplateDir . '/test_empty.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('test_empty.twig', ['items' => []]);

    expect($result)->toBe('No items');
});

test('test is even works', function () {
    $templateContent = '{% if number is even %}Even{% else %}Odd{% endif %}';
    $templateFile = $this->testTemplateDir . '/test_even.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('test_even.twig', ['number' => 4]);

    expect($result)->toBe('Even');
});

test('test is odd works', function () {
    $templateContent = '{% if number is odd %}Odd{% else %}Even{% endif %}';
    $templateFile = $this->testTemplateDir . '/test_odd.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('test_odd.twig', ['number' => 5]);

    expect($result)->toBe('Odd');
});

test('test is string works', function () {
    $templateContent = '{% if value is string %}String{% else %}Not string{% endif %}';
    $templateFile = $this->testTemplateDir . '/test_string.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('test_string.twig', ['value' => 'hello']);

    expect($result)->toBe('String');
});

test('test is number works', function () {
    $templateContent = '{% if value is number %}Number{% else %}Not number{% endif %}';
    $templateFile = $this->testTemplateDir . '/test_number.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('test_number.twig', ['value' => 42]);

    expect($result)->toBe('Number');
});

test('test is array works', function () {
    $templateContent = '{% if value is array %}Array{% else %}Not array{% endif %}';
    $templateFile = $this->testTemplateDir . '/test_array.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('test_array.twig', ['value' => [1, 2, 3]]);

    expect($result)->toBe('Array');
});

test('for else works with empty array', function () {
    $templateContent = '{% for item in items %}<li>{{ item }}</li>{% else %}<p>No items</p>{% endfor %}';
    $templateFile = $this->testTemplateDir . '/for_else_empty.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('for_else_empty.twig', ['items' => []]);

    expect($result)->toBe('<p>No items</p>');
});

test('for else works with non-empty array', function () {
    $templateContent = '{% for item in items %}<li>{{ item }}</li>{% else %}<p>No items</p>{% endfor %}';
    $templateFile = $this->testTemplateDir . '/for_else_items.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('for_else_items.twig', ['items' => ['a', 'b']]);

    expect($result)->toBe('<li>a</li><li>b</li>');
});

test('for else works with destructuring', function () {
    $templateContent = '{% for key, value in items %}{{ key }}:{{ value }},{% else %}Empty{% endfor %}';
    $templateFile = $this->testTemplateDir . '/for_else_destruct.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('for_else_destruct.twig', ['items' => []]);
    expect($result1)->toBe('Empty');
    
    $result2 = $engine->render('for_else_destruct.twig', ['items' => ['a' => '1', 'b' => '2']]);
    expect($result2)->toBe('a:1,b:2,');
});

test('ternary operator works with simple values', function () {
    $templateContent = '{{ isAdmin ? "Admin" : "User" }}';
    $templateFile = $this->testTemplateDir . '/ternary_simple.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('ternary_simple.twig', ['isAdmin' => true]);
    expect($result1)->toBe('Admin');
    
    $result2 = $engine->render('ternary_simple.twig', ['isAdmin' => false]);
    expect($result2)->toBe('User');
});

test('ternary operator works with variables', function () {
    $templateContent = '{{ user ? user : "Guest" }}';
    $templateFile = $this->testTemplateDir . '/ternary_var.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('ternary_var.twig', ['user' => 'John']);
    expect($result1)->toBe('John');
    
    $result2 = $engine->render('ternary_var.twig', ['user' => null]);
    expect($result2)->toBe('Guest');
});

test('ternary operator works with numbers', function () {
    $templateContent = '{{ count ? count : 0 }}';
    $templateFile = $this->testTemplateDir . '/ternary_number.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('ternary_number.twig', ['count' => 5]);
    expect($result1)->toBe('5');
    
    $result2 = $engine->render('ternary_number.twig', ['count' => 0]);
    expect($result2)->toBe('0');
});

test('ternary operator works in set', function () {
    $templateContent = '{% set greeting = isAdmin ? "Welcome Admin" : "Welcome User" %}{{ greeting }}';
    $templateFile = $this->testTemplateDir . '/ternary_set.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result = $engine->render('ternary_set.twig', ['isAdmin' => true]);
    expect($result)->toBe('Welcome Admin');
});

test('in operator works with arrays', function () {
    $templateContent = '{% if item in items %}Found{% else %}Not found{% endif %}';
    $templateFile = $this->testTemplateDir . '/in_array.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('in_array.twig', ['item' => 'apple', 'items' => ['apple', 'banana', 'orange']]);
    expect($result1)->toBe('Found');
    
    $result2 = $engine->render('in_array.twig', ['item' => 'grape', 'items' => ['apple', 'banana', 'orange']]);
    expect($result2)->toBe('Not found');
});

test('in operator works with strings', function () {
    $templateContent = '{% if needle in haystack %}Contains{% else %}Not contains{% endif %}';
    $templateFile = $this->testTemplateDir . '/in_string.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('in_string.twig', ['needle' => 'world', 'haystack' => 'Hello world']);
    expect($result1)->toBe('Contains');
    
    $result2 = $engine->render('in_string.twig', ['needle' => 'foo', 'haystack' => 'Hello world']);
    expect($result2)->toBe('Not contains');
});

test('not in operator works with arrays', function () {
    $templateContent = '{% if item not in items %}Not in list{% else %}In list{% endif %}';
    $templateFile = $this->testTemplateDir . '/not_in_array.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $result1 = $engine->render('not_in_array.twig', ['item' => 'grape', 'items' => ['apple', 'banana']]);
    expect($result1)->toBe('Not in list');
    
    $result2 = $engine->render('not_in_array.twig', ['item' => 'apple', 'items' => ['apple', 'banana']]);
    expect($result2)->toBe('In list');
});

test('in operator works with literal arrays', function () {
    $templateContent = '{% set role = "admin" %}{% if role in ["admin", "moderator"] %}Access granted{% endif %}';
    $templateFile = $this->testTemplateDir . '/in_literal_array.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('in_literal_array.twig');

    expect($result)->toBe('Access granted');
});
