<?php declare(strict_types=1);

use Core\MemoryProfiler;
use Core\Debug;
use Core\Environment;

beforeEach(function () {
    Environment::set(Environment::DEVELOPMENT);
    MemoryProfiler::clear();
    Debug::clearOutput();
});

afterEach(function () {
    MemoryProfiler::clear();
    Debug::clearOutput();
});

describe('MemoryProfiler Basic Operations', function () {
    test('starts profiling and creates initial snapshot', function () {
        MemoryProfiler::start();
        
        $snapshots = MemoryProfiler::getSnapshots();
        
        expect($snapshots)->toHaveCount(1);
        expect($snapshots[0]['name'])->toBe('start');
    });

    test('creates snapshots', function () {
        MemoryProfiler::start();
        
        MemoryProfiler::snapshot('test1', 'First snapshot');
        MemoryProfiler::snapshot('test2', 'Second snapshot');
        
        expect(MemoryProfiler::count())->toBe(3); // start + 2 snapshots
    });

    test('tracks memory difference between snapshots', function () {
        MemoryProfiler::start();
        
        // Выделяем память
        $data = range(1, 1000);
        MemoryProfiler::snapshot('after_array', 'After creating array');
        
        $snapshots = MemoryProfiler::getSnapshots();
        $lastSnapshot = end($snapshots);
        
        // Должна быть разница в памяти
        expect($lastSnapshot)->toHaveKey('diff');
        expect($lastSnapshot)->toHaveKey('diff_from_start');
    });

    test('gets current memory usage', function () {
        $current = MemoryProfiler::current();
        
        expect($current)->toBeInt();
        expect($current)->toBeGreaterThan(0);
    });

    test('gets peak memory usage', function () {
        $peak = MemoryProfiler::peak();
        
        expect($peak)->toBeInt();
        expect($peak)->toBeGreaterThan(0);
    });
});

describe('Memory Snapshots', function () {
    test('snapshot without label', function () {
        MemoryProfiler::start();
        
        $snapshot = MemoryProfiler::snapshot('test');
        
        expect($snapshot['name'])->toBe('test');
        expect($snapshot['label'])->toBeNull();
    });

    test('snapshot with label', function () {
        MemoryProfiler::start();
        
        $snapshot = MemoryProfiler::snapshot('test', 'Test Label');
        
        expect($snapshot['label'])->toBe('Test Label');
    });

    test('snapshot returns memory info', function () {
        MemoryProfiler::start();
        
        $snapshot = MemoryProfiler::snapshot('test');
        
        expect($snapshot)->toHaveKey('memory');
        expect($snapshot)->toHaveKey('peak');
        expect($snapshot)->toHaveKey('diff');
        expect($snapshot)->toHaveKey('diff_from_start');
        expect($snapshot)->toHaveKey('timestamp');
    });

    test('calculates diff from previous snapshot', function () {
        MemoryProfiler::start();
        
        $snapshot1 = MemoryProfiler::snapshot('first');
        
        // Выделяем память
        $data = str_repeat('x', 10000);
        
        $snapshot2 = MemoryProfiler::snapshot('second');
        
        // Второй snapshot должен показать увеличение памяти
        expect($snapshot2['diff'])->toBeGreaterThanOrEqual(0);
    });
});

describe('Memory Measure', function () {
    test('measures memory used by callback', function () {
        $result = MemoryProfiler::measure('test_function', function() {
            return range(1, 100);
        });
        
        expect($result)->toHaveCount(100);
        expect(MemoryProfiler::count())->toBeGreaterThan(0);
    });

    test('measure creates start and end snapshots', function () {
        MemoryProfiler::measure('operation', function() {
            $data = range(1, 1000);
        });
        
        $snapshots = MemoryProfiler::getSnapshots();
        
        expect(count($snapshots))->toBeGreaterThanOrEqual(2);
    });

    test('measure returns callback result', function () {
        $result = MemoryProfiler::measure('calc', function() {
            return 42;
        });
        
        expect($result)->toBe(42);
    });

    test('measure shows memory diff in output', function () {
        MemoryProfiler::measure('test', function() {
            $data = range(1, 1000);
        });
        
        expect(Debug::hasOutput())->toBeTrue();
        $output = Debug::getOutput();
        expect($output)->toContain('Memory:');
        expect($output)->toContain('test');
    });
});

describe('Memory Dump', function () {
    test('dumps memory profile', function () {
        MemoryProfiler::start();
        MemoryProfiler::snapshot('test1');
        MemoryProfiler::snapshot('test2');
        
        MemoryProfiler::dump();
        
        expect(Debug::hasOutput())->toBeTrue();
        $output = Debug::getOutput();
        
        expect($output)->toContain('Memory Profile');
        expect($output)->toContain('Current Memory:');
        expect($output)->toContain('Peak Memory:');
    });

    test('dump shows snapshots table', function () {
        MemoryProfiler::start();
        MemoryProfiler::snapshot('snap1', 'First');
        MemoryProfiler::snapshot('snap2', 'Second');
        
        MemoryProfiler::dump();
        
        $output = Debug::getOutput();
        
        expect($output)->toContain('Memory Snapshots:');
        expect($output)->toContain('snap1');
        expect($output)->toContain('snap2');
        expect($output)->toContain('First');
        expect($output)->toContain('Second');
    });

    test('dump shows memory limit', function () {
        MemoryProfiler::start();
        MemoryProfiler::dump();
        
        $output = Debug::getOutput();
        expect($output)->toContain('Memory Limit:');
    });
});

describe('Format Bytes', function () {
    test('formats bytes correctly', function () {
        expect(MemoryProfiler::formatBytes(0))->toBe('0 B');
        expect(MemoryProfiler::formatBytes(1024))->toBe('1.00 KB');
        expect(MemoryProfiler::formatBytes(1048576))->toBe('1.00 MB');
        expect(MemoryProfiler::formatBytes(1073741824))->toBe('1.00 GB');
    });

    test('formats with custom precision', function () {
        expect(MemoryProfiler::formatBytes(1536, 0))->toBe('2 KB');
        expect(MemoryProfiler::formatBytes(1536, 1))->toBe('1.5 KB');
        expect(MemoryProfiler::formatBytes(1536, 2))->toBe('1.50 KB');
    });

    test('handles negative values', function () {
        $formatted = MemoryProfiler::formatBytes(-1024);
        expect($formatted)->toBe('1.00 KB'); // берет abs
    });

    test('formats large values', function () {
        $formatted = MemoryProfiler::formatBytes(5368709120); // 5GB
        expect($formatted)->toContain('GB');
    });
});

describe('Memory Limit', function () {
    test('gets memory limit', function () {
        $limit = MemoryProfiler::getMemoryLimit();
        
        expect($limit)->toBeInt();
        expect($limit)->toBeGreaterThanOrEqual(0);
    });

    test('calculates usage percentage', function () {
        $percentage = MemoryProfiler::getUsagePercentage();
        
        expect($percentage)->toBeFloat();
        expect($percentage)->toBeGreaterThanOrEqual(0);
    });

    test('checks threshold', function () {
        $exceeded = MemoryProfiler::isThresholdExceeded(99);
        
        expect($exceeded)->toBeBool();
    });
});

describe('Helper Functions', function () {
    test('memory_start helper works', function () {
        memory_start();
        
        expect(MemoryProfiler::count())->toBeGreaterThan(0);
    });

    test('memory_snapshot helper works', function () {
        memory_start();
        $snapshot = memory_snapshot('test', 'Test');
        
        expect($snapshot)->toHaveKey('name');
        expect($snapshot['name'])->toBe('test');
    });

    test('memory_current helper works', function () {
        $current = memory_current();
        
        expect($current)->toBeInt();
        expect($current)->toBeGreaterThan(0);
    });

    test('memory_peak helper works', function () {
        $peak = memory_peak();
        
        expect($peak)->toBeInt();
        expect($peak)->toBeGreaterThan(0);
    });

    test('memory_dump helper works', function () {
        memory_start();
        memory_snapshot('test');
        memory_dump();
        
        expect(Debug::hasOutput())->toBeTrue();
    });

    test('memory_clear helper works', function () {
        memory_start();
        memory_snapshot('test');
        memory_clear();
        
        expect(MemoryProfiler::count())->toBe(0);
    });

    test('memory_measure helper works', function () {
        $result = memory_measure('test', fn() => 'result');
        
        expect($result)->toBe('result');
        expect(Debug::hasOutput())->toBeTrue();
    });

    test('memory_format helper works', function () {
        expect(memory_format(1024))->toBe('1.00 KB');
        expect(memory_format(1048576))->toBe('1.00 MB');
    });
});

describe('Production Mode', function () {
    test('profiler disabled in production', function () {
        Environment::set(Environment::PRODUCTION);
        MemoryProfiler::clear(); // Очищаем после переключения окружения
        
        MemoryProfiler::start();
        MemoryProfiler::snapshot('test');
        
        expect(MemoryProfiler::count())->toBe(0);
    });

    test('measure still works in production but without output', function () {
        Environment::set(Environment::PRODUCTION);
        MemoryProfiler::clear(); // Очищаем после переключения окружения
        
        $result = MemoryProfiler::measure('test', fn() => 'result');
        
        expect($result)->toBe('result');
        expect(Debug::hasOutput())->toBeFalse();
    });

    test('current and peak work in production', function () {
        Environment::set(Environment::PRODUCTION);
        MemoryProfiler::clear(); // Очищаем после переключения окружения
        
        $current = MemoryProfiler::current();
        $peak = MemoryProfiler::peak();
        
        expect($current)->toBeGreaterThan(0);
        expect($peak)->toBeGreaterThan(0);
    });
});

describe('Integration', function () {
    test('can use with timer profiler', function () {
        timer_start('combined');
        memory_start();
        
        // Операция
        $data = range(1, 1000);
        
        memory_snapshot('after_data');
        timer_lap('combined', 'Created data');
        
        timer_stop('combined');
        memory_dump();
        timer_dump('combined');
        
        $output = Debug::getOutput();
        expect($output)->toContain('Memory Profile');
        expect($output)->toContain('Timer: combined');
    });

    test('measure can be nested', function () {
        memory_measure('outer', function() {
            $outer = range(1, 100);
            
            timer_measure('inner', function() {
                $inner = range(1, 50);
            });
        });
        
        expect(Debug::hasOutput())->toBeTrue();
    });
});

describe('Real Usage Scenarios', function () {
    test('tracks memory in loop', function () {
        memory_start();
        
        for ($i = 0; $i < 5; $i++) {
            $data = range(1, 100);
            memory_snapshot("iteration_$i", "Iteration $i");
        }
        
        expect(MemoryProfiler::count())->toBe(6); // start + 5 iterations
    });

    test('detects memory leak simulation', function () {
        memory_start();
        
        $leaky = [];
        for ($i = 0; $i < 1000; $i++) {
            $leaky[] = str_repeat('x', 10000); // Больше данных для видимого роста
        }
        memory_snapshot('after_leak', 'Potential leak');
        
        $snapshots = MemoryProfiler::getSnapshots();
        $lastSnapshot = end($snapshots);
        
        // Должен показать рост памяти (с таким объемом данных точно будет рост)
        expect($lastSnapshot['diff_from_start'])->toBeGreaterThan(0);
        expect($lastSnapshot['label'])->toBe('Potential leak');
    });
});
