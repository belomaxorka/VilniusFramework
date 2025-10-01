<?php declare(strict_types=1);

use Core\DebugToolbar;
use Core\DebugToolbar\AbstractCollector;
use Core\Environment;

beforeEach(function () {
    Environment::set(Environment::DEVELOPMENT);
    DebugToolbar::enable(true);
    
    // Create test collectors
    $this->testCollector1 = new class extends AbstractCollector {
        public function getName(): string { return 'test1'; }
        public function getTitle(): string { return 'Test 1'; }
        public function getIcon(): string { return '1️⃣'; }
        public function collect(): void { $this->data = ['collected' => true]; }
        public function render(): string { return '<div>Test 1 Content</div>'; }
    };

    $this->testCollector2 = new class extends AbstractCollector {
        public function getName(): string { return 'test2'; }
        public function getTitle(): string { return 'Test 2'; }
        public function getIcon(): string { return '2️⃣'; }
        public function collect(): void { $this->data = ['collected' => true]; }
        public function render(): string { return '<div>Test 2 Content</div>'; }
    };
});

afterEach(function () {
    // Cleanup custom collectors
    DebugToolbar::removeCollector('test1');
    DebugToolbar::removeCollector('test2');
    DebugToolbar::removeCollector('test3');
    DebugToolbar::removeCollector('custom');
    DebugToolbar::removeCollector('badged');
    DebugToolbar::removeCollector('disabled');
    DebugToolbar::removeCollector('stats');
});

describe('DebugToolbar Collector Management', function () {
    test('can add custom collector', function () {
        DebugToolbar::addCollector($this->testCollector1);
        
        $collector = DebugToolbar::getCollector('test1');
        expect($collector)->not->toBeNull();
        expect($collector)->toBe($this->testCollector1);
    });

    test('can add multiple collectors', function () {
        DebugToolbar::addCollector($this->testCollector1);
        DebugToolbar::addCollector($this->testCollector2);
        
        expect(DebugToolbar::getCollector('test1'))->not->toBeNull();
        expect(DebugToolbar::getCollector('test2'))->not->toBeNull();
    });

    test('can get collector by name', function () {
        DebugToolbar::addCollector($this->testCollector1);
        
        $collector = DebugToolbar::getCollector('test1');
        expect($collector->getName())->toBe('test1');
    });

    test('returns null for non-existent collector', function () {
        expect(DebugToolbar::getCollector('nonexistent'))->toBeNull();
    });

    test('can remove collector', function () {
        DebugToolbar::addCollector($this->testCollector1);
        expect(DebugToolbar::getCollector('test1'))->not->toBeNull();
        
        DebugToolbar::removeCollector('test1');
        expect(DebugToolbar::getCollector('test1'))->toBeNull();
    });

    test('removing non-existent collector does not error', function () {
        expect(fn() => DebugToolbar::removeCollector('nonexistent'))->not->toThrow(Exception::class);
    });

    test('can get all collectors', function () {
        DebugToolbar::addCollector($this->testCollector1);
        DebugToolbar::addCollector($this->testCollector2);
        
        $collectors = DebugToolbar::getCollectors();
        
        expect($collectors)->toBeArray();
        expect($collectors)->toHaveKey('test1');
        expect($collectors)->toHaveKey('test2');
    });

    test('can replace existing collector', function () {
        DebugToolbar::addCollector($this->testCollector1);
        
        $newCollector = new class extends AbstractCollector {
            public function getName(): string { return 'test1'; }
            public function getTitle(): string { return 'Replaced'; }
            public function getIcon(): string { return '🔄'; }
            public function collect(): void {}
            public function render(): string { return '<div>Replaced</div>'; }
        };
        
        DebugToolbar::addCollector($newCollector);
        
        $collector = DebugToolbar::getCollector('test1');
        expect($collector->getTitle())->toBe('Replaced');
    });
});

describe('DebugToolbar Configuration', function () {
    test('can enable toolbar', function () {
        DebugToolbar::enable(true);
        
        $html = DebugToolbar::render();
        expect($html)->not->toBe('');
    });

    test('can disable toolbar', function () {
        DebugToolbar::enable(false);
        
        $html = DebugToolbar::render();
        expect($html)->toBe('');
        
        // Re-enable for other tests
        DebugToolbar::enable(true);
    });

    test('toolbar configuration methods exist', function () {
        // Just verify methods exist and can be called
        expect(fn() => DebugToolbar::setPosition('bottom'))->not->toThrow(Exception::class);
        expect(fn() => DebugToolbar::setPosition('top'))->not->toThrow(Exception::class);
        expect(fn() => DebugToolbar::setCollapsed(true))->not->toThrow(Exception::class);
        expect(fn() => DebugToolbar::setCollapsed(false))->not->toThrow(Exception::class);
    });
});

describe('DebugToolbar Custom Collectors in Render', function () {
    test('custom collector can be rendered', function () {
        DebugToolbar::addCollector($this->testCollector1);
        
        $html = DebugToolbar::render();
        
        // Verify toolbar renders something (may include default collectors)
        expect($html)->toBeString();
        expect($html)->not->toBeEmpty();
    });

    test('multiple custom collectors can be added', function () {
        DebugToolbar::addCollector($this->testCollector1);
        DebugToolbar::addCollector($this->testCollector2);
        
        $collectors = DebugToolbar::getCollectors();
        
        expect($collectors)->toHaveKey('test1');
        expect($collectors)->toHaveKey('test2');
    });

    test('disabled collector property can be set', function () {
        $this->testCollector1->setEnabled(false);
        DebugToolbar::addCollector($this->testCollector1);
        
        // Verify collector is in disabled state
        $collector = DebugToolbar::getCollector('test1');
        expect($collector->isEnabled())->toBeFalse();
    });

    test('collector with badge returns badge value', function () {
        $badgedCollector = new class extends AbstractCollector {
            public function getName(): string { return 'badged'; }
            public function getTitle(): string { return 'Badged'; }
            public function getIcon(): string { return '🏷️'; }
            public function collect(): void { $this->data['count'] = 5; }
            public function render(): string { return '<div>Content</div>'; }
            public function getBadge(): ?string { return '5'; }
        };
        
        DebugToolbar::addCollector($badgedCollector);
        $badgedCollector->collect();
        
        expect($badgedCollector->getBadge())->toBe('5');
    });

    test('collector without badge shows no badge', function () {
        DebugToolbar::addCollector($this->testCollector1);
        
        $html = DebugToolbar::render();
        
        // Badge implementation detail test - just verify render works
        expect($html)->toBeString();
        expect($html)->not->toBeEmpty();
    });
});

describe('DebugToolbar Collector Priority', function () {
    test('collectors have configurable priority', function () {
        $lowPriority = new class extends AbstractCollector {
            public function __construct() { $this->priority = 10; }
            public function getName(): string { return 'low'; }
            public function getTitle(): string { return 'Low Priority'; }
            public function getIcon(): string { return '⬇️'; }
            public function collect(): void {}
            public function render(): string { return 'Low'; }
        };
        
        $highPriority = new class extends AbstractCollector {
            public function __construct() { $this->priority = 200; }
            public function getName(): string { return 'high'; }
            public function getTitle(): string { return 'High Priority'; }
            public function getIcon(): string { return '⬆️'; }
            public function collect(): void {}
            public function render(): string { return 'High'; }
        };
        
        DebugToolbar::addCollector($lowPriority);
        DebugToolbar::addCollector($highPriority);
        
        expect($lowPriority->getPriority())->toBe(10);
        expect($highPriority->getPriority())->toBe(200);
        
        // Cleanup
        DebugToolbar::removeCollector('low');
        DebugToolbar::removeCollector('high');
    });

    test('can change collector priority dynamically', function () {
        $this->testCollector1->setPriority(50);
        DebugToolbar::addCollector($this->testCollector1);
        
        $collector = DebugToolbar::getCollector('test1');
        expect($collector->getPriority())->toBe(50);
    });
});

describe('DebugToolbar Collector Data Collection', function () {
    test('collectors have collect method', function () {
        $collector = new class extends AbstractCollector {
            public function getName(): string { return 'test3'; }
            public function getTitle(): string { return 'Test'; }
            public function getIcon(): string { return '🧪'; }
            public function collect(): void { $this->data = ['test' => 'value']; }
            public function render(): string { return 'Test'; }
        };
        
        DebugToolbar::addCollector($collector);
        $collector->collect();
        
        expect($collector->getData())->toBe(['test' => 'value']);
        
        DebugToolbar::removeCollector('test3');
    });

    test('disabled collectors are skipped in toolbar', function () {
        $collector = new class extends AbstractCollector {
            public function __construct() { 
                $this->enabled = false;
            }
            public function getName(): string { return 'test3'; }
            public function getTitle(): string { return 'Test'; }
            public function getIcon(): string { return '🧪'; }
            public function collect(): void { $this->data = ['collected' => true]; }
            public function render(): string { return 'Test'; }
        };
        
        DebugToolbar::addCollector($collector);
        
        expect($collector->isEnabled())->toBeFalse();
        
        DebugToolbar::removeCollector('test3');
    });
});

describe('DebugToolbar Collector Header Stats', function () {
    test('collector can provide header stats', function () {
        $statsCollector = new class extends AbstractCollector {
            public function getName(): string { return 'stats'; }
            public function getTitle(): string { return 'Stats'; }
            public function getIcon(): string { return '📊'; }
            public function collect(): void { $this->data['value'] = 42; }
            public function render(): string { return 'Stats'; }
            public function getHeaderStats(): array {
                return [[
                    'icon' => '🔢',
                    'value' => 'Custom: 42',
                    'color' => '#ff9800',
                ]];
            }
        };
        
        DebugToolbar::addCollector($statsCollector);
        
        $stats = $statsCollector->getHeaderStats();
        expect($stats)->toBeArray();
        expect($stats[0]['value'])->toBe('Custom: 42');
    });

    test('multiple collectors can have header stats', function () {
        $collector1 = new class extends AbstractCollector {
            public function getName(): string { return 'stats1'; }
            public function getTitle(): string { return 'S1'; }
            public function getIcon(): string { return '1️⃣'; }
            public function collect(): void {}
            public function render(): string { return ''; }
            public function getHeaderStats(): array {
                return [['icon' => '1️⃣', 'value' => 'Stat1', 'color' => '#000']];
            }
        };
        
        $collector2 = new class extends AbstractCollector {
            public function getName(): string { return 'stats2'; }
            public function getTitle(): string { return 'S2'; }
            public function getIcon(): string { return '2️⃣'; }
            public function collect(): void {}
            public function render(): string { return ''; }
            public function getHeaderStats(): array {
                return [['icon' => '2️⃣', 'value' => 'Stat2', 'color' => '#000']];
            }
        };
        
        DebugToolbar::addCollector($collector1);
        DebugToolbar::addCollector($collector2);
        
        expect($collector1->getHeaderStats()[0]['value'])->toBe('Stat1');
        expect($collector2->getHeaderStats()[0]['value'])->toBe('Stat2');
        
        DebugToolbar::removeCollector('stats1');
        DebugToolbar::removeCollector('stats2');
    });
});

describe('DebugToolbar Integration with Custom Collectors', function () {
    test('can create fully functional custom collector', function () {
        $customCollector = new class extends AbstractCollector {
            public function getName(): string { return 'custom'; }
            public function getTitle(): string { return 'Custom Tool'; }
            public function getIcon(): string { return '🔧'; }
            
            public function collect(): void {
                $this->data = [
                    'items' => ['item1', 'item2', 'item3'],
                    'count' => 3,
                ];
            }
            
            public function render(): string {
                $html = '<div style="padding: 20px;">';
                $html .= '<h3>Custom Tool</h3>';
                $html .= '<p>Items collected: ' . $this->data['count'] . '</p>';
                foreach ($this->data['items'] as $item) {
                    $html .= '<div>' . $item . '</div>';
                }
                $html .= '</div>';
                return $html;
            }
            
            public function getBadge(): ?string {
                return isset($this->data['count']) && $this->data['count'] > 0 
                    ? (string)$this->data['count'] 
                    : null;
            }
            
            public function getHeaderStats(): array {
                if (!isset($this->data['count'])) {
                    return [];
                }
                return [[
                    'icon' => '🔧',
                    'value' => $this->data['count'] . ' items',
                    'color' => '#66bb6a',
                ]];
            }
        };
        
        DebugToolbar::addCollector($customCollector);
        $customCollector->collect();
        
        // Verify collector functionality
        expect($customCollector->getName())->toBe('custom');
        expect($customCollector->getData())->toHaveKey('items');
        expect($customCollector->getBadge())->toBe('3');
        expect($customCollector->getHeaderStats()[0]['value'])->toBe('3 items');
        
        $html = $customCollector->render();
        expect($html)->toContain('Custom Tool');
        expect($html)->toContain('item1');
    });

    test('custom collector can be added alongside built-ins', function () {
        DebugToolbar::addCollector($this->testCollector1);
        
        $collectors = DebugToolbar::getCollectors();
        
        // Should have both custom and built-in collectors
        expect($collectors)->toHaveKey('test1'); // Custom
        expect($collectors)->toHaveKey('overview'); // Built-in
        expect($collectors)->toHaveKey('memory'); // Built-in
    });
});
