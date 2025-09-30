<?php declare(strict_types=1);

use Core\DebugToolbar;
use Core\DebugToolbar\AbstractCollector;
use Core\Environment;

beforeEach(function () {
    Environment::set(Environment::DEVELOPMENT);
    
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
    });

    test('can set position to bottom', function () {
        DebugToolbar::setPosition('bottom');
        
        $html = DebugToolbar::render();
        expect($html)->toContain('bottom: 0');
    });

    test('can set position to top', function () {
        DebugToolbar::setPosition('top');
        
        $html = DebugToolbar::render();
        expect($html)->toContain('top: 0');
    });

    test('can set collapsed state', function () {
        DebugToolbar::setCollapsed(true);
        
        $html = DebugToolbar::render();
        expect($html)->toContain('collapsed');
    });

    test('can set expanded state', function () {
        DebugToolbar::setCollapsed(false);
        
        $html = DebugToolbar::render();
        expect($html)->not->toContain('class="collapsed"');
    });
});

describe('DebugToolbar Custom Collectors in Render', function () {
    test('custom collector appears in rendered output', function () {
        DebugToolbar::addCollector($this->testCollector1);
        
        $html = DebugToolbar::render();
        
        expect($html)->toContain('Test 1');
        expect($html)->toContain('Test 1 Content');
    });

    test('multiple custom collectors appear', function () {
        DebugToolbar::addCollector($this->testCollector1);
        DebugToolbar::addCollector($this->testCollector2);
        
        $html = DebugToolbar::render();
        
        expect($html)->toContain('Test 1');
        expect($html)->toContain('Test 2');
    });

    test('disabled collector does not appear', function () {
        $this->testCollector1->setEnabled(false);
        DebugToolbar::addCollector($this->testCollector1);
        
        $html = DebugToolbar::render();
        
        expect($html)->not->toContain('Test 1 Content');
    });

    test('collector with badge shows badge', function () {
        $badgedCollector = new class extends AbstractCollector {
            public function getName(): string { return 'badged'; }
            public function getTitle(): string { return 'Badged'; }
            public function getIcon(): string { return '🏷️'; }
            public function collect(): void { $this->data['count'] = 5; }
            public function render(): string { return '<div>Content</div>'; }
            public function getBadge(): ?string { return '5'; }
        };
        
        DebugToolbar::addCollector($badgedCollector);
        
        $html = DebugToolbar::render();
        
        expect($html)->toContain('class="badge"');
        expect($html)->toContain('>5<');
    });

    test('collector without badge shows no badge', function () {
        DebugToolbar::addCollector($this->testCollector1);
        
        $html = DebugToolbar::render();
        
        // Should not have badge for this collector
        // (there may be badges from other collectors)
        $pos = strpos($html, 'Test 1');
        $nextBadge = strpos($html, 'class="badge"', $pos);
        $nextTab = strpos($html, 'data-tab=', $pos + 1);
        
        // If badge exists, it should be after next tab (belonging to another collector)
        if ($nextBadge !== false && $nextTab !== false) {
            expect($nextBadge)->toBeGreaterThan($nextTab);
        }
    });
});

describe('DebugToolbar Collector Priority', function () {
    test('collectors with higher priority appear first', function () {
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
        
        $html = DebugToolbar::render();
        
        $posHigh = strpos($html, 'High Priority');
        $posLow = strpos($html, 'Low Priority');
        
        expect($posHigh)->toBeLessThan($posLow);
        
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
    test('calls collect on enabled collectors during render', function () {
        $collected = false;
        
        $collector = new class($collected) extends AbstractCollector {
            private $flag;
            public function __construct(&$flag) { $this->flag = &$flag; }
            public function getName(): string { return 'test3'; }
            public function getTitle(): string { return 'Test'; }
            public function getIcon(): string { return '🧪'; }
            public function collect(): void { $this->flag = true; }
            public function render(): string { return 'Test'; }
        };
        
        DebugToolbar::addCollector($collector);
        DebugToolbar::render();
        
        expect($collected)->toBeTrue();
        
        DebugToolbar::removeCollector('test3');
    });

    test('does not call collect on disabled collectors', function () {
        $collected = false;
        
        $collector = new class($collected) extends AbstractCollector {
            private $flag;
            public function __construct(&$flag) { 
                $this->flag = &$flag;
                $this->enabled = false;
            }
            public function getName(): string { return 'test3'; }
            public function getTitle(): string { return 'Test'; }
            public function getIcon(): string { return '🧪'; }
            public function collect(): void { $this->flag = true; }
            public function render(): string { return 'Test'; }
        };
        
        DebugToolbar::addCollector($collector);
        DebugToolbar::render();
        
        expect($collected)->toBeFalse();
        
        DebugToolbar::removeCollector('test3');
    });
});

describe('DebugToolbar Collector Header Stats', function () {
    test('collector header stats appear in toolbar header', function () {
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
        
        $html = DebugToolbar::render();
        
        expect($html)->toContain('Custom: 42');
        expect($html)->toContain('#ff9800');
    });

    test('multiple collectors can provide header stats', function () {
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
        
        $html = DebugToolbar::render();
        
        expect($html)->toContain('Stat1');
        expect($html)->toContain('Stat2');
        
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
                return $this->data['count'] > 0 ? (string)$this->data['count'] : null;
            }
            
            public function getHeaderStats(): array {
                return [[
                    'icon' => '🔧',
                    'value' => $this->data['count'] . ' items',
                    'color' => '#66bb6a',
                ]];
            }
        };
        
        DebugToolbar::addCollector($customCollector);
        $html = DebugToolbar::render();
        
        // Check all aspects appear correctly
        expect($html)->toContain('Custom Tool');
        expect($html)->toContain('item1');
        expect($html)->toContain('item2');
        expect($html)->toContain('item3');
        expect($html)->toContain('3 items');
        expect($html)->toContain('class="badge"');
    });

    test('custom collector works alongside built-in collectors', function () {
        DebugToolbar::addCollector($this->testCollector1);
        
        $html = DebugToolbar::render();
        
        // Should have both custom and built-in collectors
        expect($html)->toContain('Test 1'); // Custom
        expect($html)->toContain('Overview'); // Built-in
        expect($html)->toContain('Memory'); // Built-in
    });
});
