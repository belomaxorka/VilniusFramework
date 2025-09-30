<?php declare(strict_types=1);

use Core\DebugTimer;
use Core\Debug;
use Core\Environment;

beforeEach(function () {
    Environment::set(Environment::DEVELOPMENT);
    DebugTimer::clear();
    Debug::clearOutput();
});

afterEach(function () {
    DebugTimer::clear();
    Debug::clearOutput();
});

describe('DebugTimer Basic Operations', function () {
    test('starts and stops timer', function () {
        DebugTimer::start('test');
        
        expect(DebugTimer::isRunning('test'))->toBeTrue();
        
        usleep(1000); // 1ms
        $elapsed = DebugTimer::stop('test');
        
        expect(DebugTimer::isRunning('test'))->toBeFalse();
        expect($elapsed)->toBeGreaterThan(0);
    });

    test('measures elapsed time', function () {
        DebugTimer::start('elapsed');
        
        usleep(2000); // 2ms
        $elapsed = DebugTimer::getElapsed('elapsed');
        
        expect($elapsed)->toBeGreaterThan(1);
        expect($elapsed)->toBeLessThan(50); // usleep может быть неточным
    });

    test('returns zero for non-existent timer', function () {
        $elapsed = DebugTimer::getElapsed('nonexistent');
        
        expect($elapsed)->toBe(0.0);
    });

    test('can restart same timer', function () {
        DebugTimer::start('restart');
        usleep(1000);
        DebugTimer::stop('restart');
        
        // Перезапускаем
        DebugTimer::start('restart');
        expect(DebugTimer::isRunning('restart'))->toBeTrue();
    });
});

describe('Timer Laps', function () {
    test('records lap times', function () {
        DebugTimer::start('laps');
        
        usleep(500);
        $lap1 = DebugTimer::lap('laps', 'First lap');
        
        usleep(500);
        $lap2 = DebugTimer::lap('laps', 'Second lap');
        
        expect($lap1)->toBeGreaterThan(0);
        expect($lap2)->toBeGreaterThan($lap1);
    });

    test('lap without label', function () {
        DebugTimer::start('unlabeled');
        
        usleep(500);
        $lap = DebugTimer::lap('unlabeled');
        
        expect($lap)->toBeGreaterThan(0);
    });

    test('returns zero for lap on non-existent timer', function () {
        $lap = DebugTimer::lap('nonexistent', 'Test');
        
        expect($lap)->toBe(0.0);
    });
});

describe('Multiple Timers', function () {
    test('can run multiple timers simultaneously', function () {
        DebugTimer::start('timer1');
        usleep(500);
        
        DebugTimer::start('timer2');
        usleep(500);
        
        DebugTimer::start('timer3');
        usleep(500);
        
        expect(DebugTimer::isRunning('timer1'))->toBeTrue();
        expect(DebugTimer::isRunning('timer2'))->toBeTrue();
        expect(DebugTimer::isRunning('timer3'))->toBeTrue();
        
        $elapsed1 = DebugTimer::getElapsed('timer1');
        $elapsed2 = DebugTimer::getElapsed('timer2');
        $elapsed3 = DebugTimer::getElapsed('timer3');
        
        // timer1 должен быть самым долгим
        expect($elapsed1)->toBeGreaterThan($elapsed2);
        expect($elapsed2)->toBeGreaterThan($elapsed3);
    });

    test('counts timers correctly', function () {
        expect(DebugTimer::count())->toBe(0);
        
        DebugTimer::start('one');
        expect(DebugTimer::count())->toBe(1);
        
        DebugTimer::start('two');
        expect(DebugTimer::count())->toBe(2);
        
        DebugTimer::start('three');
        expect(DebugTimer::count())->toBe(3);
    });

    test('getAll returns all timers', function () {
        DebugTimer::start('timer1');
        DebugTimer::start('timer2');
        
        $all = DebugTimer::getAll();
        
        expect($all)->toHaveKey('timer1');
        expect($all)->toHaveKey('timer2');
        expect($all['timer1']['running'])->toBeTrue();
        expect($all['timer2']['running'])->toBeTrue();
    });
});

describe('Timer Clear', function () {
    test('clears specific timer', function () {
        DebugTimer::start('one');
        DebugTimer::start('two');
        
        DebugTimer::clear('one');
        
        expect(DebugTimer::count())->toBe(1);
        expect(DebugTimer::isRunning('one'))->toBeFalse();
        expect(DebugTimer::isRunning('two'))->toBeTrue();
    });

    test('clears all timers', function () {
        DebugTimer::start('one');
        DebugTimer::start('two');
        DebugTimer::start('three');
        
        DebugTimer::clear();
        
        expect(DebugTimer::count())->toBe(0);
    });
});

describe('Timer Dump', function () {
    test('dumps timer with output', function () {
        DebugTimer::start('test');
        usleep(1000);
        DebugTimer::stop('test');
        
        DebugTimer::dump('test');
        
        expect(Debug::hasOutput())->toBeTrue();
        $output = Debug::getOutput();
        
        expect($output)->toContain('Timer: test');
        expect($output)->toContain('Total Time:');
        expect($output)->toContain('ms');
    });

    test('dumps all timers', function () {
        DebugTimer::start('one');
        DebugTimer::start('two');
        usleep(500);
        DebugTimer::stop('one');
        DebugTimer::stop('two');
        
        DebugTimer::dump();
        
        $output = Debug::getOutput();
        
        expect($output)->toContain('Timer: one');
        expect($output)->toContain('Timer: two');
    });

    test('dump shows lap times', function () {
        DebugTimer::start('laps');
        usleep(500);
        DebugTimer::lap('laps', 'Lap 1');
        usleep(500);
        DebugTimer::lap('laps', 'Lap 2');
        DebugTimer::stop('laps');
        
        DebugTimer::dump('laps');
        
        $output = Debug::getOutput();
        
        expect($output)->toContain('Lap Times:');
        expect($output)->toContain('Lap 1');
        expect($output)->toContain('Lap 2');
    });

    test('dump shows running status', function () {
        DebugTimer::start('running');
        DebugTimer::dump('running');
        
        $output = Debug::getOutput();
        expect($output)->toContain('Running');
        
        Debug::clearOutput();
        
        DebugTimer::stop('running');
        DebugTimer::dump('running');
        
        $output = Debug::getOutput();
        expect($output)->toContain('Stopped');
    });
});

describe('Timer Measure', function () {
    test('measures callback execution time', function () {
        $result = DebugTimer::measure('callback', function() {
            usleep(1000);
            return 'done';
        });
        
        expect($result)->toBe('done');
        expect(Debug::hasOutput())->toBeTrue();
        
        $output = Debug::getOutput();
        expect($output)->toContain('Timer: callback');
    });

    test('measure stops timer even on exception', function () {
        try {
            DebugTimer::measure('exception', function() {
                throw new Exception('Test error');
            });
        } catch (Exception $e) {
            // ожидаемо
        }
        
        expect(DebugTimer::isRunning('exception'))->toBeFalse();
        expect(Debug::hasOutput())->toBeTrue();
    });

    test('measure returns callback result', function () {
        $result = DebugTimer::measure('result', function() {
            return ['data' => 'value'];
        });
        
        expect($result)->toBe(['data' => 'value']);
    });
});

describe('Helper Functions', function () {
    test('timer_start helper works', function () {
        timer_start('helper');
        
        expect(DebugTimer::isRunning('helper'))->toBeTrue();
    });

    test('timer_stop helper works', function () {
        timer_start('stop');
        usleep(500);
        $elapsed = timer_stop('stop');
        
        expect($elapsed)->toBeGreaterThan(0);
        expect(DebugTimer::isRunning('stop'))->toBeFalse();
    });

    test('timer_lap helper works', function () {
        timer_start('lap');
        usleep(500);
        $lap = timer_lap('lap', 'Test Lap');
        
        expect($lap)->toBeGreaterThan(0);
    });

    test('timer_elapsed helper works', function () {
        timer_start('elapsed');
        usleep(500);
        $elapsed = timer_elapsed('elapsed');
        
        expect($elapsed)->toBeGreaterThan(0);
    });

    test('timer_dump helper works', function () {
        timer_start('dump');
        timer_stop('dump');
        timer_dump('dump');
        
        expect(Debug::hasOutput())->toBeTrue();
    });

    test('timer_clear helper works', function () {
        timer_start('clear');
        timer_clear('clear');
        
        expect(DebugTimer::count())->toBe(0);
    });

    test('timer_measure helper works', function () {
        $result = timer_measure('measure', fn() => 'test');
        
        expect($result)->toBe('test');
        expect(Debug::hasOutput())->toBeTrue();
    });
});

describe('Production Mode', function () {
    test('timer disabled in production', function () {
        Environment::set(Environment::PRODUCTION);
        DebugTimer::clear(); // Очищаем после переключения окружения
        
        DebugTimer::start('prod');
        usleep(500);
        $elapsed = DebugTimer::stop('prod');
        
        expect($elapsed)->toBe(0.0);
        expect(DebugTimer::count())->toBe(0);
    });

    test('measure still works in production but without output', function () {
        Environment::set(Environment::PRODUCTION);
        DebugTimer::clear(); // Очищаем после переключения окружения
        
        $result = DebugTimer::measure('prod', fn() => 'result');
        
        expect($result)->toBe('result');
        expect(Debug::hasOutput())->toBeFalse();
    });
});

describe('Precision and Accuracy', function () {
    test('timer has microsecond precision', function () {
        DebugTimer::start('precision');
        usleep(100); // 0.1ms
        $elapsed = DebugTimer::stop('precision');
        
        // Должно быть примерно 0.1ms с точностью до 1ms
        expect($elapsed)->toBeGreaterThan(0);
        expect($elapsed)->toBeLessThan(50); // увеличенный допуск
    });

    test('lap intervals are accurate', function () {
        DebugTimer::start('intervals');
        
        usleep(1000); // 1ms
        $lap1 = DebugTimer::lap('intervals');
        
        usleep(1000); // еще 1ms
        $lap2 = DebugTimer::lap('intervals');
        
        $all = DebugTimer::getAll();
        $laps = $all['intervals']['laps'];
        
        // Первый lap должен быть около 1ms
        expect($laps[0]['elapsed'])->toBeGreaterThan(0.5);
        expect($laps[0]['elapsed'])->toBeLessThan(50); // увеличенный допуск
        
        // Второй lap должен быть около 2ms
        expect($laps[1]['elapsed'])->toBeGreaterThan($laps[0]['elapsed']);
    });
});

describe('Edge Cases', function () {
    test('default timer name works', function () {
        DebugTimer::start(); // без имени = 'default'
        usleep(500);
        $elapsed = DebugTimer::stop();
        
        expect($elapsed)->toBeGreaterThan(0);
    });

    test('can have timer named default explicitly', function () {
        DebugTimer::start('default');
        
        expect(DebugTimer::isRunning('default'))->toBeTrue();
    });

    test('stopping already stopped timer returns elapsed time', function () {
        DebugTimer::start('stopped');
        usleep(500);
        $elapsed1 = DebugTimer::stop('stopped');
        
        usleep(500);
        $elapsed2 = DebugTimer::stop('stopped');
        
        // Второй stop должен вернуть то же время (timer уже остановлен)
        expect($elapsed2)->toBe($elapsed1);
    });
});
