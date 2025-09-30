<?php declare(strict_types=1);

use Core\Debug;
use Core\Environment;
use Core\ErrorHandler;

beforeEach(function () {
    Environment::set(Environment::DEVELOPMENT);
    Debug::clear();
    Debug::clearOutput();
});

afterEach(function () {
    Debug::clear();
    Debug::clearOutput();
    ErrorHandler::reset();
});

describe('Debug and Environment Integration', function () {
    test('debug mode follows environment settings', function () {
        // Development: debug enabled
        Environment::set(Environment::DEVELOPMENT);
        expect(Environment::isDebug())->toBeTrue();
        
        dump(['test' => 'data']);
        expect(Debug::hasOutput())->toBeTrue();
        
        Debug::clearOutput();
        
        // Production: debug disabled
        Environment::set(Environment::PRODUCTION);
        expect(Environment::isDebug())->toBeFalse();
        
        dump(['test' => 'data']);
        expect(Debug::hasOutput())->toBeFalse();
    });

    test('testing mode supports debug', function () {
        Environment::set(Environment::TESTING);
        
        // В testing режиме debug можно включить через APP_DEBUG
        \Core\Env::set('APP_DEBUG', true);
        Environment::clearCache();
        
        expect(Environment::isDebug())->toBeTrue();
    });
});

describe('Debug Shutdown Handler', function () {
    test('registers shutdown handler', function () {
        // Проверяем, что метод существует и вызывается без ошибок
        expect(function() {
            Debug::registerShutdownHandler();
        })->not->toThrow(Exception::class);
    });

    test('auto display can be toggled', function () {
        Debug::setAutoDisplay(false);
        expect(Debug::isAutoDisplay())->toBeFalse();
        
        Debug::setAutoDisplay(true);
        expect(Debug::isAutoDisplay())->toBeTrue();
    });
});

describe('Error Handler Integration', function () {
    test('error handler can be registered with debug system', function () {
        expect(function() {
            ErrorHandler::register();
        })->not->toThrow(Exception::class);
    });

    test('environment config provides correct error settings', function () {
        Environment::set(Environment::DEVELOPMENT);
        $config = Environment::getConfig();
        
        expect($config)->toHaveKey('debug');
        expect($config)->toHaveKey('error_reporting');
        expect($config)->toHaveKey('display_errors');
        expect($config)->toHaveKey('log_errors');
        
        expect($config['debug'])->toBeTrue();
        expect($config['display_errors'])->toBe(1);
    });
});

describe('Cross-feature Scenarios', function () {
    test('collect and dump can be used together', function () {
        // Собираем данные
        collect(['user' => 'Alice'], 'User 1');
        collect(['user' => 'Bob'], 'User 2');
        
        // Также делаем прямой dump
        dump(['status' => 'active'], 'Status');
        
        // Выводим собранные данные
        dump_all();
        
        $output = Debug::getOutput();
        
        // Должно быть и то и другое
        expect($output)->toContain('User 1');
        expect($output)->toContain('User 2');
        expect($output)->toContain('Status');
        expect($output)->toContain('Debug Collection');
    });

    test('benchmark can be used with dump', function () {
        $result = benchmark(function() {
            dump(['inside' => 'benchmark']);
            return 'done';
        }, 'Operation');
        
        $output = Debug::getOutput();
        
        expect($result)->toBe('done');
        expect($output)->toContain('inside');
        expect($output)->toContain('benchmark');
        expect($output)->toContain('Benchmark');
        expect($output)->toContain('Operation');
    });

    test('trace and dump show different information', function () {
        dump(['data' => 'value'], 'Data Dump');
        trace('Call Stack');
        
        $output = Debug::getOutput();
        
        // Dump вывод
        expect($output)->toContain('Data Dump');
        expect($output)->toContain('data');
        
        // Trace вывод
        expect($output)->toContain('Backtrace');
        expect($output)->toContain('Call Stack');
    });
});

describe('Buffer Persistence', function () {
    test('buffer persists across multiple operations', function () {
        dump(['first' => 1]);
        $output1 = Debug::getOutput();
        
        dump(['second' => 2]);
        $output2 = Debug::getOutput();
        
        // Второй вывод должен содержать и первый и второй dump
        expect($output2)->toContain('first');
        expect($output2)->toContain('second');
        expect(strlen($output2))->toBeGreaterThan(strlen($output1));
    });

    test('flush clears buffer for next operations', function () {
        dump(['first' => 1]);
        
        ob_start();
        Debug::flush();
        ob_end_clean();
        
        expect(Debug::hasOutput())->toBeFalse();
        
        dump(['second' => 2]);
        $output = Debug::getOutput();
        
        expect($output)->toContain('second');
        expect($output)->not->toContain('first');
    });
});

describe('Complex Data Structures', function () {
    test('handles nested objects and arrays', function () {
        $complex = [
            'user' => new class {
                public string $name = 'John';
                public array $roles = ['admin', 'editor'];
                public object $settings;
                
                public function __construct() {
                    $this->settings = new class {
                        public bool $notifications = true;
                        public string $theme = 'dark';
                    };
                }
            },
            'metadata' => [
                'created' => '2024-01-01',
                'tags' => ['php', 'testing', 'debug']
            ]
        ];
        
        Debug::dump($complex, 'Complex Structure');
        $output = Debug::getOutput();
        
        expect($output)->toContain('John');
        expect($output)->toContain('admin');
        expect($output)->toContain('editor');
        expect($output)->toContain('notifications');
        expect($output)->toContain('dark');
        expect($output)->toContain('created');
        expect($output)->toContain('php');
        expect($output)->toContain('testing');
        expect($output)->toContain('debug');
    });

    test('handles mixed object array structures with dump_pretty', function () {
        $mixed = [
            'count' => 42,
            'active' => true,
            'user' => new class {
                public string $email = 'test@example.com';
            },
            'items' => [1, 2, 3]
        ];
        
        dump_pretty($mixed, 'Mixed Structure');
        $output = Debug::getOutput();
        
        // Проверяем цветовое форматирование
        expect($output)->toContain('color:'); // есть стили
        expect($output)->toContain('test@example.com');
        expect($output)->toContain('42');
        expect($output)->toContain('true');
    });
});

describe('State Management', function () {
    test('clear() only clears collected data, not output buffer', function () {
        collect(['data1' => 1], 'Collected');
        dump(['data2' => 2], 'Dumped');
        
        Debug::clear();
        
        // Output buffer должен остаться
        expect(Debug::hasOutput())->toBeTrue();
        
        // Но collected данных быть не должно
        dump_all();
        $output = Debug::getOutput();
        
        expect($output)->toContain('Dumped');
        expect($output)->not->toContain('Debug Collection'); // нет collected данных
    });

    test('clearOutput() only clears buffer, not collected data', function () {
        collect(['data1' => 1], 'Collected');
        dump(['data2' => 2], 'Dumped');
        
        Debug::clearOutput();
        
        expect(Debug::hasOutput())->toBeFalse();
        
        // Но collected данные должны остаться
        dump_all();
        expect(Debug::hasOutput())->toBeTrue();
        
        $output = Debug::getOutput();
        expect($output)->toContain('Collected');
    });

    test('both clear methods can work together', function () {
        collect(['data1' => 1]);
        dump(['data2' => 2]);
        
        Debug::clear();
        Debug::clearOutput();
        
        expect(Debug::hasOutput())->toBeFalse();
        
        dump_all();
        expect(Debug::hasOutput())->toBeFalse(); // нет collected данных для вывода
    });
});
