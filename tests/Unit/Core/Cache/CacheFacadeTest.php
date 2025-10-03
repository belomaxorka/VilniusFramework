<?php declare(strict_types=1);

use Core\Cache;
use Core\Cache\CacheManager;

describe('Cache Facade', function () {
    beforeEach(function () {
        // Reset the Cache facade
        Cache::setManager(new CacheManager([
            'default' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array',
                    'prefix' => 'test_',
                ],
            ],
        ]));
    });

    afterEach(function () {
        Cache::clear();
    });

    test('get and set values', function () {
        expect(Cache::set('key', 'value'))->toBeTrue();
        expect(Cache::get('key'))->toBe('value');
    });

    test('delete values', function () {
        Cache::set('key', 'value');
        expect(Cache::delete('key'))->toBeTrue();
        expect(Cache::get('key'))->toBeNull();
    });

    test('has checks existence', function () {
        Cache::set('key', 'value');
        expect(Cache::has('key'))->toBeTrue();
        expect(Cache::has('nonexistent'))->toBeFalse();
    });

    test('increment and decrement', function () {
        expect(Cache::increment('counter', 5))->toBe(5);
        expect(Cache::increment('counter', 3))->toBe(8);
        expect(Cache::decrement('counter', 2))->toBe(6);
    });

    test('remember caches callback result', function () {
        $called = 0;
        $callback = function () use (&$called) {
            $called++;
            return 'computed';
        };

        $result1 = Cache::remember('key', 3600, $callback);
        $result2 = Cache::remember('key', 3600, $callback);

        expect($result1)->toBe('computed');
        expect($result2)->toBe('computed');
        expect($called)->toBe(1); // Callback called only once
    });

    test('pull gets and deletes', function () {
        Cache::set('key', 'value');
        expect(Cache::pull('key'))->toBe('value');
        expect(Cache::has('key'))->toBeFalse();
    });

    test('add only adds if not exists', function () {
        expect(Cache::add('key', 'value1'))->toBeTrue();
        expect(Cache::add('key', 'value2'))->toBeFalse();
        expect(Cache::get('key'))->toBe('value1');
    });

    test('forever stores permanently', function () {
        expect(Cache::forever('key', 'value'))->toBeTrue();
        expect(Cache::get('key'))->toBe('value');
    });

    test('rememberForever caches forever', function () {
        $called = 0;
        $callback = function () use (&$called) {
            $called++;
            return 'forever';
        };

        $result1 = Cache::rememberForever('key', $callback);
        $result2 = Cache::rememberForever('key', $callback);

        expect($result1)->toBe('forever');
        expect($result2)->toBe('forever');
        expect($called)->toBe(1);
    });

    test('multiple operations', function () {
        $values = ['key1' => 'value1', 'key2' => 'value2'];
        
        expect(Cache::setMultiple($values))->toBeTrue();
        
        $results = Cache::getMultiple(['key1', 'key2']);
        expect($results)->toHaveKey('key1');
        expect($results)->toHaveKey('key2');
        
        expect(Cache::deleteMultiple(['key1', 'key2']))->toBeTrue();
    });

    test('driver returns specific driver', function () {
        $driver = Cache::driver('array');
        expect($driver)->toBeInstanceOf(\Core\Cache\CacheDriverInterface::class);
    });
});

