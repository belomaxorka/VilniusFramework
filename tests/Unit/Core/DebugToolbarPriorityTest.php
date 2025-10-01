<?php declare(strict_types=1);

use Core\DebugToolbar;
use Core\DebugToolbar\AbstractCollector;

test('collectors are sorted by priority (higher = earlier)', function () {
    // Создаем тестовые коллекторы с разными приоритетами
    $collector1 = new class extends AbstractCollector {
        public function __construct() {
            $this->priority = 50; // Низкий приоритет
        }
        public function getName(): string { return 'test1'; }
        public function getTitle(): string { return 'Test 1'; }
        public function getIcon(): string { return '1️⃣'; }
        public function collect(): void {}
        public function render(): string { return 'Test 1 Content'; }
    };

    $collector2 = new class extends AbstractCollector {
        public function __construct() {
            $this->priority = 100; // Высокий приоритет - отображается первым
        }
        public function getName(): string { return 'test2'; }
        public function getTitle(): string { return 'Test 2'; }
        public function getIcon(): string { return '2️⃣'; }
        public function collect(): void {}
        public function render(): string { return 'Test 2 Content'; }
    };

    $collector3 = new class extends AbstractCollector {
        public function __construct() {
            $this->priority = 10; // Самый низкий приоритет - последний
        }
        public function getName(): string { return 'test3'; }
        public function getTitle(): string { return 'Test 3'; }
        public function getIcon(): string { return '3️⃣'; }
        public function collect(): void {}
        public function render(): string { return 'Test 3 Content'; }
    };

    // Добавляем в случайном порядке
    DebugToolbar::addCollector($collector1);
    DebugToolbar::addCollector($collector3);
    DebugToolbar::addCollector($collector2);

    // Рендерим (внутри происходит сортировка)
    $html = DebugToolbar::render();

    // Проверяем порядок в HTML (test2 (100) -> test1 (50) -> test3 (10))
    $pos1 = strpos($html, 'Test 1');
    $pos2 = strpos($html, 'Test 2');
    $pos3 = strpos($html, 'Test 3');

    expect($pos2)->toBeLessThan($pos1, 'Test 2 (priority 100) должен быть раньше Test 1 (priority 50)');
    expect($pos1)->toBeLessThan($pos3, 'Test 1 (priority 50) должен быть раньше Test 3 (priority 10)');

    // Очищаем
    DebugToolbar::removeCollector('test1');
    DebugToolbar::removeCollector('test2');
    DebugToolbar::removeCollector('test3');
});

test('header stats are sorted by priority (higher = earlier)', function () {
    // Создаем коллекторы с header stats
    $collector1 = new class extends AbstractCollector {
        public function __construct() {
            $this->priority = 50;
        }
        public function getName(): string { return 'stats1'; }
        public function getTitle(): string { return 'Stats 1'; }
        public function getIcon(): string { return '📊'; }
        public function collect(): void {
            $this->data = ['count' => 1];
        }
        public function render(): string { return ''; }
        public function getHeaderStats(): array {
            return [[
                'icon' => '1️⃣',
                'value' => 'First',
                'color' => '#000',
            ]];
        }
    };

    $collector2 = new class extends AbstractCollector {
        public function __construct() {
            $this->priority = 80;
        }
        public function getName(): string { return 'stats2'; }
        public function getTitle(): string { return 'Stats 2'; }
        public function getIcon(): string { return '📊'; }
        public function collect(): void {
            $this->data = ['count' => 2];
        }
        public function render(): string { return ''; }
        public function getHeaderStats(): array {
            return [[
                'icon' => '2️⃣',
                'value' => 'Second',
                'color' => '#000',
            ]];
        }
    };

    // Добавляем в обратном порядке
    DebugToolbar::addCollector($collector1);
    DebugToolbar::addCollector($collector2);

    // Рендерим
    $html = DebugToolbar::render();

    // Проверяем порядок в header (Second (80) должен быть раньше First (50))
    $posFirst = strpos($html, '>First<');
    $posSecond = strpos($html, '>Second<');

    expect($posSecond)->toBeLessThan($posFirst, 'Header stat с приоритетом 80 должен быть раньше чем с приоритетом 50');

    // Очищаем
    DebugToolbar::removeCollector('stats1');
    DebugToolbar::removeCollector('stats2');
});

test('priority can be changed dynamically', function () {
    $collector = new class extends AbstractCollector {
        public function getName(): string { return 'dynamic'; }
        public function getTitle(): string { return 'Dynamic'; }
        public function getIcon(): string { return '🔄'; }
        public function collect(): void {}
        public function render(): string { return 'Dynamic'; }
    };

    DebugToolbar::addCollector($collector);

    // Проверяем начальный приоритет
    expect($collector->getPriority())->toBe(100); // По умолчанию из AbstractCollector

    // Меняем приоритет
    $collector->setPriority(25);
    expect($collector->getPriority())->toBe(25);

    // Очищаем
    DebugToolbar::removeCollector('dynamic');
});
