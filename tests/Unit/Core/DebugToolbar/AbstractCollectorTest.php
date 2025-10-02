<?php declare(strict_types=1);

use Core\DebugToolbar\AbstractCollector;

describe('AbstractCollector Basic Methods', function () {
    beforeEach(function () {
        $this->collector = new class extends AbstractCollector {
            public function getName(): string { return 'test'; }
            public function getTitle(): string { return 'Test'; }
            public function getIcon(): string { return '🧪'; }
            public function collect(): void { $this->data = ['test' => 'data']; }
            public function render(): string { return '<div>Test</div>'; }
        };
    });

    test('has default priority of 100', function () {
        expect($this->collector->getPriority())->toBe(100);
    });

    test('can set priority', function () {
        $this->collector->setPriority(50);
        expect($this->collector->getPriority())->toBe(50);
    });

    test('priority setter returns self for chaining', function () {
        $result = $this->collector->setPriority(75);
        expect($result)->toBe($this->collector);
    });

    test('is enabled by default', function () {
        expect($this->collector->isEnabled())->toBeTrue();
    });

    test('can be disabled', function () {
        $this->collector->setEnabled(false);
        expect($this->collector->isEnabled())->toBeFalse();
    });

    test('can be re-enabled', function () {
        $this->collector->setEnabled(false);
        $this->collector->setEnabled(true);
        expect($this->collector->isEnabled())->toBeTrue();
    });

    test('enabled setter returns self for chaining', function () {
        $result = $this->collector->setEnabled(false);
        expect($result)->toBe($this->collector);
    });

    test('returns null badge by default', function () {
        expect($this->collector->getBadge())->toBeNull();
    });

    test('returns empty header stats by default', function () {
        expect($this->collector->getHeaderStats())->toBe([]);
    });

    test('can get collected data', function () {
        $this->collector->collect();
        expect($this->collector->getData())->toBe(['test' => 'data']);
    });

    test('data is empty array by default', function () {
        expect($this->collector->getData())->toBe([]);
    });
});

describe('AbstractCollector formatBytes', function () {
    beforeEach(function () {
        $this->collector = new class extends AbstractCollector {
            public function getName(): string { return 'test'; }
            public function getTitle(): string { return 'Test'; }
            public function getIcon(): string { return '🧪'; }
            public function collect(): void {}
            public function render(): string { return ''; }
            
            // Expose protected method for testing
            public function testFormatBytes(int $bytes): string {
                return $this->formatBytes($bytes);
            }
        };
    });

    test('formats bytes correctly', function () {
        expect($this->collector->testFormatBytes(500))->toBe('500.00 B');
    });

    test('formats kilobytes correctly', function () {
        expect($this->collector->testFormatBytes(1024))->toBe('1.00 KB');
        expect($this->collector->testFormatBytes(1536))->toBe('1.50 KB');
    });

    test('formats megabytes correctly', function () {
        expect($this->collector->testFormatBytes(1048576))->toBe('1.00 MB');
        expect($this->collector->testFormatBytes(2621440))->toBe('2.50 MB');
    });

    test('formats gigabytes correctly', function () {
        expect($this->collector->testFormatBytes(1073741824))->toBe('1.00 GB');
        expect($this->collector->testFormatBytes(2147483648))->toBe('2.00 GB');
    });

    test('handles zero bytes', function () {
        expect($this->collector->testFormatBytes(0))->toBe('0 B');
    });

    test('rounds to 2 decimal places', function () {
        expect($this->collector->testFormatBytes(1234567))->toBe('1.18 MB');
    });
});

describe('AbstractCollector formatTime', function () {
    beforeEach(function () {
        $this->collector = new class extends AbstractCollector {
            public function getName(): string { return 'test'; }
            public function getTitle(): string { return 'Test'; }
            public function getIcon(): string { return '🧪'; }
            public function collect(): void {}
            public function render(): string { return ''; }
            
            public function testFormatTime(float $time): string {
                return $this->formatTime($time);
            }
        };
    });

    test('formats milliseconds correctly', function () {
        expect($this->collector->testFormatTime(100))->toBe('100.00 ms');
        expect($this->collector->testFormatTime(1.5))->toBe('1.50 ms');
    });

    test('formats microseconds for values less than 1ms', function () {
        expect($this->collector->testFormatTime(0.5))->toBe('500.00 μs');
        expect($this->collector->testFormatTime(0.001))->toBe('1.00 μs');
    });

    test('handles zero time', function () {
        expect($this->collector->testFormatTime(0))->toBe('0.00 μs');
    });

    test('rounds to 2 decimal places', function () {
        expect($this->collector->testFormatTime(123.456))->toBe('123.46 ms');
    });

    test('handles very small values', function () {
        expect($this->collector->testFormatTime(0.0001))->toBe('0.10 μs');
    });
});

describe('AbstractCollector getColorByThreshold', function () {
    beforeEach(function () {
        $this->collector = new class extends AbstractCollector {
            public function getName(): string { return 'test'; }
            public function getTitle(): string { return 'Test'; }
            public function getIcon(): string { return '🧪'; }
            public function collect(): void {}
            public function render(): string { return ''; }
            
            public function testGetColorByThreshold(float $value, float $warning, float $critical): string {
                return $this->getColorByThreshold($value, $warning, $critical);
            }
        };
    });

    test('returns green for values below warning', function () {
        $color = $this->collector->testGetColorByThreshold(25, 50, 75);
        expect($color)->toBe('#66bb6a');
    });

    test('returns orange for values at or above warning but below critical', function () {
        $color = $this->collector->testGetColorByThreshold(50, 50, 75);
        expect($color)->toBe('#ffa726');
        
        $color = $this->collector->testGetColorByThreshold(60, 50, 75);
        expect($color)->toBe('#ffa726');
    });

    test('returns red for values at or above critical', function () {
        $color = $this->collector->testGetColorByThreshold(75, 50, 75);
        expect($color)->toBe('#ef5350');
        
        $color = $this->collector->testGetColorByThreshold(90, 50, 75);
        expect($color)->toBe('#ef5350');
    });

    test('handles edge cases', function () {
        expect($this->collector->testGetColorByThreshold(0, 50, 75))->toBe('#66bb6a');
        expect($this->collector->testGetColorByThreshold(100, 50, 75))->toBe('#ef5350');
    });

    test('works with decimal thresholds', function () {
        expect($this->collector->testGetColorByThreshold(4.5, 5.0, 10.0))->toBe('#66bb6a');
        expect($this->collector->testGetColorByThreshold(7.5, 5.0, 10.0))->toBe('#ffa726');
        expect($this->collector->testGetColorByThreshold(12.0, 5.0, 10.0))->toBe('#ef5350');
    });
});

describe('AbstractCollector Integration', function () {
    test('can chain configuration methods', function () {
        $collector = new class extends AbstractCollector {
            public function getName(): string { return 'chainable'; }
            public function getTitle(): string { return 'Chainable'; }
            public function getIcon(): string { return '⛓️'; }
            public function collect(): void {}
            public function render(): string { return ''; }
        };

        $result = $collector->setPriority(80)->setEnabled(false);
        
        expect($result)->toBe($collector);
        expect($collector->getPriority())->toBe(80);
        expect($collector->isEnabled())->toBeFalse();
    });

    test('collector with custom badge', function () {
        $collector = new class extends AbstractCollector {
            public function getName(): string { return 'badged'; }
            public function getTitle(): string { return 'Badged'; }
            public function getIcon(): string { return '🏷️'; }
            public function collect(): void { $this->data['count'] = 5; }
            public function render(): string { return ''; }
            public function getBadge(): ?string {
                return isset($this->data['count']) ? (string)$this->data['count'] : null;
            }
        };

        expect($collector->getBadge())->toBeNull();
        
        $collector->collect();
        expect($collector->getBadge())->toBe('5');
    });

    test('collector with custom header stats', function () {
        $collector = new class extends AbstractCollector {
            public function getName(): string { return 'stats'; }
            public function getTitle(): string { return 'Stats'; }
            public function getIcon(): string { return '📈'; }
            public function collect(): void { $this->data['value'] = 42; }
            public function render(): string { return ''; }
            public function getHeaderStats(): array {
                if (!isset($this->data['value'])) {
                    return [];
                }
                return [[
                    'icon' => '📊',
                    'value' => (string)$this->data['value'],
                    'color' => '#66bb6a',
                ]];
            }
        };

        expect($collector->getHeaderStats())->toBe([]);
        
        $collector->collect();
        $stats = $collector->getHeaderStats();
        
        expect($stats)->toHaveCount(1);
        expect($stats[0]['icon'])->toBe('📊');
        expect($stats[0]['value'])->toBe('42');
        expect($stats[0]['color'])->toBe('#66bb6a');
    });
});
