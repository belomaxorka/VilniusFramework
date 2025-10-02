<?php declare(strict_types=1);

use Core\DebugToolbar\Collectors\MemoryCollector;
use Core\MemoryProfiler;

beforeEach(function () {
    $this->collector = new MemoryCollector();
});

describe('MemoryCollector Configuration', function () {
    test('has correct name', function () {
        expect($this->collector->getName())->toBe('memory');
    });

    test('has correct title', function () {
        expect($this->collector->getTitle())->toBe('Memory');
    });

    test('has correct icon', function () {
        expect($this->collector->getIcon())->toBe('💾');
    });

    test('has priority 60', function () {
        expect($this->collector->getPriority())->toBe(60);
    });

    test('is enabled when MemoryProfiler class exists', function () {
        expect($this->collector->isEnabled())->toBeTrue();
    });
});

describe('MemoryCollector Data Collection', function () {
    test('collects current memory usage', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data)->toHaveKey('current');
        expect($data['current'])->toBeInt();
        expect($data['current'])->toBeGreaterThan(0);
    });

    test('collects peak memory usage', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data)->toHaveKey('peak');
        expect($data['peak'])->toBeInt();
        expect($data['peak'])->toBeGreaterThan(0);
    });

    test('collects memory limit', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data)->toHaveKey('limit');
        expect($data['limit'])->toBeInt();
        expect($data['limit'])->toBeGreaterThanOrEqual(0); // Can be 0 if limit is -1
    });

    test('peak memory is greater than or equal to current', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['peak'])->toBeGreaterThanOrEqual($data['current']);
    });

    test('memory values are in bytes', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        // Memory should be at least a few KB (few thousand bytes)
        expect($data['current'])->toBeGreaterThan(1000);
        expect($data['peak'])->toBeGreaterThan(1000);
    });
});

describe('MemoryCollector Rendering', function () {
    test('renders HTML content', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toBeString();
        expect($html)->not->toBeEmpty();
    });

    test('includes memory usage title', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Memory Usage');
    });

    test('displays current memory', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Current');
    });

    test('displays peak memory', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Peak');
    });

    test('displays memory limit when available', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        $html = $this->collector->render();
        
        if ($data['limit'] > 0) {
            expect($html)->toContain('Limit');
        }
    });

    test('displays usage percentage when limit is set', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        $html = $this->collector->render();
        
        if ($data['limit'] > 0) {
            expect($html)->toContain('Usage');
            expect($html)->toContain('%');
        }
    });

    test('displays progress bar when limit is set', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        $html = $this->collector->render();
        
        if ($data['limit'] > 0) {
            expect($html)->toContain('border-radius: 10px'); // Progress bar container
            expect($html)->toMatch('/width: \d+(\.\d+)?%/'); // Progress bar fill
        }
    });

    test('progress bar color changes based on usage', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        $html = $this->collector->render();
        
        if ($data['limit'] > 0) {
            // Should contain one of the threshold colors
            $hasColor = str_contains($html, '#66bb6a') || // green
                       str_contains($html, '#ffa726') || // orange
                       str_contains($html, '#ef5350');   // red
            expect($hasColor)->toBeTrue();
        }
    });

    test('formats memory values in human readable format', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        // Should contain units (B, KB, MB, or GB)
        $hasUnits = str_contains($html, ' B') ||
                   str_contains($html, ' KB') ||
                   str_contains($html, ' MB') ||
                   str_contains($html, ' GB');
        expect($hasUnits)->toBeTrue();
    });

    test('renders with proper styling', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('padding: 20px');
        expect($html)->toContain('background: #f5f5f5');
    });
});

describe('MemoryCollector Header Stats', function () {
    test('provides header stats with memory information', function () {
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        // MemoryCollector now provides header stats
        expect($stats)->toBeArray();
        expect($stats)->toHaveCount(1);
        expect($stats[0])->toHaveKey('icon');
        expect($stats[0])->toHaveKey('value');
        expect($stats[0])->toHaveKey('color');
        expect($stats[0]['icon'])->toBe('💾');
    });
});

describe('MemoryCollector Edge Cases', function () {
    test('handles unlimited memory (-1 limit)', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        // When memory_limit is -1, limit should be 0
        expect($data['limit'])->toBeInt();
        
        $html = $this->collector->render();
        expect($html)->toBeString();
    });

    test('handles small memory allocations', function () {
        $this->collector->collect();
        
        // Should work regardless of memory size
        expect($this->collector->getData())->toBeArray();
        expect($this->collector->render())->toBeString();
    });

    test('memory values increase over time', function () {
        $this->collector->collect();
        $firstPeak = $this->collector->getData()['peak'];
        
        // Allocate some memory
        $largeArray = array_fill(0, 10000, 'test');
        
        $this->collector->collect();
        $secondPeak = $this->collector->getData()['peak'];
        
        // Peak should be same or higher (PHP might not increase it for small allocations)
        expect($secondPeak)->toBeGreaterThanOrEqual($firstPeak);
        
        unset($largeArray); // Clean up
    });

    test('formats memory limit correctly for different units', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        if ($data['limit'] > 0) {
            $html = $this->collector->render();
            
            // Limit should be formatted with appropriate unit
            expect($html)->toMatch('/Limit:<\/strong> \d+(\.\d+)? (B|KB|MB|GB)/');
        }
    });
});

describe('MemoryCollector Integration', function () {
    test('works with MemoryProfiler', function () {
        MemoryProfiler::snapshot('test');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['current'])->toBe(MemoryProfiler::current());
        expect($data['peak'])->toBe(MemoryProfiler::peak());
    });

    test('percentage calculation is accurate', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        if ($data['limit'] > 0) {
            $expectedPercent = ($data['peak'] / $data['limit']) * 100;
            $html = $this->collector->render();
            
            // Extract percentage from HTML
            if (preg_match('/Usage:<\/strong> ([\d.]+)%/', $html, $matches)) {
                $renderedPercent = (float)$matches[1];
                // Compare with tolerance of 0.01
                expect(abs($renderedPercent - $expectedPercent))->toBeLessThan(0.01);
            }
        }
    });

    test('progress bar width matches usage percentage', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        if ($data['limit'] > 0) {
            $percent = ($data['peak'] / $data['limit']) * 100;
            $html = $this->collector->render();
            
            // Progress bar width should not exceed 100%
            if (preg_match('/width: ([\d.]+)%/', $html, $matches)) {
                $barWidth = (float)$matches[1];
                expect($barWidth)->toBeLessThanOrEqual(100);
                
                // Should match actual usage (or be capped at 100) with tolerance
                $expected = min(100, round($percent, 2));
                expect(abs($barWidth - $expected))->toBeLessThan(0.1);
            }
        }
    });

    test('enabled property can be set', function () {
        $result = $this->collector->setEnabled(false);
        
        // Note: isEnabled() checks if MemoryProfiler class exists, not the enabled property
        expect($result)->toBe($this->collector);
        expect($this->collector->isEnabled())->toBeTrue(); // Still true because class exists
    });

    test('handles multiple collect calls', function () {
        $this->collector->collect();
        $firstData = $this->collector->getData();
        
        $this->collector->collect();
        $secondData = $this->collector->getData();
        
        // Should have same structure
        expect(array_keys($firstData))->toBe(array_keys($secondData));
    });

    test('color threshold works correctly', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        $html = $this->collector->render();
        
        if ($data['limit'] > 0) {
            $percent = ($data['peak'] / $data['limit']) * 100;
            
            if ($percent >= 75) {
                expect($html)->toContain('#ef5350'); // red
            } elseif ($percent >= 50) {
                expect($html)->toContain('#ffa726'); // orange
            } else {
                expect($html)->toContain('#66bb6a'); // green
            }
        }
    });
});
