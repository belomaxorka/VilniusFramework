<?php declare(strict_types=1);

use Core\TemplateEngine;

beforeEach(function () {
    // Создаем временную директорию для тестов
    $this->testTemplateDir = sys_get_temp_dir() . '/vilnius_templates_filters_conditions_test';
    $this->testCacheDir = sys_get_temp_dir() . '/torrentpier_cache_filters_conditions_test';

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

// ============================================
// Тесты для фильтров в условиях if
// ============================================

describe('Filters in IF conditions', function () {
    test('can use length filter in if condition', function () {
        $templateContent = '{% if users|length > 0 %}Has users{% else %}No users{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_length.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_length.twig', ['users' => ['John', 'Jane', 'Bob']]);
        expect($result)->toBe('Has users');
        
        $result = $engine->render('if_length.twig', ['users' => []]);
        expect($result)->toBe('No users');
    });

    test('can use count filter in if condition', function () {
        $templateContent = '{% if items|count > 5 %}Many items{% else %}Few items{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_count.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_count.twig', ['items' => range(1, 10)]);
        expect($result)->toBe('Many items');
        
        $result = $engine->render('if_count.twig', ['items' => [1, 2, 3]]);
        expect($result)->toBe('Few items');
    });

    test('can use upper filter in if condition', function () {
        $templateContent = '{% if name|upper == "JOHN" %}Match{% else %}No match{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_upper.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_upper.twig', ['name' => 'john']);
        expect($result)->toBe('Match');
        
        $result = $engine->render('if_upper.twig', ['name' => 'jane']);
        expect($result)->toBe('No match');
    });

    test('can use lower filter in if condition', function () {
        $templateContent = '{% if name|lower == "admin" %}Admin{% else %}User{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_lower.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_lower.twig', ['name' => 'ADMIN']);
        expect($result)->toBe('Admin');
    });

    test('can use trim filter in if condition', function () {
        $templateContent = '{% if text|trim != "" %}Has text{% else %}Empty{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_trim.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_trim.twig', ['text' => '  Hello  ']);
        expect($result)->toBe('Has text');
        
        $result = $engine->render('if_trim.twig', ['text' => '   ']);
        expect($result)->toBe('Empty');
    });

    test('can use abs filter in if condition', function () {
        $templateContent = '{% if number|abs > 10 %}Big{% else %}Small{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_abs.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_abs.twig', ['number' => -15]);
        expect($result)->toBe('Big');
        
        $result = $engine->render('if_abs.twig', ['number' => -5]);
        expect($result)->toBe('Small');
    });

    test('can use first filter in if condition', function () {
        $templateContent = '{% if items|first == "apple" %}Apple first{% else %}Other{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_first.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_first.twig', ['items' => ['apple', 'banana', 'cherry']]);
        expect($result)->toBe('Apple first');
        
        $result = $engine->render('if_first.twig', ['items' => ['banana', 'apple', 'cherry']]);
        expect($result)->toBe('Other');
    });

    test('can use last filter in if condition', function () {
        $templateContent = '{% if items|last == "end" %}Ends with end{% else %}Other{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_last.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_last.twig', ['items' => ['start', 'middle', 'end']]);
        expect($result)->toBe('Ends with end');
    });
});

describe('Multiple filters in IF conditions', function () {
    test('can chain multiple filters in if condition', function () {
        $templateContent = '{% if name|trim|upper == "JOHN" %}Match{% else %}No match{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_chain.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_chain.twig', ['name' => '  john  ']);
        expect($result)->toBe('Match');
    });

    test('can use filter with arguments in if condition', function () {
        $templateContent = '{% if text|slice(0, 5) == "Hello" %}Starts with Hello{% else %}Other{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_filter_args.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_filter_args.twig', ['text' => 'Hello World']);
        expect($result)->toBe('Starts with Hello');
    });

    test('can use filters in complex if conditions with and', function () {
        $templateContent = '{% if users|length > 0 and name|upper == "ADMIN" %}OK{% else %}NO{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_complex_and.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_complex_and.twig', [
            'users' => ['John', 'Jane'],
            'name' => 'admin'
        ]);
        expect($result)->toBe('OK');
        
        $result = $engine->render('if_complex_and.twig', [
            'users' => [],
            'name' => 'admin'
        ]);
        expect($result)->toBe('NO');
    });

    test('can use filters in complex if conditions with or', function () {
        $templateContent = '{% if items|length > 10 or status|upper == "ACTIVE" %}Show{% else %}Hide{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_complex_or.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_complex_or.twig', [
            'items' => [1, 2, 3],
            'status' => 'active'
        ]);
        expect($result)->toBe('Show');
        
        $result = $engine->render('if_complex_or.twig', [
            'items' => range(1, 15),
            'status' => 'inactive'
        ]);
        expect($result)->toBe('Show');
    });
});

describe('Filters in ELSEIF conditions', function () {
    test('can use filters in elseif condition', function () {
        $templateContent = '{% if count|length > 10 %}Many{% elseif count|length > 5 %}Some{% else %}Few{% endif %}';
        $templateFile = $this->testTemplateDir . '/elseif_filter.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('elseif_filter.twig', ['count' => range(1, 12)]);
        expect($result)->toBe('Many');
        
        $result = $engine->render('elseif_filter.twig', ['count' => range(1, 7)]);
        expect($result)->toBe('Some');
        
        $result = $engine->render('elseif_filter.twig', ['count' => [1, 2, 3]]);
        expect($result)->toBe('Few');
    });

    test('can use different filters in if and elseif', function () {
        $templateContent = '{% if name|upper == "ADMIN" %}Admin{% elseif name|length > 10 %}Long{% else %}Normal{% endif %}';
        $templateFile = $this->testTemplateDir . '/elseif_different.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('elseif_different.twig', ['name' => 'admin']);
        expect($result)->toBe('Admin');
        
        $result = $engine->render('elseif_different.twig', ['name' => 'verylongusername']);
        expect($result)->toBe('Long');
        
        $result = $engine->render('elseif_different.twig', ['name' => 'john']);
        expect($result)->toBe('Normal');
    });
});

describe('Edge cases for filters in conditions', function () {
    test('can use filter on empty array in if condition', function () {
        $templateContent = '{% if items|length == 0 %}Empty{% else %}Not empty{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_empty_array.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_empty_array.twig', ['items' => []]);
        expect($result)->toBe('Empty');
    });

    test('can use filter with comparison operators', function () {
        $templateContent = '{% if price|abs >= 100 %}Expensive{% else %}Affordable{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_comparison.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_comparison.twig', ['price' => -150]);
        expect($result)->toBe('Expensive');
    });

    test('can use filter with not equals operator', function () {
        $templateContent = '{% if status|upper != "ACTIVE" %}Inactive{% else %}Active{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_not_equals.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_not_equals.twig', ['status' => 'pending']);
        expect($result)->toBe('Inactive');
        
        $result = $engine->render('if_not_equals.twig', ['status' => 'active']);
        expect($result)->toBe('Active');
    });

    test('can use filter in nested if conditions', function () {
        $templateContent = '{% if users|length > 0 %}{% if users|first|upper == "ADMIN" %}Admin first{% else %}Normal{% endif %}{% else %}No users{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_nested.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_nested.twig', ['users' => ['admin', 'john']]);
        expect($result)->toBe('Admin first');
        
        $result = $engine->render('if_nested.twig', ['users' => ['john', 'admin']]);
        expect($result)->toBe('Normal');
        
        $result = $engine->render('if_nested.twig', ['users' => []]);
        expect($result)->toBe('No users');
    });
});

describe('Arithmetic operations with filters in conditions', function () {
    test('can use filter with addition in if condition', function () {
        $templateContent = '{% set count = items|length %}{% if count + 5 > 10 %}More than 5 items{% else %}5 or less{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_addition.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_addition.twig', ['items' => range(1, 7)]);
        expect($result)->toBe('More than 5 items');
        
        $result = $engine->render('if_addition.twig', ['items' => [1, 2, 3]]);
        expect($result)->toBe('5 or less');
    });

    test('can use filter in parenthesized expression', function () {
        $templateContent = '{% if (items|length) > 5 %}Many{% else %}Few{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_parenthesized.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_parenthesized.twig', ['items' => range(1, 10)]);
        expect($result)->toBe('Many');
        
        $result = $engine->render('if_parenthesized.twig', ['items' => [1, 2, 3]]);
        expect($result)->toBe('Few');
    });

    test('can compare two filtered values', function () {
        $templateContent = '{% if name1|upper == name2|upper %}Same{% else %}Different{% endif %}';
        $templateFile = $this->testTemplateDir . '/if_two_filters.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('if_two_filters.twig', ['name1' => 'john', 'name2' => 'JOHN']);
        expect($result)->toBe('Same');
        
        $result = $engine->render('if_two_filters.twig', ['name1' => 'john', 'name2' => 'jane']);
        expect($result)->toBe('Different');
    });
});

describe('Real-world use cases', function () {
    test('can check if user has posts using length filter', function () {
        $templateContent = '{% if posts|length > 0 %}{{ posts|length }} posts found{% else %}No posts{% endif %}';
        $templateFile = $this->testTemplateDir . '/user_posts.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('user_posts.twig', ['posts' => ['Post 1', 'Post 2', 'Post 3']]);
        expect($result)->toBe('3 posts found');
        
        $result = $engine->render('user_posts.twig', ['posts' => []]);
        expect($result)->toBe('No posts');
    });

    test('can validate email format using filters', function () {
        $templateContent = '{% if email|lower|trim != "" %}Valid{% else %}Invalid{% endif %}';
        $templateFile = $this->testTemplateDir . '/email_check.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('email_check.twig', ['email' => '  USER@EXAMPLE.COM  ']);
        expect($result)->toBe('Valid');
    });

    test('can check pagination using length filter', function () {
        $templateContent = '{% if items|length > 10 %}Showing first 10 of {{ items|length }}{% endif %}';
        $templateFile = $this->testTemplateDir . '/pagination.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('pagination.twig', ['items' => range(1, 15)]);
        expect($result)->toBe('Showing first 10 of 15');
        
        $result = $engine->render('pagination.twig', ['items' => range(1, 5)]);
        expect($result)->toBe('');
    });

    test('can show different messages based on array size', function () {
        $templateContent = '{% if users|length == 0 %}No users{% elseif users|length == 1 %}One user{% else %}{{ users|length }} users{% endif %}';
        $templateFile = $this->testTemplateDir . '/user_count.twig';
        file_put_contents($templateFile, $templateContent);

        $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);
        
        $result = $engine->render('user_count.twig', ['users' => []]);
        expect($result)->toBe('No users');
        
        $result = $engine->render('user_count.twig', ['users' => ['John']]);
        expect($result)->toBe('One user');
        
        $result = $engine->render('user_count.twig', ['users' => ['John', 'Jane', 'Bob']]);
        expect($result)->toBe('3 users');
    });
});

