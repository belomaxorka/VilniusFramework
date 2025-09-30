<?php declare(strict_types=1);

use Core\DebugContext;
use Core\Debug;
use Core\Environment;

beforeEach(function () {
    Environment::set(Environment::DEVELOPMENT);
    DebugContext::clear();
    Debug::clearOutput();
});

afterEach(function () {
    DebugContext::clear();
    Debug::clearOutput();
});

describe('DebugContext Basic Operations', function () {
    test('starts and ends context', function () {
        DebugContext::start('test');
        
        expect(DebugContext::current())->toBe('test');
        expect(DebugContext::exists('test'))->toBeTrue();
        
        DebugContext::end('test');
        
        expect(DebugContext::current())->toBeNull();
    });

    test('creates context with default config', function () {
        DebugContext::start('custom');
        
        $context = DebugContext::get('custom');
        
        expect($context)->toHaveKey('config');
        expect($context['config'])->toHaveKey('color');
        expect($context['config'])->toHaveKey('icon');
        expect($context['config'])->toHaveKey('label');
    });

    test('creates context with preset config', function () {
        DebugContext::start('database');
        
        $context = DebugContext::get('database');
        
        expect($context['config']['icon'])->toBe('🗄️');
        expect($context['config']['label'])->toBe('Database');
    });

    test('creates context with custom config', function () {
        $config = [
            'color' => '#ff0000',
            'icon' => '🔥',
            'label' => 'Custom Context'
        ];
        
        DebugContext::start('custom', $config);
        
        $context = DebugContext::get('custom');
        
        expect($context['config'])->toBe($config);
    });
});

describe('Context Stack', function () {
    test('supports nested contexts', function () {
        DebugContext::start('outer');
        expect(DebugContext::current())->toBe('outer');
        
        DebugContext::start('inner');
        expect(DebugContext::current())->toBe('inner');
        
        DebugContext::end('inner');
        expect(DebugContext::current())->toBe('outer');
        
        DebugContext::end('outer');
        expect(DebugContext::current())->toBeNull();
    });

    test('auto ends current context', function () {
        DebugContext::start('context1');
        DebugContext::start('context2');
        
        expect(DebugContext::current())->toBe('context2');
        
        DebugContext::end(); // без имени - текущий
        
        expect(DebugContext::current())->toBe('context1');
    });

    test('tracks parent context', function () {
        DebugContext::start('parent');
        DebugContext::start('child');
        
        $child = DebugContext::get('child');
        
        expect($child['parent'])->toBe('parent');
    });
});

describe('Context Run', function () {
    test('runs callback in context', function () {
        $result = DebugContext::run('test', function() {
            expect(DebugContext::current())->toBe('test');
            return 'result';
        });
        
        expect($result)->toBe('result');
        expect(DebugContext::current())->toBeNull();
    });

    test('ends context even on exception', function () {
        DebugContext::start('outer');
        
        try {
            DebugContext::run('test', function() {
                throw new Exception('Test error');
            });
        } catch (Exception $e) {
            // ожидаемо
        }
        
        // Контекст должен быть завершен
        expect(DebugContext::current())->toBe('outer');
    });

    test('runs with custom config', function () {
        $config = ['color' => '#00ff00', 'icon' => '✅', 'label' => 'Success'];
        
        DebugContext::run('test', function() {}, $config);
        
        $context = DebugContext::get('test');
        expect($context['config'])->toBe($config);
    });
});

describe('Context Items', function () {
    test('adds items to context', function () {
        DebugContext::start('test');
        DebugContext::add('info', 'Test message');
        DebugContext::add('data', ['key' => 'value']);
        
        $context = DebugContext::get('test');
        
        expect($context['items'])->toHaveCount(2);
        expect($context['items'][0]['type'])->toBe('info');
        expect($context['items'][1]['type'])->toBe('data');
    });

    test('adds to current context by default', function () {
        DebugContext::start('current');
        DebugContext::add('message', 'Test');
        
        $context = DebugContext::get('current');
        
        expect($context['items'])->toHaveCount(1);
    });

    test('can add to specific context', function () {
        DebugContext::start('context1');
        DebugContext::start('context2');
        
        DebugContext::add('msg', 'To context1', 'context1');
        
        $context1 = DebugContext::get('context1');
        $context2 = DebugContext::get('context2');
        
        expect($context1['items'])->toHaveCount(1);
        expect($context2['items'])->toHaveCount(0);
    });

    test('creates context if not exists when adding', function () {
        DebugContext::add('test', 'data', 'auto_created');
        
        expect(DebugContext::exists('auto_created'))->toBeTrue();
    });
});

describe('Context Filtering', function () {
    test('filters contexts on dump', function () {
        DebugContext::start('database');
        DebugContext::add('query', 'SELECT * FROM users');
        
        DebugContext::start('cache');
        DebugContext::add('get', 'user:1');
        
        DebugContext::enableFilter(['database']);
        
        expect(DebugContext::isEnabled('database'))->toBeTrue();
        expect(DebugContext::isEnabled('cache'))->toBeFalse();
    });

    test('disables filter', function () {
        DebugContext::enableFilter(['database']);
        
        expect(DebugContext::isEnabled('cache'))->toBeFalse();
        
        DebugContext::disableFilter();
        
        expect(DebugContext::isEnabled('cache'))->toBeTrue();
        expect(DebugContext::isEnabled('database'))->toBeTrue();
    });

    test('all enabled by default', function () {
        expect(DebugContext::isEnabled('any_context'))->toBeTrue();
    });
});

describe('Context Stats', function () {
    test('gets statistics', function () {
        DebugContext::run('test', function() {
            DebugContext::add('item1', 'data');
            DebugContext::add('item2', 'data');
            usleep(1000);
        });
        
        $stats = DebugContext::getStats();
        
        expect($stats)->toHaveKey('test');
        expect($stats['test']['items'])->toBe(2);
        expect($stats['test']['duration'])->toBeGreaterThan(0);
    });

    test('counts contexts', function () {
        expect(DebugContext::count())->toBe(0);
        
        DebugContext::start('one');
        expect(DebugContext::count())->toBe(1);
        
        DebugContext::start('two');
        expect(DebugContext::count())->toBe(2);
    });
});

describe('Context Dump', function () {
    test('dumps contexts with items', function () {
        DebugContext::start('database');
        DebugContext::add('query', 'SELECT * FROM users');
        DebugContext::add('result', '10 rows');
        DebugContext::end('database');
        
        DebugContext::dump();
        
        expect(Debug::hasOutput())->toBeTrue();
        $output = Debug::getOutput();
        
        expect($output)->toContain('Debug Contexts');
        expect($output)->toContain('Database');
        expect($output)->toContain('🗄️');
    });

    test('dumps specific contexts only', function () {
        DebugContext::start('database');
        DebugContext::add('query', 'Test');
        
        DebugContext::start('cache');
        DebugContext::add('get', 'Test');
        
        DebugContext::dump(['database']);
        
        $output = Debug::getOutput();
        
        expect($output)->toContain('Database');
    });

    test('shows duration for ended contexts', function () {
        DebugContext::run('test', function() {
            usleep(1000);
        });
        
        DebugContext::dump();
        
        $output = Debug::getOutput();
        expect($output)->toContain('ms');
    });
});

describe('Preset Contexts', function () {
    test('has predefined presets', function () {
        $presets = DebugContext::getPresets();
        
        expect($presets)->toHaveKey('database');
        expect($presets)->toHaveKey('cache');
        expect($presets)->toHaveKey('api');
        expect($presets)->toHaveKey('email');
    });

    test('all presets work', function () {
        $presets = ['database', 'cache', 'api', 'queue', 'email', 'security'];
        
        foreach ($presets as $preset) {
            DebugContext::start($preset);
            $context = DebugContext::get($preset);
            
            expect($context['config'])->toHaveKey('color');
            expect($context['config'])->toHaveKey('icon');
            expect($context['config'])->toHaveKey('label');
        }
    });

    test('can register custom preset', function () {
        DebugContext::register('custom', [
            'color' => '#123456',
            'icon' => '🎨',
            'label' => 'Custom'
        ]);
        
        $presets = DebugContext::getPresets();
        
        expect($presets)->toHaveKey('custom');
    });
});

describe('Helper Functions', function () {
    test('context_start helper works', function () {
        context_start('test');
        
        expect(DebugContext::current())->toBe('test');
    });

    test('context_end helper works', function () {
        context_start('test');
        context_end('test');
        
        expect(DebugContext::current())->toBeNull();
    });

    test('context_run helper works', function () {
        $result = context_run('test', fn() => 'value');
        
        expect($result)->toBe('value');
        expect(DebugContext::exists('test'))->toBeTrue();
    });

    test('context_current helper works', function () {
        context_start('test');
        
        expect(context_current())->toBe('test');
    });

    test('context_dump helper works', function () {
        context_start('test');
        context_dump();
        
        expect(Debug::hasOutput())->toBeTrue();
    });

    test('context_clear helper works', function () {
        context_start('test');
        context_clear();
        
        expect(DebugContext::count())->toBe(0);
    });

    test('context_filter helper works', function () {
        context_filter(['database']);
        
        expect(DebugContext::isEnabled('database'))->toBeTrue();
        expect(DebugContext::isEnabled('cache'))->toBeFalse();
    });
});

describe('Integration with Debug', function () {
    test('dump adds to current context', function () {
        context_start('test');
        
        dump(['data' => 'value'], 'Test Data');
        
        $context = DebugContext::get('test');
        
        expect($context['items'])->toHaveCount(1);
        expect($context['items'][0]['type'])->toBe('dump');
    });

    test('works with nested contexts', function () {
        context_run('database', function() {
            dump('Query executed');
            
            context_run('cache', function() {
                dump('Cache miss');
            });
        });
        
        expect(DebugContext::count())->toBe(2);
    });
});

describe('Production Mode', function () {
    test('context disabled in production', function () {
        Environment::set(Environment::PRODUCTION);
        
        DebugContext::start('test');
        DebugContext::add('item', 'data');
        
        expect(DebugContext::count())->toBe(0);
    });

    test('run still executes callback in production', function () {
        Environment::set(Environment::PRODUCTION);
        
        $result = DebugContext::run('test', fn() => 'result');
        
        expect($result)->toBe('result');
        expect(DebugContext::count())->toBe(0);
    });
});

describe('Real Usage Scenarios', function () {
    test('database operations context', function () {
        context_run('database', function() {
            DebugContext::add('query', 'SELECT * FROM users');
            timer_start('db_query');
            usleep(500);
            timer_stop('db_query');
            DebugContext::add('rows', 10);
        });
        
        $context = DebugContext::get('database');
        expect($context['items'])->toHaveCount(2);
    });

    test('API request context', function () {
        context_run('api', function() {
            DebugContext::add('endpoint', 'POST /api/users');
            memory_snapshot('before_request');
            // simulate request
            memory_snapshot('after_request');
            DebugContext::add('status', 200);
        });
        
        context_dump();
        
        $output = Debug::getOutput();
        // Проверяем наличие контекста (иконка или items)
        expect($output)->toContain('Debug Contexts');
        expect($output)->toContain('endpoint');
        expect($output)->toContain('POST /api/users');
    });
});
