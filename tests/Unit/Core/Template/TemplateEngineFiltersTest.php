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
    $templateFile = $this->testTemplateDir . '/upper.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('upper.twig', ['name' => 'john']);

    expect($result)->toBe('JOHN');
});

test('can apply lower filter', function () {
    $templateContent = '{{ name|lower }}';
    $templateFile = $this->testTemplateDir . '/lower.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('lower.twig', ['name' => 'JOHN']);

    expect($result)->toBe('john');
});

test('can apply capitalize filter', function () {
    $templateContent = '{{ text|capitalize }}';
    $templateFile = $this->testTemplateDir . '/capitalize.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('capitalize.twig', ['text' => 'hello world']);

    expect($result)->toBe('Hello World');
});

test('can apply trim filter', function () {
    $templateContent = '{{ text|trim }}';
    $templateFile = $this->testTemplateDir . '/trim.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('trim.twig', ['text' => '  hello  ']);

    expect($result)->toBe('hello');
});

// Тесты для числовых фильтров

test('can apply abs filter', function () {
    $templateContent = '{{ number|abs }}';
    $templateFile = $this->testTemplateDir . '/abs.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('abs.twig', ['number' => -42]);

    expect($result)->toBe('42');
});

test('can apply round filter', function () {
    $templateContent = '{{ number|round(2) }}';
    $templateFile = $this->testTemplateDir . '/round.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('round.twig', ['number' => 3.14159]);

    expect($result)->toBe('3.14');
});

test('can apply number_format filter', function () {
    $templateContent = '{{ price|number_format(2, ".", ",") }}';
    $templateFile = $this->testTemplateDir . '/number_format.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('number_format.twig', ['price' => 1234.56]);

    expect($result)->toBe('1,234.56');
});

// Тесты для массивов

test('can apply length filter on array', function () {
    $templateContent = '{{ items|length }}';
    $templateFile = $this->testTemplateDir . '/length.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('length.twig', ['items' => ['a', 'b', 'c']]);

    expect($result)->toBe('3');
});

test('can apply length filter on string', function () {
    $templateContent = '{{ text|length }}';
    $templateFile = $this->testTemplateDir . '/string_length.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('string_length.twig', ['text' => 'hello']);

    expect($result)->toBe('5');
});

test('can apply join filter', function () {
    $templateContent = '{{ items|join(", ") }}';
    $templateFile = $this->testTemplateDir . '/join.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('join.twig', ['items' => ['apple', 'banana', 'cherry']]);

    expect($result)->toBe('apple, banana, cherry');
});

test('can apply first filter', function () {
    $templateContent = '{{ items|first }}';
    $templateFile = $this->testTemplateDir . '/first.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('first.twig', ['items' => ['apple', 'banana', 'cherry']]);

    expect($result)->toBe('apple');
});

test('can apply last filter', function () {
    $templateContent = '{{ items|last }}';
    $templateFile = $this->testTemplateDir . '/last.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('last.twig', ['items' => ['apple', 'banana', 'cherry']]);

    expect($result)->toBe('cherry');
});

// Тесты для строковых фильтров

test('can apply truncate filter', function () {
    $templateContent = '{{ text|truncate(10, "...") }}';
    $templateFile = $this->testTemplateDir . '/truncate.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('truncate.twig', ['text' => 'This is a very long text']);

    expect($result)->toBe('This is a ...');
});

test('can apply replace filter', function () {
    $templateContent = '{{ text|replace("world", "PHP") }}';
    $templateFile = $this->testTemplateDir . '/replace.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('replace.twig', ['text' => 'Hello world']);

    expect($result)->toBe('Hello PHP');
});

// Тесты для HTML фильтров

test('can apply striptags filter', function () {
    $templateContent = '{! html|striptags !}';
    $templateFile = $this->testTemplateDir . '/striptags.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('striptags.twig', ['html' => '<p>Hello <b>world</b></p>']);

    expect($result)->toBe('Hello world');
});

test('can apply nl2br filter', function () {
    $templateContent = '{! text|nl2br !}';
    $templateFile = $this->testTemplateDir . '/nl2br.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('nl2br.twig', ['text' => "Line 1\nLine 2"]);

    expect($result)->toContain('<br />');
});

// Тесты для дефолтных значений

test('can apply default filter with empty value', function () {
    $templateContent = '{{ name|default("Guest") }}';
    $templateFile = $this->testTemplateDir . '/default.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('default.twig', ['name' => '']);

    expect($result)->toBe('Guest');
});

test('can apply default filter with non-empty value', function () {
    $templateContent = '{{ name|default("Guest") }}';
    $templateFile = $this->testTemplateDir . '/default_filled.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('default_filled.twig', ['name' => 'John']);

    expect($result)->toBe('John');
});

// Тесты для цепочек фильтров

test('can apply multiple filters in chain', function () {
    $templateContent = '{{ name|trim|upper }}';
    $templateFile = $this->testTemplateDir . '/chain.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('chain.twig', ['name' => '  john  ']);

    expect($result)->toBe('JOHN');
});

test('can apply multiple filters with arguments', function () {
    $templateContent = '{{ text|truncate(10, "...")|upper }}';
    $templateFile = $this->testTemplateDir . '/chain_args.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('chain_args.twig', ['text' => 'hello world']);

    expect($result)->toBe('HELLO WORL...');
});

// Тесты для пользовательских фильтров

test('can add custom filter', function () {
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $engine->addFilter('double', fn($value) => $value * 2);

    $templateContent = '{{ number|double }}';
    $templateFile = $this->testTemplateDir . '/custom.twig';
    file_put_contents($templateFile, $templateContent);

    $result = $engine->render('custom.twig', ['number' => 5]);

    expect($result)->toBe('10');
});

test('can add custom filter with arguments', function () {
    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $engine->addFilter('repeat', fn($value, $times) => str_repeat($value, $times));

    $templateContent = '{{ text|repeat(3) }}';
    $templateFile = $this->testTemplateDir . '/custom_args.twig';
    file_put_contents($templateFile, $templateContent);

    $result = $engine->render('custom_args.twig', ['text' => 'Ha']);

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
    $templateFile = $this->testTemplateDir . '/undefined_filter.twig';
    file_put_contents($templateFile, $templateContent);

    expect(fn() => $engine->render('undefined_filter.twig', ['name' => 'John']))
        ->toThrow(InvalidArgumentException::class);
});

// Тесты для JSON фильтров

test('can apply json filter', function () {
    $templateContent = '{! data|json !}';
    $templateFile = $this->testTemplateDir . '/json.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('json.twig', ['data' => ['name' => 'John', 'age' => 30]]);

    expect($result)->toBe('{"name":"John","age":30}');
});

// Тесты для URL фильтров

test('can apply url_encode filter', function () {
    $templateContent = '{{ url|url_encode }}';
    $templateFile = $this->testTemplateDir . '/url_encode.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
    $result = $engine->render('url_encode.twig', ['url' => 'hello world']);

    expect($result)->toBe('hello+world');
});

// Тесты для date фильтра

test('can apply date filter with timestamp', function () {
    $templateContent = '{{ timestamp|date("Y-m-d") }}';
    $templateFile = $this->testTemplateDir . '/date.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    // Используем текущую дату для надежности
    $timestamp = strtotime('2024-01-15 00:00:00');
    $expected = date('Y-m-d', $timestamp);

    $result = $engine->render('date.twig', ['timestamp' => $timestamp]);

    expect($result)->toBe($expected);
});

test('can apply date filter with custom format', function () {
    $templateContent = '{{ timestamp|date("d/m/Y H:i") }}';
    $templateFile = $this->testTemplateDir . '/date_format.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $timestamp = strtotime('2024-01-15 14:30:00');
    $expected = date('d/m/Y H:i', $timestamp);

    $result = $engine->render('date_format.twig', ['timestamp' => $timestamp]);

    expect($result)->toBe($expected);
});
