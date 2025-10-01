<?php declare(strict_types=1);

use Core\DebugToolbar\Collectors\CacheCollector;

beforeEach(function () {
    // Clear static operations array via reflection
    $reflection = new ReflectionClass(CacheCollector::class);
    $property = $reflection->getProperty('operations');
    $property->setAccessible(true);
    $property->setValue(null, []);
    
    $this->collector = new CacheCollector();
});

afterEach(function () {
    // Clear operations
    $reflection = new ReflectionClass(CacheCollector::class);
    $property = $reflection->getProperty('operations');
    $property->setAccessible(true);
    $property->setValue(null, []);
});

describe('CacheCollector Configuration', function () {
    test('has correct name', function () {
        expect($this->collector->getName())->toBe('cache');
    });

    test('has correct title', function () {
        expect($this->collector->getTitle())->toBe('Cache');
    });

    test('has correct icon', function () {
        expect($this->collector->getIcon())->toBe('🗃️');
    });

    test('has priority 75', function () {
        expect($this->collector->getPriority())->toBe(75);
    });

    test('is enabled by default', function () {
        expect($this->collector->isEnabled())->toBeTrue();
    });
});

describe('CacheCollector Operation Logging', function () {
    test('logs cache hit', function () {
        CacheCollector::logHit('user:123', 'John Doe');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['operations'])->toHaveCount(1);
        expect($data['operations'][0]['type'])->toBe('hit');
        expect($data['operations'][0]['key'])->toBe('user:123');
    });

    test('logs cache miss', function () {
        CacheCollector::logMiss('user:999');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['operations'])->toHaveCount(1);
        expect($data['operations'][0]['type'])->toBe('miss');
        expect($data['operations'][0]['key'])->toBe('user:999');
    });

    test('logs cache write', function () {
        CacheCollector::logWrite('config:app', ['debug' => true]);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['operations'])->toHaveCount(1);
        expect($data['operations'][0]['type'])->toBe('write');
        expect($data['operations'][0]['key'])->toBe('config:app');
    });

    test('logs cache delete', function () {
        CacheCollector::logDelete('temp:data');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['operations'])->toHaveCount(1);
        expect($data['operations'][0]['type'])->toBe('delete');
        expect($data['operations'][0]['key'])->toBe('temp:data');
    });

    test('logs multiple operations', function () {
        CacheCollector::logHit('key1', 'value1');
        CacheCollector::logMiss('key2');
        CacheCollector::logWrite('key3', 'value3');
        CacheCollector::logDelete('key4');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['operations'])->toHaveCount(4);
    });

    test('operation includes timestamp', function () {
        CacheCollector::logHit('key', 'value');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['operations'][0])->toHaveKey('timestamp');
        expect($data['operations'][0]['timestamp'])->toBeFloat();
    });

    test('operation includes execution time', function () {
        CacheCollector::logHit('key', 'value', 5.5);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['operations'][0])->toHaveKey('time');
        expect($data['operations'][0]['time'])->toBe(5.5);
    });

    test('operation includes value for hit and write', function () {
        CacheCollector::logHit('key1', 'value1');
        CacheCollector::logWrite('key2', ['data' => 'value2']);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['operations'][0]['value'])->toBe('value1');
        expect($data['operations'][1]['value'])->toBe(['data' => 'value2']);
    });

    test('operation has null value for miss and delete', function () {
        CacheCollector::logMiss('key1');
        CacheCollector::logDelete('key2');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['operations'][0]['value'])->toBeNull();
        expect($data['operations'][1]['value'])->toBeNull();
    });

    test('can use generic logOperation method', function () {
        CacheCollector::logOperation('custom', 'key', 'value', 10.0);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['operations'][0]['type'])->toBe('custom');
    });
});

describe('CacheCollector Statistics', function () {
    test('calculates total operations', function () {
        CacheCollector::logHit('key1', 'val');
        CacheCollector::logMiss('key2');
        CacheCollector::logWrite('key3', 'val');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['stats']['total'])->toBe(3);
    });

    test('calculates hit count', function () {
        CacheCollector::logHit('key1', 'val');
        CacheCollector::logHit('key2', 'val');
        CacheCollector::logMiss('key3');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['stats']['hits'])->toBe(2);
    });

    test('calculates miss count', function () {
        CacheCollector::logMiss('key1');
        CacheCollector::logMiss('key2');
        CacheCollector::logMiss('key3');
        CacheCollector::logHit('key4', 'val');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['stats']['misses'])->toBe(3);
    });

    test('calculates write count', function () {
        CacheCollector::logWrite('key1', 'val1');
        CacheCollector::logWrite('key2', 'val2');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['stats']['writes'])->toBe(2);
    });

    test('calculates delete count', function () {
        CacheCollector::logDelete('key1');
        CacheCollector::logDelete('key2');
        CacheCollector::logDelete('key3');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['stats']['deletes'])->toBe(3);
    });

    test('statistics are zero when no operations', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['stats']['total'])->toBe(0);
        expect($data['stats']['hits'])->toBe(0);
        expect($data['stats']['misses'])->toBe(0);
        expect($data['stats']['writes'])->toBe(0);
        expect($data['stats']['deletes'])->toBe(0);
    });

    test('calculates comprehensive statistics', function () {
        CacheCollector::logHit('k1', 'v');
        CacheCollector::logHit('k2', 'v');
        CacheCollector::logMiss('k3');
        CacheCollector::logWrite('k4', 'v');
        CacheCollector::logWrite('k5', 'v');
        CacheCollector::logWrite('k6', 'v');
        CacheCollector::logDelete('k7');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['stats'])->toBe([
            'total' => 7,
            'hits' => 2,
            'misses' => 1,
            'writes' => 3,
            'deletes' => 1,
        ]);
    });
});

describe('CacheCollector Badge', function () {
    test('returns null badge when no operations', function () {
        $this->collector->collect();
        
        expect($this->collector->getBadge())->toBeNull();
    });

    test('returns count as badge when operations exist', function () {
        CacheCollector::logHit('k1', 'v');
        CacheCollector::logMiss('k2');
        CacheCollector::logWrite('k3', 'v');
        
        $this->collector->collect();
        
        expect($this->collector->getBadge())->toBe('3');
    });

    test('badge is string type', function () {
        CacheCollector::logHit('key', 'val');
        
        $this->collector->collect();
        
        expect($this->collector->getBadge())->toBeString();
    });
});

describe('CacheCollector Rendering', function () {
    test('renders empty state when no operations', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('No cache operations');
    });

    test('renders statistics section', function () {
        CacheCollector::logHit('k1', 'v');
        CacheCollector::logMiss('k2');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Total');
        expect($html)->toContain('Hits');
        expect($html)->toContain('Misses');
        expect($html)->toContain('Writes');
        expect($html)->toContain('Deletes');
    });

    test('displays hit rate percentage', function () {
        CacheCollector::logHit('k1', 'v');
        CacheCollector::logHit('k2', 'v');
        CacheCollector::logMiss('k3');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Hit Rate');
        expect($html)->toContain('%');
        // 2 hits out of 3 lookups = 66.7%
        expect($html)->toMatch('/66\.\d+%/');
    });

    test('renders operation list', function () {
        CacheCollector::logHit('user:123', 'John');
        CacheCollector::logMiss('user:999');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('HIT');
        expect($html)->toContain('MISS');
        expect($html)->toContain('user:123');
        expect($html)->toContain('user:999');
    });

    test('shows operation type in uppercase', function () {
        CacheCollector::logHit('k', 'v');
        CacheCollector::logMiss('k');
        CacheCollector::logWrite('k', 'v');
        CacheCollector::logDelete('k');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('HIT');
        expect($html)->toContain('MISS');
        expect($html)->toContain('WRITE');
        expect($html)->toContain('DELETE');
    });

    test('color codes operations', function () {
        CacheCollector::logHit('k1', 'v');
        CacheCollector::logMiss('k2');
        CacheCollector::logWrite('k3', 'v');
        CacheCollector::logDelete('k4');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('#66bb6a'); // green for hit
        expect($html)->toContain('#ffa726'); // orange for miss
        expect($html)->toContain('#42a5f5'); // blue for write
        expect($html)->toContain('#ef5350'); // red for delete
    });

    test('displays operation execution time', function () {
        CacheCollector::logHit('key', 'value', 15.5);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('15.5');
    });

    test('displays value preview for hit operations', function () {
        CacheCollector::logHit('key', 'test value');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Value:');
        expect($html)->toContain('test value');
    });

    test('displays value preview for write operations', function () {
        CacheCollector::logWrite('key', 'cached data');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Value:');
        expect($html)->toContain('cached data');
    });

    test('truncates long string values', function () {
        $longValue = str_repeat('x', 100);
        CacheCollector::logHit('key', $longValue);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('...');
    });

    test('formats array values', function () {
        CacheCollector::logWrite('key', ['a' => 1, 'b' => 2, 'c' => 3]);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Array (3 items)');
    });

    test('formats object values', function () {
        CacheCollector::logHit('key', (object)['prop' => 'value']);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Object (stdClass)');
    });

    test('renders scrollable container', function () {
        CacheCollector::logHit('key', 'val');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('overflow-y: auto');
        expect($html)->toContain('max-height: 350px');
    });

    test('escapes HTML in cache keys', function () {
        CacheCollector::logHit('<script>alert(1)</script>', 'value');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('&lt;script&gt;');
    });
});

describe('CacheCollector Header Stats', function () {
    test('returns empty array when no operations', function () {
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats)->toBe([]);
    });

    test('returns stats array when operations exist', function () {
        CacheCollector::logHit('key', 'val');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats)->toBeArray();
        expect($stats)->toHaveCount(1);
    });

    test('header stat has correct structure', function () {
        CacheCollector::logHit('key', 'val');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0])->toHaveKey('icon');
        expect($stats[0])->toHaveKey('value');
        expect($stats[0])->toHaveKey('color');
    });

    test('header stat shows operation and hit count', function () {
        CacheCollector::logHit('k1', 'v');
        CacheCollector::logHit('k2', 'v');
        CacheCollector::logMiss('k3');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['value'])->toContain('3 cache ops');
        expect($stats[0]['value'])->toContain('2 hits');
    });

    test('header stat icon is correct', function () {
        CacheCollector::logHit('key', 'val');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['icon'])->toBe('🗃️');
    });

    test('header stat color is green', function () {
        CacheCollector::logHit('key', 'val');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['color'])->toBe('#66bb6a');
    });
});

describe('CacheCollector Integration', function () {
    test('tracks typical cache workflow', function () {
        // Try to get from cache (miss)
        CacheCollector::logMiss('user:123', 2.5);
        
        // Write to cache
        CacheCollector::logWrite('user:123', ['name' => 'John'], 5.0);
        
        // Get from cache (hit)
        CacheCollector::logHit('user:123', ['name' => 'John'], 1.5);
        
        // Another hit
        CacheCollector::logHit('user:123', ['name' => 'John'], 1.0);
        
        // Delete from cache
        CacheCollector::logDelete('user:123', 2.0);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['operations'])->toHaveCount(5);
        expect($data['stats'])->toBe([
            'total' => 5,
            'hits' => 2,
            'misses' => 1,
            'writes' => 1,
            'deletes' => 1,
        ]);
    });

    test('calculates hit rate correctly', function () {
        // 70% hit rate
        CacheCollector::logHit('k1', 'v');
        CacheCollector::logHit('k2', 'v');
        CacheCollector::logHit('k3', 'v');
        CacheCollector::logHit('k4', 'v');
        CacheCollector::logHit('k5', 'v');
        CacheCollector::logHit('k6', 'v');
        CacheCollector::logHit('k7', 'v');
        CacheCollector::logMiss('k8');
        CacheCollector::logMiss('k9');
        CacheCollector::logMiss('k10');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('70.0%');
    });

    test('can be disabled', function () {
        $this->collector->setEnabled(false);
        
        expect($this->collector->isEnabled())->toBeFalse();
    });

    test('handles many operations efficiently', function () {
        for ($i = 0; $i < 100; $i++) {
            CacheCollector::logHit("key$i", "value$i");
        }
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['operations'])->toHaveCount(100);
        expect($data['stats']['total'])->toBe(100);
        expect($data['stats']['hits'])->toBe(100);
    });
});
