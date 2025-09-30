<?php declare(strict_types=1);

use Core\DebugToolbar\Collectors\OverviewCollector;
use Core\Debug;
use Core\QueryDebugger;
use Core\DebugContext;
use Core\MemoryProfiler;

beforeEach(function () {
    Debug::clearOutput();
    QueryDebugger::clear();
    DebugContext::clear();
    $this->collector = new OverviewCollector();
});

afterEach(function () {
    Debug::clearOutput();
    QueryDebugger::clear();
    DebugContext::clear();
});

describe('OverviewCollector Configuration', function () {
    test('has correct name', function () {
        expect($this->collector->getName())->toBe('overview');
    });

    test('has correct title', function () {
        expect($this->collector->getTitle())->toBe('Overview');
    });

    test('has correct icon', function () {
        expect($this->collector->getIcon())->toBe('📊');
    });

    test('has highest priority', function () {
        expect($this->collector->getPriority())->toBe(100);
    });

    test('is enabled by default', function () {
        expect($this->collector->isEnabled())->toBeTrue();
    });
});

describe('OverviewCollector Data Collection', function () {
    test('collects execution time', function () {
        // Define APP_START constant for time calculation
        if (!defined('APP_START')) {
            define('APP_START', microtime(true) - 0.1); // Simulate 100ms execution
        }
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data)->toHaveKey('time');
        expect($data['time'])->toBeFloat();
        expect($data['time'])->toBeGreaterThanOrEqual(0);
    });

    test('collects memory usage', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data)->toHaveKey('memory');
        expect($data['memory'])->toBeInt();
        expect($data['memory'])->toBeGreaterThan(0);
    });

    test('collects peak memory', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data)->toHaveKey('peak_memory');
        expect($data['peak_memory'])->toBeInt();
        expect($data['peak_memory'])->toBeGreaterThan(0);
    });

    test('peak memory is greater than or equal to current memory', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['peak_memory'])->toBeGreaterThanOrEqual($data['memory']);
    });

    test('collects queries count', function () {
        QueryDebugger::log('SELECT * FROM users', [], 10.0, 5);
        QueryDebugger::log('SELECT * FROM posts', [], 5.0, 3);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['queries'])->toBe(2);
    });

    test('queries count is zero when no queries', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['queries'])->toBe(0);
    });

    test('collects slow queries count', function () {
        QueryDebugger::setSlowQueryThreshold(10.0);
        QueryDebugger::log('FAST', [], 5.0);
        QueryDebugger::log('SLOW', [], 50.0);
        QueryDebugger::log('VERY SLOW', [], 100.0);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['slow_queries'])->toBe(2);
    });

    test('collects total query time', function () {
        QueryDebugger::log('Q1', [], 10.0);
        QueryDebugger::log('Q2', [], 15.0);
        QueryDebugger::log('Q3', [], 5.0);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['query_time'])->toBe(30.0);
    });

    test('collects contexts count', function () {
        DebugContext::start('test1');
        DebugContext::start('test2');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        // 2 custom + 2 default (general, database)
        expect($data['contexts'])->toBeGreaterThanOrEqual(2);
    });

    test('collects dumps count', function () {
        dump('test1');
        dump('test2');
        dump('test3');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['dumps'])->toBe(3);
    });

    test('dumps count is zero when no dumps', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['dumps'])->toBe(0);
    });
});

describe('OverviewCollector Rendering', function () {
    test('renders HTML content', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toBeString();
        expect($html)->not->toBeEmpty();
    });

    test('includes overview title', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Request Overview');
    });

    test('includes performance section', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Performance');
        expect($html)->toContain('Total Time');
    });

    test('includes memory section', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Memory');
        expect($html)->toContain('Current');
        expect($html)->toContain('Peak');
    });

    test('includes database section when queries exist', function () {
        QueryDebugger::log('SELECT 1', [], 5.0);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Database');
        expect($html)->toContain('Queries');
        expect($html)->toContain('Slow');
    });

    test('excludes database section when no queries', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->not->toContain('Database');
    });

    test('includes debug section', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Debug');
        expect($html)->toContain('Dumps');
        expect($html)->toContain('Contexts');
    });

    test('renders query time when queries exist', function () {
        QueryDebugger::log('SELECT 1', [], 25.5);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Query Time');
    });

    test('displays formatted values', function () {
        QueryDebugger::log('Q1', [], 10.0);
        dump('test');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        // Check that values are displayed (not just labels)
        expect($html)->toMatch('/Queries:<\/strong>\s*\d+/');
        expect($html)->toMatch('/Dumps:<\/strong>\s*\d+/');
    });
});

describe('OverviewCollector Header Stats', function () {
    test('returns array of header stats', function () {
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats)->toBeArray();
        expect($stats)->toHaveCount(2); // time and memory
    });

    test('includes time stat', function () {
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        $timeStat = $stats[0];
        expect($timeStat)->toHaveKey('icon');
        expect($timeStat)->toHaveKey('value');
        expect($timeStat)->toHaveKey('color');
        expect($timeStat['icon'])->toBe('⏱️');
    });

    test('includes memory stat', function () {
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        $memoryStat = $stats[1];
        expect($memoryStat)->toHaveKey('icon');
        expect($memoryStat)->toHaveKey('value');
        expect($memoryStat)->toHaveKey('color');
        expect($memoryStat['icon'])->toBe('💾');
    });

    test('time color is green for fast execution', function () {
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        $timeStat = $stats[0];
        // Assuming test runs fast (< 1000ms)
        expect($timeStat['color'])->toBe('#66bb6a');
    });

    test('memory color changes based on usage', function () {
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        $memoryStat = $stats[1];
        // Color should be one of: green, orange, or red
        expect($memoryStat['color'])->toBeIn(['#66bb6a', '#ffa726', '#ef5350']);
    });
});

describe('OverviewCollector Integration', function () {
    test('provides comprehensive overview with all data types', function () {
        // Define APP_START if not defined
        if (!defined('APP_START')) {
            define('APP_START', microtime(true) - 0.05);
        }
        
        // Add various data
        dump('test dump');
        QueryDebugger::log('SELECT * FROM users', [], 10.5, 100);
        DebugContext::start('api');
        DebugContext::add('request', 'GET /api/test');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        // Verify all data is collected
        expect($data['time'])->toBeGreaterThanOrEqual(0); // Can be 0 if constants not defined
        expect($data['memory'])->toBeGreaterThan(0);
        expect($data['peak_memory'])->toBeGreaterThan(0);
        expect($data['queries'])->toBe(1);
        expect($data['query_time'])->toBe(10.5);
        expect($data['contexts'])->toBeGreaterThan(0);
        expect($data['dumps'])->toBe(1);
    });

    test('rendered HTML includes all collected data', function () {
        dump('debug info');
        QueryDebugger::log('SELECT 1', [], 5.0);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        // Should contain sections for all data types
        expect($html)->toContain('Performance');
        expect($html)->toContain('Memory');
        expect($html)->toContain('Database');
        expect($html)->toContain('Debug');
    });

    test('handles empty state gracefully', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        // Should still render without errors
        expect($html)->toBeString();
        expect($html)->not->toBeEmpty();
        expect($html)->toContain('Overview');
    });
});
