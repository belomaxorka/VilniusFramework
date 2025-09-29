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

// Тесты для текстовых фильтров

test('can apply upper filter', function () {
    $templateContent = '{{ name|upper }}';
    $templateFile = $this->testTemplateDir . '/upper.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('upper.tpl', ['name' => 'john']);
    
    expect($result)->toBe('JOHN');
});

test('can apply lower filter', function () {
    $templateContent = '{{ name|lower }}';
    $templateFile = $this->testTemplateDir . '/lower.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('lower.tpl', ['name' => 'JOHN']);
    
    expect($result)->toBe('john');
});

test('can apply capitalize filter', function () {
    $templateContent = '{{ text|capitalize }}';
    $templateFile = $this->testTemplateDir . '/capitalize.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('capitalize.tpl', ['text' => 'hello world']);
    
    expect($result)->toBe('Hello World');
});

test('can apply trim filter', function () {
    $templateContent = '{{ text|trim }}';
    $templateFile = $this->testTemplateDir . '/trim.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('trim.tpl', ['text' => '  hello  ']);
    
    expect($result)->toBe('hello');
});

// Тесты для числовых фильтров

test('can apply abs filter', function () {
    $templateContent = '{{ number|abs }}';
    $templateFile = $this->testTemplateDir . '/abs.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('abs.tpl', ['number' => -42]);
    
    expect($result)->toBe('42');
});

test('can apply round filter', function () {
    $templateContent = '{{ number|round(2) }}';
    $templateFile = $this->testTemplateDir . '/round.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('round.tpl', ['number' => 3.14159]);
    
    expect($result)->toBe('3.14');
});

test('can apply number_format filter', function () {
    $templateContent = '{{ price|number_format(2, ".", ",") }}';
    $templateFile = $this->testTemplateDir . '/number_format.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('number_format.tpl', ['price' => 1234.56]);
    
    expect($result)->toBe('1,234.56');
});

// Тесты для массивов

test('can apply length filter on array', function () {
    $templateContent = '{{ items|length }}';
    $templateFile = $this->testTemplateDir . '/length.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('length.tpl', ['items' => ['a', 'b', 'c']]);
    
    expect($result)->toBe('3');
});

test('can apply length filter on string', function () {
    $templateContent = '{{ text|length }}';
    $templateFile = $this->testTemplateDir . '/string_length.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('string_length.tpl', ['text' => 'hello']);
    
    expect($result)->toBe('5');
});

test('can apply join filter', function () {
    $templateContent = '{{ items|join(", ") }}';
    $templateFile = $this->testTemplateDir . '/join.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('join.tpl', ['items' => ['apple', 'banana', 'cherry']]);
    
    expect($result)->toBe('apple, banana, cherry');
});

test('can apply first filter', function () {
    $templateContent = '{{ items|first }}';
    $templateFile = $this->testTemplateDir . '/first.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('first.tpl', ['items' => ['apple', 'banana', 'cherry']]);
    
    expect($result)->toBe('apple');
});

test('can apply last filter', function () {
    $templateContent = '{{ items|last }}';
    $templateFile = $this->testTemplateDir . '/last.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('last.tpl', ['items' => ['apple', 'banana', 'cherry']]);
    
    expect($result)->toBe('cherry');
});

// Тесты для строковых фильтров

test('can apply truncate filter', function () {
    $templateContent = '{{ text|truncate(10, "...") }}';
    $templateFile = $this->testTemplateDir . '/truncate.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('truncate.tpl', ['text' => 'This is a very long text']);
    
    expect($result)->toBe('This is a ...');
});

test('can apply replace filter', function () {
    $templateContent = '{{ text|replace("world", "PHP") }}';
    $templateFile = $this->testTemplateDir . '/replace.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('replace.tpl', ['text' => 'Hello world']);
    
    expect($result)->toBe('Hello PHP');
});

// Тесты для HTML фильтров

test('can apply striptags filter', function () {
    $templateContent = '{! html|striptags !}';
    $templateFile = $this->testTemplateDir . '/striptags.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('striptags.tpl', ['html' => '<p>Hello <b>world</b></p>']);
    
    expect($result)->toBe('Hello world');
});

test('can apply nl2br filter', function () {
    $templateContent = '{! text|nl2br !}';
    $templateFile = $this->testTemplateDir . '/nl2br.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('nl2br.tpl', ['text' => "Line 1\nLine 2"]);
    
    expect($result)->toContain('<br />');
});

// Тесты для дефолтных значений

test('can apply default filter with empty value', function () {
    $templateContent = '{{ name|default("Guest") }}';
    $templateFile = $this->testTemplateDir . '/default.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('default.tpl', ['name' => '']);
    
    expect($result)->toBe('Guest');
});

test('can apply default filter with non-empty value', function () {
    $templateContent = '{{ name|default("Guest") }}';
    $templateFile = $this->testTemplateDir . '/default_filled.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('default_filled.tpl', ['name' => 'John']);
    
    expect($result)->toBe('John');
});

// Тесты для цепочек фильтров

test('can apply multiple filters in chain', function () {
    $templateContent = '{{ name|trim|upper }}';
    $templateFile = $this->testTemplateDir . '/chain.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('chain.tpl', ['name' => '  john  ']);
    
    expect($result)->toBe('JOHN');
});

test('can apply multiple filters with arguments', function () {
    $templateContent = '{{ text|truncate(10, "...")|upper }}';
    $templateFile = $this->testTemplateDir . '/chain_args.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('chain_args.tpl', ['text' => 'hello world']);
    
    expect($result)->toBe('HELLO WORL...');
});

// Тесты для пользовательских фильтров

test('can add custom filter', function () {
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $engine->addFilter('double', fn($value) => $value * 2);
    
    $templateContent = '{{ number|double }}';
    $templateFile = $this->testTemplateDir . '/custom.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $result = $engine->render('custom.tpl', ['number' => 5]);
    
    expect($result)->toBe('10');
});

test('can add custom filter with arguments', function () {
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $engine->addFilter('repeat', fn($value, $times) => str_repeat($value, $times));
    
    $templateContent = '{{ text|repeat(3) }}';
    $templateFile = $this->testTemplateDir . '/custom_args.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $result = $engine->render('custom_args.tpl', ['text' => 'Ha']);
    
    expect($result)->toBe('HaHaHa');
});

test('can check if filter exists', function () {
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    expect($engine->hasFilter('upper'))->toBeTrue();
    expect($engine->hasFilter('nonexistent'))->toBeFalse();
});

test('throws exception for undefined filter', function () {
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $templateContent = '{{ name|nonexistent }}';
    $templateFile = $this->testTemplateDir . '/undefined_filter.tpl';
    file_put_contents($templateFile, $templateContent);
    
    expect(fn() => $engine->render('undefined_filter.tpl', ['name' => 'John']))
        ->toThrow(InvalidArgumentException::class);
});

// Тесты для JSON фильтров

test('can apply json filter', function () {
    $templateContent = '{! data|json !}';
    $templateFile = $this->testTemplateDir . '/json.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('json.tpl', ['data' => ['name' => 'John', 'age' => 30]]);
    
    expect($result)->toBe('{"name":"John","age":30}');
});

// Тесты для URL фильтров

test('can apply url_encode filter', function () {
    $templateContent = '{{ url|url_encode }}';
    $templateFile = $this->testTemplateDir . '/url_encode.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('url_encode.tpl', ['url' => 'hello world']);
    
    expect($result)->toBe('hello+world');
});

// Тесты для date фильтра

test('can apply date filter with timestamp', function () {
    $templateContent = '{{ timestamp|date("Y-m-d") }}';
    $templateFile = $this->testTemplateDir . '/date.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    // Используем текущую дату для надежности
    $timestamp = strtotime('2024-01-15 00:00:00');
    $expected = date('Y-m-d', $timestamp);
    
    $result = $engine->render('date.tpl', ['timestamp' => $timestamp]);
    
    expect($result)->toBe($expected);
});

test('can apply date filter with custom format', function () {
    $templateContent = '{{ timestamp|date("d/m/Y H:i") }}';
    $templateFile = $this->testTemplateDir . '/date_format.tpl';
    file_put_contents($templateFile, $templateContent);
    
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    
    $timestamp = strtotime('2024-01-15 14:30:00');
    $expected = date('d/m/Y H:i', $timestamp);
    
    $result = $engine->render('date_format.tpl', ['timestamp' => $timestamp]);
    
    expect($result)->toBe($expected);
});
