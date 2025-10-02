<?php declare(strict_types=1);

use Core\Cache;
use Core\Cache\CacheManager;

describe('Cache Helpers', function () {
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
        cache_flush();
    });

    test('cache() without args returns manager', function () {
        $manager = cache();
        expect($manager)->toBeInstanceOf(CacheManager::class);
    });

    test('cache() with key gets value', function () {
        Cache::set('key', 'value');
        expect(cache('key'))->toBe('value');
        expect(cache('nonexistent', 'default'))->toBe('default');
    });

    test('cache_remember() caches callback result', function () {
        $called = 0;
        $callback = function () use (&$called) {
            $called++;
            return 'computed';
        };

        $result1 = cache_remember('key', 3600, $callback);
        $result2 = cache_remember('key', 3600, $callback);

        expect($result1)->toBe('computed');
        expect($result2)->toBe('computed');
        expect($called)->toBe(1);
    });

    test('cache_forget() deletes value', function () {
        Cache::set('key', 'value');
        expect(cache_forget('key'))->toBeTrue();
        expect(Cache::has('key'))->toBeFalse();
    });

    test('cache_flush() clears all', function () {
        Cache::set('key1', 'value1');
        Cache::set('key2', 'value2');
        expect(cache_flush())->toBeTrue();
        expect(Cache::has('key1'))->toBeFalse();
        expect(Cache::has('key2'))->toBeFalse();
    });

    test('cache_has() checks existence', function () {
        Cache::set('key', 'value');
        expect(cache_has('key'))->toBeTrue();
        expect(cache_has('nonexistent'))->toBeFalse();
    });

    test('cache_pull() gets and deletes', function () {
        Cache::set('key', 'value');
        expect(cache_pull('key'))->toBe('value');
        expect(cache_has('key'))->toBeFalse();
    });

    test('cache_forever() stores permanently', function () {
        expect(cache_forever('key', 'value'))->toBeTrue();
        expect(cache('key'))->toBe('value');
    });

    test('cache_increment() increases value', function () {
        expect(cache_increment('counter', 5))->toBe(5);
        expect(cache_increment('counter', 3))->toBe(8);
    });

    test('cache_decrement() decreases value', function () {
        Cache::set('counter', 10);
        expect(cache_decrement('counter', 3))->toBe(7);
        expect(cache('counter'))->toBe(7);
    });
});

