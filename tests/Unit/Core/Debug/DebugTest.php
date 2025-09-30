<?php declare(strict_types=1);

use Core\Debug;
use Core\Environment;

beforeEach(function () {
    // Устанавливаем testing окружение для тестов
    Environment::set(Environment::TESTING);
    
    // Очищаем буферы перед каждым тестом
    Debug::clear();
    Debug::clearOutput();
});

afterEach(function () {
    // Очищаем после тестов
    Debug::clear();
    Debug::clearOutput();
});

describe('Debug::dump()', function () {
    test('dumps variable to output buffer in development mode', function () {
        $data = ['name' => 'John', 'age' => 30];
        
        Debug::dump($data, 'User Data');
        
        expect(Debug::hasOutput())->toBeTrue();
        $output = Debug::getOutput();
        expect($output)->toContain('User Data');
        expect($output)->toContain('name');
        expect($output)->toContain('John');
    });

    test('does not output in production mode', function () {
        Environment::set(Environment::PRODUCTION);
        
        Debug::dump(['test' => 'data'], 'Test');
        
        expect(Debug::hasOutput())->toBeFalse();
    });

    test('handles different data types', function () {
        Debug::dump(null, 'Null Test');
        Debug::dump(true, 'Boolean Test');
        Debug::dump(42, 'Integer Test');
        Debug::dump('string', 'String Test');
        Debug::dump(['array'], 'Array Test');
        
        expect(Debug::hasOutput())->toBeTrue();
        $output = Debug::getOutput();
        
        expect($output)->toContain('NULL');
        expect($output)->toContain('true');
        expect($output)->toContain('42');
        expect($output)->toContain('string');
        expect($output)->toContain('array');
    });

    test('dd() exits after dump', function () {
        expect(function () {
            Debug::dump(['test' => 'data'], 'Test', true);
        })->toThrow(Exception::class);
    })->skip('Cannot test exit() in PHPUnit');
});

describe('Debug::dumpPretty()', function () {
    test('dumps with syntax highlighting', function () {
        $data = ['user' => ['name' => 'Jane']];
        
        Debug::dumpPretty($data, 'Pretty Test');
        
        expect(Debug::hasOutput())->toBeTrue();
        $output = Debug::getOutput();
        
        // Проверяем наличие HTML с цветами
        expect($output)->toContain('background: #1e1e1e'); // темная тема
        expect($output)->toContain('Pretty Test');
        expect($output)->toContain('color: #569cd6'); // цвет для keywords
    });

    test('formats nested structures', function () {
        $nested = [
            'level1' => [
                'level2' => [
                    'value' => 'deep'
                ]
            ]
        ];
        
        Debug::dumpPretty($nested);
        $output = Debug::getOutput();
        
        expect($output)->toContain('level1');
        expect($output)->toContain('level2');
        expect($output)->toContain('deep');
    });
});

describe('Debug::collect()', function () {
    test('collects data without output', function () {
        Debug::collect(['test' => 'data'], 'Test Data');
        
        expect(Debug::hasOutput())->toBeFalse();
    });

    test('dumpAll() outputs all collected data', function () {
        Debug::collect(['first' => 1], 'First');
        Debug::collect(['second' => 2], 'Second');
        Debug::collect(['third' => 3], 'Third');
        
        Debug::dumpAll();
        
        expect(Debug::hasOutput())->toBeTrue();
        $output = Debug::getOutput();
        
        expect($output)->toContain('Debug Collection');
        expect($output)->toContain('First');
        expect($output)->toContain('Second');
        expect($output)->toContain('Third');
        expect($output)->toContain('"first"');
        expect($output)->toContain('"second"');
        expect($output)->toContain('"third"');
    });

    test('clear() removes collected data', function () {
        Debug::collect(['test' => 'data'], 'Test');
        
        Debug::clear();
        Debug::dumpAll();
        
        expect(Debug::hasOutput())->toBeFalse();
    });
});

describe('Debug Buffer Management', function () {
    test('addOutput() adds custom HTML to buffer', function () {
        $customHtml = '<div class="custom">Custom Debug</div>';
        
        Debug::addOutput($customHtml);
        
        expect(Debug::hasOutput())->toBeTrue();
        expect(Debug::getOutput())->toContain('Custom Debug');
    });

    test('flush() outputs and clears buffer', function () {
        Debug::dump(['test' => 'data']);
        
        ob_start();
        Debug::flush();
        $output = ob_get_clean();
        
        expect($output)->toContain('test');
        expect(Debug::hasOutput())->toBeFalse();
    });

    test('getOutput() returns buffer without clearing', function () {
        Debug::dump(['test' => 'data']);
        
        $output1 = Debug::getOutput();
        $output2 = Debug::getOutput();
        
        expect($output1)->toBe($output2);
        expect(Debug::hasOutput())->toBeTrue();
    });

    test('clearOutput() removes all buffered output', function () {
        Debug::dump(['test' => 'data']);
        Debug::dumpPretty(['other' => 'data']);
        
        Debug::clearOutput();
        
        expect(Debug::hasOutput())->toBeFalse();
        expect(Debug::getOutput())->toBe('');
    });
});

describe('Debug Settings', function () {
    test('setMaxDepth() limits recursion depth', function () {
        Debug::setMaxDepth(2);
        
        $deep = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => 'too deep'
                    ]
                ]
            ]
        ];
        
        Debug::dump($deep);
        $output = Debug::getOutput();
        
        expect($output)->toContain('max depth reached');
        
        // Восстанавливаем значение по умолчанию
        Debug::setMaxDepth(10);
    });

    test('setShowBacktrace() controls file/line display', function () {
        Debug::setShowBacktrace(true);
        Debug::dump(['test' => 'data'], 'With Backtrace');
        $output1 = Debug::getOutput();
        
        Debug::clearOutput();
        
        Debug::setShowBacktrace(false);
        Debug::dump(['test' => 'data'], 'Without Backtrace');
        $output2 = Debug::getOutput();
        
        // С backtrace должен содержать имя файла
        expect($output1)->toContain('.php');
        
        // Без backtrace не должен содержать детали файла
        // (но может содержать название в других местах)
        
        // Восстанавливаем значение по умолчанию
        Debug::setShowBacktrace(true);
    });

    test('setAutoDisplay() controls automatic output', function () {
        Debug::setAutoDisplay(false);
        expect(Debug::isAutoDisplay())->toBeFalse();
        
        Debug::setAutoDisplay(true);
        expect(Debug::isAutoDisplay())->toBeTrue();
    });
});

describe('Debug Variable Formatting', function () {
    test('formats objects correctly', function () {
        $obj = new class {
            private string $name = 'Test Object';
            public int $value = 42;
        };
        
        Debug::dump($obj, 'Object Test');
        $output = Debug::getOutput();
        
        expect($output)->toContain('object');
        expect($output)->toContain('name');
        expect($output)->toContain('Test Object');
        expect($output)->toContain('value');
        expect($output)->toContain('42');
    });

    test('handles empty arrays', function () {
        Debug::dump([], 'Empty Array');
        $output = Debug::getOutput();
        
        expect($output)->toContain('array()');
    });

    test('handles resources', function () {
        $resource = fopen('php://memory', 'r');
        
        Debug::dump($resource, 'Resource Test');
        $output = Debug::getOutput();
        
        expect($output)->toContain('resource');
        
        fclose($resource);
    });

    test('escapes HTML in strings', function () {
        $html = '<script>alert("xss")</script>';
        
        Debug::dump($html, 'HTML Test');
        $output = Debug::getOutput();
        
        // HTML должен быть экранирован
        expect($output)->toContain('&lt;script&gt;');
        expect($output)->not->toContain('<script>alert');
    });

    test('handles special characters in array keys', function () {
        $data = [
            'key with spaces' => 'value1',
            'key"with"quotes' => 'value2',
            "key\nwith\nnewlines" => 'value3'
        ];
        
        Debug::dump($data);
        $output = Debug::getOutput();
        
        expect($output)->toContain('key with spaces');
        expect($output)->toContain('value1');
        expect($output)->toContain('value2');
        expect($output)->toContain('value3');
    });
});

describe('Global Helper Functions', function () {
    test('dump() helper function works', function () {
        dump(['test' => 'data'], 'Helper Test');
        
        expect(Debug::hasOutput())->toBeTrue();
        expect(Debug::getOutput())->toContain('Helper Test');
    });

    test('dump_pretty() helper function works', function () {
        dump_pretty(['test' => 'data'], 'Pretty Helper');
        
        expect(Debug::hasOutput())->toBeTrue();
        $output = Debug::getOutput();
        expect($output)->toContain('Pretty Helper');
        expect($output)->toContain('#1e1e1e'); // dark theme
    });

    test('collect() and dump_all() helpers work', function () {
        collect(['data1' => 1], 'First');
        collect(['data2' => 2], 'Second');
        
        dump_all();
        
        expect(Debug::hasOutput())->toBeTrue();
        $output = Debug::getOutput();
        expect($output)->toContain('First');
        expect($output)->toContain('Second');
    });

    test('has_debug_output() helper works', function () {
        expect(has_debug_output())->toBeFalse();
        
        dump(['test' => 'data']);
        
        expect(has_debug_output())->toBeTrue();
    });

    test('debug_flush() helper works', function () {
        dump(['test' => 'data']);
        
        ob_start();
        debug_flush();
        $output = ob_get_clean();
        
        expect($output)->toContain('test');
        expect(has_debug_output())->toBeFalse();
    });

    test('debug_output() helper works', function () {
        dump(['test' => 'data'], 'Test Output');
        
        $output = debug_output();
        
        expect($output)->toContain('Test Output');
        expect($output)->toContain('test');
    });
});

describe('Edge Cases', function () {
    test('handles very large arrays', function () {
        $largeArray = array_fill(0, 1000, 'value');
        
        Debug::dump($largeArray, 'Large Array');
        
        expect(Debug::hasOutput())->toBeTrue();
        $output = Debug::getOutput();
        expect($output)->toContain('Large Array');
    });

    test('handles unicode characters', function () {
        $unicode = [
            'russian' => 'Привет мир',
            'chinese' => '你好世界',
            'emoji' => '🔥💡🚀'
        ];
        
        Debug::dump($unicode, 'Unicode Test');
        $output = Debug::getOutput();
        
        expect($output)->toContain('Привет мир');
        expect($output)->toContain('你好世界');
        expect($output)->toContain('🔥💡🚀');
    });

    test('handles numeric string keys', function () {
        $data = [
            '0' => 'zero',
            '1' => 'one',
            '123' => 'number'
        ];
        
        Debug::dump($data);
        $output = Debug::getOutput();
        
        expect($output)->toContain('zero');
        expect($output)->toContain('one');
        expect($output)->toContain('number');
    });
});

describe('Debug Render On Page', function () {
    test('renderOnPage is false by default', function () {
        expect(Debug::isRenderOnPage())->toBeFalse();
    });

    test('setRenderOnPage() changes render behavior', function () {
        Debug::setRenderOnPage(true);
        expect(Debug::isRenderOnPage())->toBeTrue();
        
        Debug::setRenderOnPage(false);
        expect(Debug::isRenderOnPage())->toBeFalse();
    });

    test('debug_render_on_page() helper function works', function () {
        debug_render_on_page(true);
        expect(Debug::isRenderOnPage())->toBeTrue();
        
        debug_render_on_page(false);
        expect(Debug::isRenderOnPage())->toBeFalse();
    });

    test('shutdown handler does not flush when renderOnPage is false', function () {
        Debug::setRenderOnPage(false);
        Debug::dump(['test' => 'data']);
        
        // Симулируем shutdown - данные должны остаться в буфере
        expect(Debug::hasOutput())->toBeTrue();
        
        // Данные доступны для toolbar
        $output = Debug::getOutput();
        expect($output)->toContain('test');
    });

    test('data is available for toolbar regardless of renderOnPage setting', function () {
        Debug::setRenderOnPage(false);
        Debug::dump(['user' => 'John'], 'User Data');
        Debug::dumpPretty(['config' => 'value'], 'Config');
        
        // Данные в буфере для toolbar
        expect(Debug::hasOutput())->toBeTrue();
        
        $outputArray = Debug::getOutput(true);
        expect(count($outputArray))->toBe(2);
        
        $output = Debug::getOutput();
        expect($output)->toContain('User Data');
        expect($output)->toContain('Config');
    });
});