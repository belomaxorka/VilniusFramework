<?php declare(strict_types=1);

use Core\Env;

beforeEach(function () {
    // Очищаем кеш перед каждым тестом
    Env::clearCache();
    
    // Очищаем переменные окружения
    unset($_ENV['TEST_VAR']);
    unset($_SERVER['TEST_VAR']);
    putenv('TEST_VAR');
});

describe('Env::get()', function () {
    test('returns default value when variable does not exist', function () {
        expect(Env::get('NON_EXISTENT_VAR', 'default'))->toBe('default');
        
        // Очищаем кеш между вызовами с разными значениями по умолчанию
        Env::clearCache();
        expect(Env::get('NON_EXISTENT_VAR', 123))->toBe(123);
        
        Env::clearCache();
        expect(Env::get('NON_EXISTENT_VAR', null))->toBeNull();
    });

    test('returns cached value on subsequent calls', function () {
        Env::set('TEST_VAR', 'test_value');
        
        $first = Env::get('TEST_VAR');
        $second = Env::get('TEST_VAR');
        
        expect($first)->toBe('test_value');
        expect($second)->toBe('test_value');
    });

    test('returns value from $_ENV', function () {
        $_ENV['TEST_VAR'] = 'env_value';
        
        expect(Env::get('TEST_VAR'))->toBe('env_value');
    });

    test('returns value from $_SERVER when not in $_ENV', function () {
        $_SERVER['TEST_VAR'] = 'server_value';
        
        expect(Env::get('TEST_VAR'))->toBe('server_value');
    });

    test('prioritizes $_ENV over $_SERVER', function () {
        $_ENV['TEST_VAR'] = 'env_value';
        $_SERVER['TEST_VAR'] = 'server_value';
        
        expect(Env::get('TEST_VAR'))->toBe('env_value');
    });
});

describe('Env::set()', function () {
    test('sets variable in all environments', function () {
        Env::set('TEST_VAR', 'test_value');
        
        expect($_ENV['TEST_VAR'])->toBe('test_value');
        expect($_SERVER['TEST_VAR'])->toBe('test_value');
        expect(getenv('TEST_VAR'))->toBe('test_value');
    });

    test('converts value to string', function () {
        Env::set('TEST_VAR', 123);
        
        expect($_ENV['TEST_VAR'])->toBe('123');
        expect($_SERVER['TEST_VAR'])->toBe('123');
    });

    test('updates cache', function () {
        Env::set('TEST_VAR', 'original');
        Env::set('TEST_VAR', 'updated');
        
        expect(Env::get('TEST_VAR'))->toBe('updated');
    });
});

describe('Env::has()', function () {
    test('returns true when variable exists in $_ENV', function () {
        $_ENV['TEST_VAR'] = 'value';
        
        expect(Env::has('TEST_VAR'))->toBeTrue();
    });

    test('returns true when variable exists in $_SERVER', function () {
        $_SERVER['TEST_VAR'] = 'value';
        
        expect(Env::has('TEST_VAR'))->toBeTrue();
    });

    test('returns false when variable does not exist', function () {
        expect(Env::has('NON_EXISTENT_VAR'))->toBeFalse();
    });
});

describe('Env::all()', function () {
    test('returns merged $_SERVER and $_ENV arrays', function () {
        $_ENV['ENV_VAR'] = 'env_value';
        $_SERVER['SERVER_VAR'] = 'server_value';
        
        $all = Env::all();
        
        expect($all)->toHaveKey('ENV_VAR');
        expect($all)->toHaveKey('SERVER_VAR');
        expect($all['ENV_VAR'])->toBe('env_value');
        expect($all['SERVER_VAR'])->toBe('server_value');
    });
});

describe('Env::load()', function () {
    test('returns false when file does not exist', function () {
        $result = Env::load('/non/existent/file.env');
        
        expect($result)->toBeFalse();
    });

    test('throws exception when file is required but does not exist', function () {
        expect(fn() => Env::load('/non/existent/file.env', true))
            ->toThrow(RuntimeException::class, 'Environment file not found');
    });

    test('loads variables from .env file', function () {
        $envFile = sys_get_temp_dir() . '/test.env';
        file_put_contents($envFile, "TEST_VAR=test_value\nANOTHER_VAR=another_value");
        
        $result = Env::load($envFile);
        
        expect($result)->toBeTrue();
        expect(Env::get('TEST_VAR'))->toBe('test_value');
        expect(Env::get('ANOTHER_VAR'))->toBe('another_value');
        
        unlink($envFile);
    });

    test('ignores comments in .env file', function () {
        $envFile = sys_get_temp_dir() . '/test.env';
        file_put_contents($envFile, "# This is a comment\nTEST_VAR=test_value\n# Another comment");
        
        Env::load($envFile);
        
        expect(Env::get('TEST_VAR'))->toBe('test_value');
        
        unlink($envFile);
    });

    test('removes quotes from values', function () {
        $envFile = sys_get_temp_dir() . '/test.env';
        file_put_contents($envFile, "QUOTED_VAR=\"quoted_value\"\nSINGLE_QUOTED='single_quoted'");
        
        Env::load($envFile);
        
        expect(Env::get('QUOTED_VAR'))->toBe('quoted_value');
        expect(Env::get('SINGLE_QUOTED'))->toBe('single_quoted');
        
        unlink($envFile);
    });

    test('does not override existing variables', function () {
        $_ENV['EXISTING_VAR'] = 'existing_value';
        
        $envFile = sys_get_temp_dir() . '/test.env';
        file_put_contents($envFile, 'EXISTING_VAR=new_value');
        
        Env::load($envFile);
        
        expect(Env::get('EXISTING_VAR'))->toBe('existing_value');
        
        unlink($envFile);
    });

    test('returns true when already loaded and no path provided', function () {
        $result = Env::load();
        
        expect($result)->toBeTrue();
    });
});

describe('Env::clearCache()', function () {
    test('clears internal cache', function () {
        Env::set('TEST_VAR', 'test_value');
        $cached = Env::get('TEST_VAR');
        
        Env::clearCache();
        
        // После очистки кеша, значение должно быть получено заново
        unset($_ENV['TEST_VAR']);
        unset($_SERVER['TEST_VAR']);
        putenv('TEST_VAR');
        
        expect(Env::get('TEST_VAR', 'default'))->toBe('default');
    });
});

describe('Env value parsing', function () {
    test('parses boolean true values', function () {
        $trueValues = ['true', 'TRUE', '1', 'yes', 'YES', 'on', 'ON'];
        
        foreach ($trueValues as $value) {
            Env::set('TEST_VAR', $value);
            expect(Env::get('TEST_VAR'))->toBeTrue();
        }
    });

    test('parses boolean false values', function () {
        $falseValues = ['false', 'FALSE', '0', 'no', 'NO', 'off', 'OFF', ''];
        
        foreach ($falseValues as $value) {
            Env::set('TEST_VAR', $value);
            expect(Env::get('TEST_VAR'))->toBeFalse();
        }
    });

    test('parses null values', function () {
        $nullValues = ['null', 'NULL', 'nil', 'NIL'];
        
        foreach ($nullValues as $value) {
            Env::set('TEST_VAR', $value);
            expect(Env::get('TEST_VAR'))->toBeNull();
        }
    });

    test('parses integer values', function () {
        Env::set('TEST_VAR', '123');
        expect(Env::get('TEST_VAR'))->toBe(123);
        
        Env::set('TEST_VAR', '-456');
        expect(Env::get('TEST_VAR'))->toBe(-456);
    });

    test('parses float values', function () {
        Env::set('TEST_VAR', '123.45');
        expect(Env::get('TEST_VAR'))->toBe(123.45);
        
        Env::set('TEST_VAR', '-67.89');
        expect(Env::get('TEST_VAR'))->toBe(-67.89);
    });

    test('parses JSON objects', function () {
        Env::set('TEST_VAR', '{"key": "value", "number": 123}');
        $result = Env::get('TEST_VAR');
        
        expect($result)->toBeArray();
        expect($result['key'])->toBe('value');
        expect($result['number'])->toBe(123);
    });

    test('parses JSON arrays', function () {
        Env::set('TEST_VAR', '[1, 2, 3, "test"]');
        $result = Env::get('TEST_VAR');
        
        expect($result)->toBeArray();
        expect($result)->toBe([1, 2, 3, 'test']);
    });

    test('returns string for invalid JSON', function () {
        Env::set('TEST_VAR', '{invalid json}');
        expect(Env::get('TEST_VAR'))->toBe('{invalid json}');
    });

    test('returns string for regular text', function () {
        Env::set('TEST_VAR', 'regular text');
        expect(Env::get('TEST_VAR'))->toBe('regular text');
    });

    test('trims whitespace from values', function () {
        Env::set('TEST_VAR', '  test value  ');
        expect(Env::get('TEST_VAR'))->toBe('test value');
    });
});

describe('Env quote removal', function () {
    test('removes double quotes', function () {
        $envFile = sys_get_temp_dir() . '/test.env';
        file_put_contents($envFile, 'QUOTED="value with spaces"');
        
        Env::load($envFile);
        
        expect(Env::get('QUOTED'))->toBe('value with spaces');
        
        unlink($envFile);
    });

    test('removes single quotes', function () {
        $envFile = sys_get_temp_dir() . '/test.env';
        file_put_contents($envFile, "QUOTED='value with spaces'");
        
        Env::load($envFile);
        
        expect(Env::get('QUOTED'))->toBe('value with spaces');
        
        unlink($envFile);
    });

    test('does not remove quotes if not properly paired', function () {
        $envFile = sys_get_temp_dir() . '/test.env';
        file_put_contents($envFile, 'MALFORMED="unclosed quote');
        
        Env::load($envFile);
        
        expect(Env::get('MALFORMED'))->toBe('"unclosed quote');
        
        unlink($envFile);
    });

    test('does not remove quotes in the middle of string', function () {
        $envFile = sys_get_temp_dir() . '/test.env';
        file_put_contents($envFile, 'MIXED=value"with"quotes');
        
        Env::load($envFile);
        
        expect(Env::get('MIXED'))->toBe('value"with"quotes');
        
        unlink($envFile);
    });
});

describe('Env edge cases', function () {
    test('handles empty .env file', function () {
        $envFile = sys_get_temp_dir() . '/empty.env';
        file_put_contents($envFile, '');
        
        $result = Env::load($envFile);
        
        expect($result)->toBeTrue();
        
        unlink($envFile);
    });

    test('handles .env file with only comments', function () {
        $envFile = sys_get_temp_dir() . '/comments.env';
        file_put_contents($envFile, "# Comment 1\n# Comment 2\n   # Comment with spaces");
        
        $result = Env::load($envFile);
        
        expect($result)->toBeTrue();
        
        unlink($envFile);
    });

    test('handles .env file with empty lines', function () {
        $envFile = sys_get_temp_dir() . '/empty_lines.env';
        file_put_contents($envFile, "VAR1=value1\n\n\nVAR2=value2");
        
        Env::load($envFile);
        
        expect(Env::get('VAR1'))->toBe('value1');
        expect(Env::get('VAR2'))->toBe('value2');
        
        unlink($envFile);
    });

    test('handles variables with empty values', function () {
        $envFile = sys_get_temp_dir() . '/empty_values.env';
        file_put_contents($envFile, "EMPTY_VAR=\nSPACES_VAR=   ");
        
        Env::load($envFile);
        
        expect(Env::get('EMPTY_VAR'))->toBe('');
        expect(Env::get('SPACES_VAR'))->toBe('');
        
        unlink($envFile);
    });

    test('handles variables with equals sign in value', function () {
        $envFile = sys_get_temp_dir() . '/equals.env';
        file_put_contents($envFile, 'URL=https://example.com?param=value');
        
        Env::load($envFile);
        
        expect(Env::get('URL'))->toBe('https://example.com?param=value');
        
        unlink($envFile);
    });
});
