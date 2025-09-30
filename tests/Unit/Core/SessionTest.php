<?php declare(strict_types=1);

use Core\Session;

beforeEach(function () {
    // Мокаем сессию для тестирования
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    $_SESSION = [];
});

afterEach(function () {
    $_SESSION = [];
});

describe('Session Basic Operations', function () {
    test('get returns session value', function () {
        $_SESSION['test'] = 'value';
        
        expect(Session::get('test'))->toBe('value');
    });

    test('get returns default if key not exists', function () {
        expect(Session::get('non_existent', 'default'))->toBe('default');
    });

    test('get returns null by default', function () {
        expect(Session::get('non_existent'))->toBeNull();
    });

    test('set stores value in session', function () {
        Session::set('key', 'value');
        
        expect($_SESSION['key'])->toBe('value');
    });

    test('has checks key existence', function () {
        $_SESSION['exists'] = 'value';
        
        expect(Session::has('exists'))->toBeTrue();
        expect(Session::has('not_exists'))->toBeFalse();
    });

    test('delete removes key from session', function () {
        $_SESSION['to_delete'] = 'value';
        
        Session::delete('to_delete');
        
        expect(Session::has('to_delete'))->toBeFalse();
    });

    test('all returns all session data', function () {
        $_SESSION = ['a' => '1', 'b' => '2', 'c' => '3'];
        
        $all = Session::all();
        
        expect($all)->toBe(['a' => '1', 'b' => '2', 'c' => '3']);
    });

    test('clear removes all session data', function () {
        $_SESSION = ['a' => '1', 'b' => '2', 'c' => '3'];
        
        Session::clear();
        
        expect($_SESSION)->toBeEmpty();
    });
});

describe('Session Flash Messages', function () {
    test('flash sets message', function () {
        Session::flash('success', 'Message sent!');
        
        expect($_SESSION['_flash.success'])->toBe('Message sent!');
    });

    test('getFlash returns and removes message', function () {
        Session::flash('success', 'Hello');
        
        $message = Session::getFlash('success');
        
        expect($message)->toBe('Hello');
        expect(Session::has('_flash.success'))->toBeFalse();
    });

    test('getFlash returns default if not exists', function () {
        expect(Session::getFlash('non_existent', 'default'))->toBe('default');
    });

    test('hasFlash checks flash existence', function () {
        Session::flash('info', 'Info message');
        
        expect(Session::hasFlash('info'))->toBeTrue();
        expect(Session::hasFlash('not_exists'))->toBeFalse();
    });

    test('getAllFlash returns and removes all flash messages', function () {
        Session::flash('success', 'Success message');
        Session::flash('error', 'Error message');
        Session::set('regular', 'Regular value');
        
        $flash = Session::getAllFlash();
        
        expect($flash)->toBe([
            'success' => 'Success message',
            'error' => 'Error message'
        ]);
        expect(Session::has('_flash.success'))->toBeFalse();
        expect(Session::has('_flash.error'))->toBeFalse();
        expect(Session::has('regular'))->toBeTrue();
    });
});

describe('Session CSRF Token', function () {
    test('generateCsrfToken creates token', function () {
        $token = Session::generateCsrfToken();
        
        expect($token)->toBeString();
        expect(strlen($token))->toBe(64); // 32 bytes in hex = 64 characters
    });

    test('generateCsrfToken returns same token on subsequent calls', function () {
        $token1 = Session::generateCsrfToken();
        $token2 = Session::generateCsrfToken();
        
        expect($token1)->toBe($token2);
    });

    test('getCsrfToken returns existing token', function () {
        $generated = Session::generateCsrfToken();
        $retrieved = Session::getCsrfToken();
        
        expect($retrieved)->toBe($generated);
    });

    test('getCsrfToken returns null if not generated', function () {
        expect(Session::getCsrfToken())->toBeNull();
    });

    test('verifyCsrfToken validates correct token', function () {
        $token = Session::generateCsrfToken();
        
        expect(Session::verifyCsrfToken($token))->toBeTrue();
    });

    test('verifyCsrfToken rejects incorrect token', function () {
        Session::generateCsrfToken();
        
        expect(Session::verifyCsrfToken('wrong_token'))->toBeFalse();
    });

    test('verifyCsrfToken returns false if no token set', function () {
        expect(Session::verifyCsrfToken('any_token'))->toBeFalse();
    });
});

describe('Session Previous URL', function () {
    test('setPreviousUrl stores URL', function () {
        Session::setPreviousUrl('/profile');
        
        expect($_SESSION['_previous_url'])->toBe('/profile');
    });

    test('getPreviousUrl returns stored URL', function () {
        Session::setPreviousUrl('/users/list');
        
        expect(Session::getPreviousUrl())->toBe('/users/list');
    });

    test('getPreviousUrl returns default if not set', function () {
        expect(Session::getPreviousUrl('/home'))->toBe('/home');
    });
});

describe('Session Pull Operation', function () {
    test('pull returns and removes value', function () {
        Session::set('temp', 'value');
        
        $value = Session::pull('temp');
        
        expect($value)->toBe('value');
        expect(Session::has('temp'))->toBeFalse();
    });

    test('pull returns default if key not exists', function () {
        expect(Session::pull('non_existent', 'default'))->toBe('default');
    });
});

describe('Session Push Operation', function () {
    test('push adds value to new array', function () {
        Session::push('items', 'first');
        
        expect(Session::get('items'))->toBe(['first']);
    });

    test('push adds value to existing array', function () {
        Session::set('items', ['first']);
        Session::push('items', 'second');
        Session::push('items', 'third');
        
        expect(Session::get('items'))->toBe(['first', 'second', 'third']);
    });

    test('push converts non-array to array', function () {
        Session::set('value', 'single');
        Session::push('value', 'another');
        
        expect(Session::get('value'))->toBe(['single', 'another']);
    });
});

describe('Session Increment/Decrement', function () {
    test('increment increases value', function () {
        $result = Session::increment('counter');
        
        expect($result)->toBe(1);
        expect(Session::get('counter'))->toBe(1);
    });

    test('increment with amount', function () {
        Session::increment('counter', 5);
        Session::increment('counter', 3);
        
        expect(Session::get('counter'))->toBe(8);
    });

    test('increment starts from existing value', function () {
        Session::set('counter', 10);
        Session::increment('counter');
        
        expect(Session::get('counter'))->toBe(11);
    });

    test('decrement decreases value', function () {
        Session::set('counter', 10);
        $result = Session::decrement('counter');
        
        expect($result)->toBe(9);
        expect(Session::get('counter'))->toBe(9);
    });

    test('decrement with amount', function () {
        Session::set('counter', 20);
        Session::decrement('counter', 5);
        
        expect(Session::get('counter'))->toBe(15);
    });

    test('decrement can go negative', function () {
        Session::decrement('counter', 5);
        
        expect(Session::get('counter'))->toBe(-5);
    });
});

describe('Session Remember Operation', function () {
    test('remember executes callback if key not exists', function () {
        $executed = false;
        
        Session::remember('key', function() use (&$executed) {
            $executed = true;
            return 'computed_value';
        });
        
        expect($executed)->toBeTrue();
        expect(Session::get('key'))->toBe('computed_value');
    });

    test('remember returns existing value without callback', function () {
        Session::set('key', 'existing');
        $executed = false;
        
        $value = Session::remember('key', function() use (&$executed) {
            $executed = true;
            return 'new_value';
        });
        
        expect($executed)->toBeFalse();
        expect($value)->toBe('existing');
    });

    test('remember stores callback result', function () {
        $value = Session::remember('computed', fn() => 42);
        
        expect($value)->toBe(42);
        expect(Session::get('computed'))->toBe(42);
    });
});

describe('Session Edge Cases', function () {
    test('handles null values', function () {
        Session::set('null_value', null);
        
        expect(Session::get('null_value'))->toBeNull();
        expect(Session::has('null_value'))->toBeTrue();
    });

    test('handles boolean values', function () {
        Session::set('bool_true', true);
        Session::set('bool_false', false);
        
        expect(Session::get('bool_true'))->toBeTrue();
        expect(Session::get('bool_false'))->toBeFalse();
    });

    test('handles arrays', function () {
        $array = ['a' => 1, 'b' => 2, 'nested' => ['c' => 3]];
        Session::set('array', $array);
        
        expect(Session::get('array'))->toBe($array);
    });

    test('handles objects', function () {
        $obj = new stdClass();
        $obj->name = 'Test';
        $obj->value = 123;
        
        Session::set('object', $obj);
        
        $retrieved = Session::get('object');
        expect($retrieved)->toBeInstanceOf(stdClass::class);
        expect($retrieved->name)->toBe('Test');
        expect($retrieved->value)->toBe(123);
    });

    test('handles special characters in keys', function () {
        Session::set('key-with-dashes', 'value1');
        Session::set('key_with_underscores', 'value2');
        Session::set('key.with.dots', 'value3');
        
        expect(Session::get('key-with-dashes'))->toBe('value1');
        expect(Session::get('key_with_underscores'))->toBe('value2');
        expect(Session::get('key.with.dots'))->toBe('value3');
    });
});

describe('Session Method Existence', function () {
    test('all required methods exist', function () {
        expect(method_exists(Session::class, 'start'))->toBeTrue();
        expect(method_exists(Session::class, 'isStarted'))->toBeTrue();
        expect(method_exists(Session::class, 'destroy'))->toBeTrue();
        expect(method_exists(Session::class, 'regenerate'))->toBeTrue();
        expect(method_exists(Session::class, 'id'))->toBeTrue();
        expect(method_exists(Session::class, 'setId'))->toBeTrue();
        expect(method_exists(Session::class, 'name'))->toBeTrue();
        expect(method_exists(Session::class, 'setName'))->toBeTrue();
        expect(method_exists(Session::class, 'save'))->toBeTrue();
        expect(method_exists(Session::class, 'getCookieParams'))->toBeTrue();
        expect(method_exists(Session::class, 'setCookieParams'))->toBeTrue();
    });
});

