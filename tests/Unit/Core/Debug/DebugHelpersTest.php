<?php declare(strict_types=1);

use Core\Debug;
use Core\Environment;

beforeEach(function () {
    Environment::set(Environment::TESTING);
    Debug::clear();
    Debug::clearOutput();
});

afterEach(function () {
    Debug::clear();
    Debug::clearOutput();
});

describe('benchmark() function', function () {
    test('measures execution time', function () {
        $result = benchmark(function() {
            usleep(1000); // 1ms
            return 'test result';
        }, 'Test Operation');
        
        expect($result)->toBe('test result');
        expect(Debug::hasOutput())->toBeTrue();
        
        $output = Debug::getOutput();
        expect($output)->toContain('Benchmark');
        expect($output)->toContain('Test Operation');
        expect($output)->toContain('Execution time:');
        expect($output)->toContain('ms');
    });

    test('works without label', function () {
        benchmark(function() {
            return 42;
        });
        
        expect(Debug::hasOutput())->toBeTrue();
        $output = Debug::getOutput();
        expect($output)->toContain('Execution time:');
    });

    test('returns callback result', function () {
        $result = benchmark(function() {
            return ['data' => 'value'];
        });
        
        expect($result)->toBe(['data' => 'value']);
    });

    test('handles exceptions in callback', function () {
        expect(function() {
            benchmark(function() {
                throw new Exception('Test error');
            });
        })->toThrow(Exception::class, 'Test error');
    });

    test('disabled in production', function () {
        Environment::set(Environment::PRODUCTION);
        
        $result = benchmark(function() {
            return 'result';
        }, 'Should not log');
        
        expect($result)->toBe('result');
        expect(Debug::hasOutput())->toBeFalse();
    });
});

describe('trace() function', function () {
    test('outputs backtrace', function () {
        trace('Test Trace');
        
        expect(Debug::hasOutput())->toBeTrue();
        $output = Debug::getOutput();
        
        expect($output)->toContain('Backtrace');
        expect($output)->toContain('Test Trace');
        expect($output)->toContain('.php'); // должен содержать имена файлов
    });

    test('works without label', function () {
        trace();
        
        expect(Debug::hasOutput())->toBeTrue();
        $output = Debug::getOutput();
        expect($output)->toContain('Backtrace');
    });

    test('shows function call stack', function () {
        function testFunction() {
            trace('Inside testFunction');
        }
        
        testFunction();
        
        $output = Debug::getOutput();
        expect($output)->toContain('testFunction');
    });

    test('disabled in production', function () {
        Environment::set(Environment::PRODUCTION);
        
        trace('Should not output');
        
        expect(Debug::hasOutput())->toBeFalse();
    });
});

describe('Environment check functions', function () {
    test('is_debug() returns correct value', function () {
        Environment::set(Environment::DEVELOPMENT);
        expect(is_debug())->toBeTrue();
        
        Environment::set(Environment::PRODUCTION);
        expect(is_debug())->toBeFalse();
    });

    test('is_dev() returns correct value', function () {
        Environment::set(Environment::DEVELOPMENT);
        expect(is_dev())->toBeTrue();
        
        Environment::set(Environment::PRODUCTION);
        expect(is_dev())->toBeFalse();
    });

    test('is_prod() returns correct value', function () {
        Environment::set(Environment::PRODUCTION);
        expect(is_prod())->toBeTrue();
        
        Environment::set(Environment::DEVELOPMENT);
        expect(is_prod())->toBeFalse();
    });
});

describe('debug_log() function', function () {
    test('logs only in debug mode', function () {
        Environment::set(Environment::DEVELOPMENT);
        
        // Тестируем что функция не падает
        expect(function() {
            debug_log('Test message');
        })->not->toThrow(Exception::class);
    });

    test('does nothing in production', function () {
        Environment::set(Environment::PRODUCTION);
        
        expect(function() {
            debug_log('Should not log');
        })->not->toThrow(Exception::class);
    });
});

describe('render_debug() function', function () {
    test('returns debug output as string', function () {
        dump(['test' => 'data'], 'Test Data');
        
        $output = render_debug();
        
        expect($output)->toBeString();
        expect($output)->toContain('Test Data');
        expect($output)->toContain('test');
    });

    test('returns empty string when no output', function () {
        $output = render_debug();
        
        expect($output)->toBe('');
    });

    test('does not clear buffer', function () {
        dump(['test' => 'data']);
        
        render_debug();
        
        expect(Debug::hasOutput())->toBeTrue();
    });
});

describe('Multiple dumps interaction', function () {
    test('multiple dump calls accumulate in buffer', function () {
        dump(['first' => 1], 'First');
        dump(['second' => 2], 'Second');
        dump_pretty(['third' => 3], 'Third');
        
        $output = Debug::getOutput();
        
        expect($output)->toContain('First');
        expect($output)->toContain('Second');
        expect($output)->toContain('Third');
        expect($output)->toContain('"first"');
        expect($output)->toContain('"second"');
        expect($output)->toContain('"third"');
    });

    test('mix of dump, collect, and benchmark', function () {
        dump(['data1' => 1]);
        collect(['data2' => 2], 'Collected');
        benchmark(fn() => 'result', 'Benchmark Test');
        
        dump_all();
        
        $output = Debug::getOutput();
        
        expect($output)->toContain('data1');
        expect($output)->toContain('Collected');
        expect($output)->toContain('Benchmark Test');
    });
});

describe('Performance tests', function () {
    test('dump handles 100 calls efficiently', function () {
        $start = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            dump(['iteration' => $i]);
        }
        
        $duration = microtime(true) - $start;
        
        // Должно выполниться менее чем за 1 секунду
        expect($duration)->toBeLessThan(1.0);
        expect(Debug::hasOutput())->toBeTrue();
    });

    test('benchmark overhead is minimal', function () {
        // Прогреваем PHP для более стабильных результатов
        for ($warmup = 0; $warmup < 10; $warmup++) {
            $x = 0;
            for ($i = 0; $i < 100; $i++) {
                $x += $i;
            }
        }
        
        $directStart = microtime(true);
        $x = 0;
        for ($i = 0; $i < 10000; $i++) { // Больше итераций для измеримого времени
            $x += $i;
        }
        $directDuration = microtime(true) - $directStart;
        
        $benchStart = microtime(true);
        benchmark(function() {
            $x = 0;
            for ($i = 0; $i < 10000; $i++) {
                $x += $i;
            }
        });
        $benchDuration = microtime(true) - $benchStart;
        
        // Overhead должен быть разумным (менее 3x)
        expect($benchDuration)->toBeLessThan($directDuration * 3);
        // И benchmark должен вообще выполниться
        expect($benchDuration)->toBeGreaterThan(0);
    });
});
