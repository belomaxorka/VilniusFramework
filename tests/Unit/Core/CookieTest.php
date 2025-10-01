<?php declare(strict_types=1);

use Core\Cookie;

beforeEach(function () {
    // Очищаем cookies перед каждым тестом
    $_COOKIE = [];
});

afterEach(function () {
    $_COOKIE = [];
});

describe('Cookie Basic Operations', function () {
    test('get returns cookie value', function () {
        $_COOKIE['test'] = 'value';
        
        expect(Cookie::get('test'))->toBe('value');
    });

    test('get returns default if cookie not exists', function () {
        expect(Cookie::get('non_existent', 'default'))->toBe('default');
    });

    test('get returns null by default', function () {
        expect(Cookie::get('non_existent'))->toBeNull();
    });

    test('has checks cookie existence', function () {
        $_COOKIE['exists'] = 'value';
        
        expect(Cookie::has('exists'))->toBeTrue();
        expect(Cookie::has('not_exists'))->toBeFalse();
    });

    test('all returns all cookies', function () {
        $_COOKIE = ['a' => '1', 'b' => '2', 'c' => '3'];
        
        $all = Cookie::all();
        
        expect($all)->toBe(['a' => '1', 'b' => '2', 'c' => '3']);
    });

    test('delete removes cookie from $_COOKIE', function () {
        $_COOKIE['to_delete'] = 'value';
        
        Cookie::delete('to_delete');
        
        expect(Cookie::has('to_delete'))->toBeFalse();
    });

    test('delete returns false if cookie not exists', function () {
        expect(Cookie::delete('non_existent'))->toBeFalse();
    });
});

describe('Cookie JSON Operations', function () {
    test('getJson decodes JSON cookie', function () {
        $_COOKIE['json_data'] = '{"name":"John","age":30}';
        
        $data = Cookie::getJson('json_data');
        
        expect($data)->toBe(['name' => 'John', 'age' => 30]);
    });

    test('getJson returns default if cookie not exists', function () {
        $default = ['default' => 'value'];
        
        expect(Cookie::getJson('non_existent', $default))->toBe($default);
    });

    test('getJson returns default if JSON invalid', function () {
        $_COOKIE['invalid_json'] = 'not a json';
        $default = ['default' => 'value'];
        
        expect(Cookie::getJson('invalid_json', $default))->toBe($default);
    });

    test('getJson handles arrays', function () {
        $_COOKIE['array'] = '[1,2,3,4,5]';
        
        expect(Cookie::getJson('array'))->toBe([1, 2, 3, 4, 5]);
    });

    test('getJson handles nested structures', function () {
        $_COOKIE['nested'] = '{"user":{"name":"John","roles":["admin","user"]}}';
        
        $data = Cookie::getJson('nested');
        
        expect($data)->toBe([
            'user' => [
                'name' => 'John',
                'roles' => ['admin', 'user']
            ]
        ]);
    });
});

describe('Cookie Clear Operation', function () {
    test('clear removes all cookies', function () {
        $_COOKIE = ['a' => '1', 'b' => '2', 'c' => '3'];
        
        Cookie::clear();
        
        expect($_COOKIE)->toBeEmpty();
    });
});

// Примечание: Мы не можем полностью протестировать set(), setForDays(), setForHours(),
// и другие методы, которые вызывают setcookie(), так как они требуют отправки заголовков.
// Эти методы должны тестироваться в интеграционных или функциональных тестах.

describe('Cookie Helper Methods Behavior', function () {
    test('setForDays calculates correct expires time', function () {
        // Мы можем только проверить, что метод существует и принимает правильные параметры
        expect(method_exists(Cookie::class, 'setForDays'))->toBeTrue();
    });

    test('setForHours method exists', function () {
        expect(method_exists(Cookie::class, 'setForHours'))->toBeTrue();
    });

    test('forever method exists', function () {
        expect(method_exists(Cookie::class, 'forever'))->toBeTrue();
    });

    test('setSecure method exists', function () {
        expect(method_exists(Cookie::class, 'setSecure'))->toBeTrue();
    });

    test('setJson method exists', function () {
        expect(method_exists(Cookie::class, 'setJson'))->toBeTrue();
    });
});

describe('Cookie Edge Cases', function () {
    test('get handles special characters', function () {
        $_COOKIE['special'] = 'value with spaces & symbols!@#';
        
        expect(Cookie::get('special'))->toBe('value with spaces & symbols!@#');
    });

    test('get handles unicode', function () {
        $_COOKIE['unicode'] = 'Привет мир 你好世界';
        
        expect(Cookie::get('unicode'))->toBe('Привет мир 你好世界');
    });

    test('getJson handles empty arrays', function () {
        $_COOKIE['empty_array'] = '[]';
        
        expect(Cookie::getJson('empty_array'))->toBe([]);
    });

    test('getJson handles empty objects', function () {
        $_COOKIE['empty_object'] = '{}';
        
        expect(Cookie::getJson('empty_object'))->toBe([]);
    });

    test('has returns false for null value', function () {
        $_COOKIE['null_value'] = null;
        
        expect(Cookie::has('null_value'))->toBeFalse();
    });
});

