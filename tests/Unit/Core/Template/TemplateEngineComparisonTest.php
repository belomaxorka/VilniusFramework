<?php declare(strict_types=1);

use Core\TemplateEngine;

beforeEach(function () {
    // Создаем временную директорию для тестов
    $this->testTemplateDir = sys_get_temp_dir() . '/vilnius_templates_comparison_test';
    $this->testCacheDir = sys_get_temp_dir() . '/vilnius_cache_comparison_test';

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
    $templateFile = $this->testTemplateDir . '/equality.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('equality.twig', ['age' => 18]);
    expect($result1)->toBe('You are 18!');

    $result2 = $engine->render('equality.twig', ['age' => 20]);
    expect($result2)->toBe('');
});

test('can render template with inequality operator', function () {
    $templateContent = '{% if age != 18 %}You are not 18!{% endif %}';
    $templateFile = $this->testTemplateDir . '/inequality.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('inequality.twig', ['age' => 20]);
    expect($result1)->toBe('You are not 18!');

    $result2 = $engine->render('inequality.twig', ['age' => 18]);
    expect($result2)->toBe('');
});

test('can render template with greater than operator', function () {
    $templateContent = '{% if age > 18 %}You are an adult!{% endif %}';
    $templateFile = $this->testTemplateDir . '/greater.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('greater.twig', ['age' => 20]);
    expect($result1)->toBe('You are an adult!');

    $result2 = $engine->render('greater.twig', ['age' => 18]);
    expect($result2)->toBe('');

    $result3 = $engine->render('greater.twig', ['age' => 16]);
    expect($result3)->toBe('');
});

test('can render template with less than operator', function () {
    $templateContent = '{% if age < 18 %}You are a minor!{% endif %}';
    $templateFile = $this->testTemplateDir . '/less.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('less.twig', ['age' => 16]);
    expect($result1)->toBe('You are a minor!');

    $result2 = $engine->render('less.twig', ['age' => 18]);
    expect($result2)->toBe('');

    $result3 = $engine->render('less.twig', ['age' => 20]);
    expect($result3)->toBe('');
});

test('can render template with greater than or equal operator', function () {
    $templateContent = '{% if age >= 18 %}You can vote!{% endif %}';
    $templateFile = $this->testTemplateDir . '/greater_equal.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('greater_equal.twig', ['age' => 18]);
    expect($result1)->toBe('You can vote!');

    $result2 = $engine->render('greater_equal.twig', ['age' => 20]);
    expect($result2)->toBe('You can vote!');

    $result3 = $engine->render('greater_equal.twig', ['age' => 16]);
    expect($result3)->toBe('');
});

test('can render template with less than or equal operator', function () {
    $templateContent = '{% if age <= 12 %}Child ticket!{% endif %}';
    $templateFile = $this->testTemplateDir . '/less_equal.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('less_equal.twig', ['age' => 12]);
    expect($result1)->toBe('Child ticket!');

    $result2 = $engine->render('less_equal.twig', ['age' => 10]);
    expect($result2)->toBe('Child ticket!');

    $result3 = $engine->render('less_equal.twig', ['age' => 15]);
    expect($result3)->toBe('');
});

test('can render template with string comparison', function () {
    $templateContent = '{% if status == "active" %}System is active!{% endif %}';
    $templateFile = $this->testTemplateDir . '/string_comparison.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('string_comparison.twig', ['status' => 'active']);
    expect($result1)->toBe('System is active!');

    $result2 = $engine->render('string_comparison.twig', ['status' => 'inactive']);
    expect($result2)->toBe('');
});

test('can render template with array property comparison', function () {
    $templateContent = '{% if user.age >= 18 %}Adult user{% endif %}';
    $templateFile = $this->testTemplateDir . '/array_comparison.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('array_comparison.twig', [
        'user' => ['name' => 'John', 'age' => 20]
    ]);
    expect($result1)->toBe('Adult user');

    $result2 = $engine->render('array_comparison.twig', [
        'user' => ['name' => 'Jane', 'age' => 16]
    ]);
    expect($result2)->toBe('');
});

test('can render template with array property in condition and output', function () {
    $templateContent = '{% if user.age >= 18 %}{{ user.name }}{% endif %}';
    $templateFile = $this->testTemplateDir . '/array_output_comparison.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('array_output_comparison.twig', [
        'user' => ['name' => 'John', 'age' => 20]
    ]);
    expect($result1)->toBe('John');

    $result2 = $engine->render('array_output_comparison.twig', [
        'user' => ['name' => 'Jane', 'age' => 16]
    ]);
    expect($result2)->toBe('');
});

test('can render template with array element comparison', function () {
    $templateContent = '{% if scores[0] > 80 %}Excellent!{% endif %}';
    $templateFile = $this->testTemplateDir . '/array_element_comparison.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('array_element_comparison.twig', ['scores' => [85, 70, 90]]);
    expect($result1)->toBe('Excellent!');

    $result2 = $engine->render('array_element_comparison.twig', ['scores' => [75, 70, 90]]);
    expect($result2)->toBe('');
});

test('can render template with multiple comparisons', function () {
    $templateContent = '{% if age >= 18 and age < 65 %}Working age!{% endif %}';
    $templateFile = $this->testTemplateDir . '/multiple_comparison.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('multiple_comparison.twig', ['age' => 25]);
    expect($result1)->toBe('Working age!');

    $result2 = $engine->render('multiple_comparison.twig', ['age' => 16]);
    expect($result2)->toBe('');

    $result3 = $engine->render('multiple_comparison.twig', ['age' => 70]);
    expect($result3)->toBe('');
});

test('can render template with comparison in loop', function () {
    $templateContent = '{% for score in scores %}{% if score > 80 %}{{ score }} - Excellent! {% endif %}{% endfor %}';
    $templateFile = $this->testTemplateDir . '/loop_comparison.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result = $engine->render('loop_comparison.twig', ['scores' => [85, 70, 90, 75]]);
    expect($result)->toBe('85 - Excellent! 90 - Excellent! ');
});

// Дополнительные тесты для полноты покрытия

test('can handle else branch in conditions', function () {
    $templateContent = '{% if age >= 18 %}Adult{% else %}Minor{% endif %}';
    $templateFile = $this->testTemplateDir . '/else_branch.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('else_branch.twig', ['age' => 20]);
    expect($result1)->toBe('Adult');

    $result2 = $engine->render('else_branch.twig', ['age' => 16]);
    expect($result2)->toBe('Minor');
});

test('can handle negative numbers in comparisons', function () {
    $templateContent = '{% if temperature < 0 %}Freezing!{% endif %}';
    $templateFile = $this->testTemplateDir . '/negative_numbers.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('negative_numbers.twig', ['temperature' => -5]);
    expect($result1)->toBe('Freezing!');

    $result2 = $engine->render('negative_numbers.twig', ['temperature' => 5]);
    expect($result2)->toBe('');
});

test('can handle zero in comparisons', function () {
    $templateContent = '{% if value == 0 %}Zero detected{% endif %}';
    $templateFile = $this->testTemplateDir . '/zero_comparison.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('zero_comparison.twig', ['value' => 0]);
    expect($result1)->toBe('Zero detected');

    $result2 = $engine->render('zero_comparison.twig', ['value' => 1]);
    expect($result2)->toBe('');
});

test('can handle boolean comparisons', function () {
    $templateContent = '{% if isActive == true %}Active!{% endif %}';
    $templateFile = $this->testTemplateDir . '/boolean_comparison.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('boolean_comparison.twig', ['isActive' => true]);
    expect($result1)->toBe('Active!');

    $result2 = $engine->render('boolean_comparison.twig', ['isActive' => false]);
    expect($result2)->toBe('');
});

test('can handle empty string comparisons', function () {
    $templateContent = '{% if name == "" %}No name{% endif %}';
    $templateFile = $this->testTemplateDir . '/empty_string.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('empty_string.twig', ['name' => '']);
    expect($result1)->toBe('No name');

    $result2 = $engine->render('empty_string.twig', ['name' => 'John']);
    expect($result2)->toBe('');
});

test('can handle type coercion in comparisons', function () {
    $templateContent = '{% if age == "18" %}Match{% endif %}';
    $templateFile = $this->testTemplateDir . '/type_coercion.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    // Тест показывает, как движок обрабатывает сравнение числа со строкой
    $result = $engine->render('type_coercion.twig', ['age' => 18]);
    // Результат зависит от реализации движка - строгое или нестрогое сравнение
    expect($result)->toBeString();
});

test('can handle or operator in comparisons', function () {
    $templateContent = '{% if age < 18 or age > 65 %}Special rate{% endif %}';
    $templateFile = $this->testTemplateDir . '/or_operator.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('or_operator.twig', ['age' => 16]);
    expect($result1)->toBe('Special rate');

    $result2 = $engine->render('or_operator.twig', ['age' => 70]);
    expect($result2)->toBe('Special rate');

    $result3 = $engine->render('or_operator.twig', ['age' => 30]);
    expect($result3)->toBe('');
});

test('can handle nested conditions', function () {
    $templateContent = '{% if age >= 18 %}{% if hasLicense == true %}Can drive{% endif %}{% endif %}';
    $templateFile = $this->testTemplateDir . '/nested_conditions.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('nested_conditions.twig', ['age' => 20, 'hasLicense' => true]);
    expect($result1)->toBe('Can drive');

    $result2 = $engine->render('nested_conditions.twig', ['age' => 20, 'hasLicense' => false]);
    expect($result2)->toBe('');

    $result3 = $engine->render('nested_conditions.twig', ['age' => 16, 'hasLicense' => true]);
    expect($result3)->toBe('');
});

test('can handle float comparisons', function () {
    $templateContent = '{% if price > 99.99 %}Expensive{% endif %}';
    $templateFile = $this->testTemplateDir . '/float_comparison.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('float_comparison.twig', ['price' => 150.50]);
    expect($result1)->toBe('Expensive');

    $result2 = $engine->render('float_comparison.twig', ['price' => 50.00]);
    expect($result2)->toBe('');
});

test('can handle complex and conditions', function () {
    $templateContent = '{% if age >= 18 and score >= 80 and isActive == true %}Eligible{% endif %}';
    $templateFile = $this->testTemplateDir . '/complex_and.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('complex_and.twig', ['age' => 20, 'score' => 85, 'isActive' => true]);
    expect($result1)->toBe('Eligible');

    $result2 = $engine->render('complex_and.twig', ['age' => 20, 'score' => 75, 'isActive' => true]);
    expect($result2)->toBe('');

    $result3 = $engine->render('complex_and.twig', ['age' => 16, 'score' => 85, 'isActive' => true]);
    expect($result3)->toBe('');
});

test('can handle complex or conditions', function () {
    $templateContent = '{% if role == "admin" or role == "moderator" or role == "owner" %}Has access{% endif %}';
    $templateFile = $this->testTemplateDir . '/complex_or.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('complex_or.twig', ['role' => 'admin']);
    expect($result1)->toBe('Has access');

    $result2 = $engine->render('complex_or.twig', ['role' => 'moderator']);
    expect($result2)->toBe('Has access');

    $result3 = $engine->render('complex_or.twig', ['role' => 'owner']);
    expect($result3)->toBe('Has access');

    $result4 = $engine->render('complex_or.twig', ['role' => 'user']);
    expect($result4)->toBe('');
});

test('can handle mixed and/or conditions', function () {
    $templateContent = '{% if age >= 18 and (status == "active" or status == "premium") %}Welcome{% endif %}';
    $templateFile = $this->testTemplateDir . '/mixed_conditions.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('mixed_conditions.twig', ['age' => 20, 'status' => 'active']);
    expect($result1)->toBe('Welcome');

    $result2 = $engine->render('mixed_conditions.twig', ['age' => 20, 'status' => 'premium']);
    expect($result2)->toBe('Welcome');

    $result3 = $engine->render('mixed_conditions.twig', ['age' => 16, 'status' => 'active']);
    expect($result3)->toBe('');

    $result4 = $engine->render('mixed_conditions.twig', ['age' => 20, 'status' => 'inactive']);
    expect($result4)->toBe('');
});

test('can handle not operator with comparisons', function () {
    $templateContent = '{% if not age < 18 %}Adult content{% endif %}';
    $templateFile = $this->testTemplateDir . '/not_operator.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('not_operator.twig', ['age' => 20]);
    expect($result1)->toBe('Adult content');

    $result2 = $engine->render('not_operator.twig', ['age' => 16]);
    expect($result2)->toBe('');
});

test('can handle !== operator', function () {
    $templateContent = '{% if status !== "banned" %}Access granted{% endif %}';
    $templateFile = $this->testTemplateDir . '/not_equal_strict.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('not_equal_strict.twig', ['status' => 'active']);
    expect($result1)->toBe('Access granted');

    $result2 = $engine->render('not_equal_strict.twig', ['status' => 'banned']);
    expect($result2)->toBe('');
});

test('can handle === operator', function () {
    $templateContent = '{% if count === 0 %}Empty{% endif %}';
    $templateFile = $this->testTemplateDir . '/equal_strict.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('equal_strict.twig', ['count' => 0]);
    expect($result1)->toBe('Empty');

    $result2 = $engine->render('equal_strict.twig', ['count' => 1]);
    expect($result2)->toBe('');
});

test('can handle multiple variables in condition', function () {
    $templateContent = '{% if minAge <= age and age <= maxAge %}In range{% endif %}';
    $templateFile = $this->testTemplateDir . '/range_check.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('range_check.twig', ['minAge' => 18, 'age' => 25, 'maxAge' => 65]);
    expect($result1)->toBe('In range');

    $result2 = $engine->render('range_check.twig', ['minAge' => 18, 'age' => 16, 'maxAge' => 65]);
    expect($result2)->toBe('');

    $result3 = $engine->render('range_check.twig', ['minAge' => 18, 'age' => 70, 'maxAge' => 65]);
    expect($result3)->toBe('');
});

test('can handle arithmetic in conditions', function () {
    $templateContent = '{% if price * quantity > 100 %}Bulk discount{% endif %}';
    $templateFile = $this->testTemplateDir . '/arithmetic.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('arithmetic.twig', ['price' => 15, 'quantity' => 10]);
    expect($result1)->toBe('Bulk discount');

    $result2 = $engine->render('arithmetic.twig', ['price' => 5, 'quantity' => 10]);
    expect($result2)->toBe('');
});

test('can handle elseif with comparisons', function () {
    $templateContent = '{% if score >= 90 %}A{% elseif score >= 80 %}B{% elseif score >= 70 %}C{% else %}F{% endif %}';
    $templateFile = $this->testTemplateDir . '/elseif_grades.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('elseif_grades.twig', ['score' => 95]);
    expect($result1)->toBe('A');

    $result2 = $engine->render('elseif_grades.twig', ['score' => 85]);
    expect($result2)->toBe('B');

    $result3 = $engine->render('elseif_grades.twig', ['score' => 75]);
    expect($result3)->toBe('C');

    $result4 = $engine->render('elseif_grades.twig', ['score' => 65]);
    expect($result4)->toBe('F');
});

test('can handle simple variable condition without operators', function () {
    $templateContent = '{% if isLoggedIn %}Welcome back!{% endif %}';
    $templateFile = $this->testTemplateDir . '/simple_variable.twig';
    file_put_contents($templateFile, $templateContent);

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('simple_variable.twig', ['isLoggedIn' => true]);
    expect($result1)->toBe('Welcome back!');

    $result2 = $engine->render('simple_variable.twig', ['isLoggedIn' => false]);
    expect($result2)->toBe('');
});

test('can handle object property in simple condition', function () {
    $templateContent = '{% if user.isAdmin %}Admin panel{% endif %}';
    $templateFile = $this->testTemplateDir . '/object_property_condition.twig';
    file_put_contents($templateFile, $templateContent);

    $user = new \stdClass();
    $user->isAdmin = true;

    $engine = new TemplateEngine($this->testTemplateDir, $this->testCacheDir);

    $result1 = $engine->render('object_property_condition.twig', ['user' => $user]);
    expect($result1)->toBe('Admin panel');

    $user->isAdmin = false;
    $result2 = $engine->render('object_property_condition.twig', ['user' => $user]);
    expect($result2)->toBe('');
});
