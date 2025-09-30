<?php declare(strict_types=1);

use Core\Config;

beforeEach(function (): void {
    Config::clear();
});

// === Edge Cases Tests ===

it('handles empty string key gracefully', function (): void {
    expect(fn() => Config::set('', 'value'))
        ->not->toThrow(Exception::class);

    // Should still be able to get/set with empty key
    Config::set('', 'empty-key-value');
    expect(Config::get('', 'default'))->toBe('empty-key-value');
});

it('handles keys with multiple consecutive dots', function (): void {
    Config::set('a..b', 'value');
    expect(Config::get('a..b'))->toBe('value');
});

it('handles keys starting with dot', function (): void {
    Config::set('.start', 'value');
    expect(Config::get('.start'))->toBe('value');
});

it('handles keys ending with dot', function (): void {
    Config::set('end.', 'value');
    expect(Config::get('end.'))->toBe('value');
});

it('handles very long keys', function (): void {
    $longKey = str_repeat('a.', 100) . 'b';
    Config::set($longKey, 'deep-value');
    expect(Config::get($longKey))->toBe('deep-value');
});

it('handles special characters in keys', function (): void {
    Config::set('key-with-dash', 'value1');
    Config::set('key_with_underscore', 'value2');
    Config::set('key@special', 'value3');

    expect(Config::get('key-with-dash'))->toBe('value1');
    expect(Config::get('key_with_underscore'))->toBe('value2');
    expect(Config::get('key@special'))->toBe('value3');
});

it('handles unicode keys', function (): void {
    Config::set('ключ.配置.キー', 'unicode-value');
    expect(Config::get('ключ.配置.キー'))->toBe('unicode-value');
});

it('handles null values correctly', function (): void {
    Config::set('nullable', null);
    expect(Config::has('nullable'))->toBeTrue();
    expect(Config::get('nullable'))->toBeNull();
    expect(Config::get('nullable', 'default'))->toBeNull();
});

it('handles false values correctly', function (): void {
    Config::set('falsy', false);
    expect(Config::has('falsy'))->toBeTrue();
    expect(Config::get('falsy'))->toBeFalse();
    expect(Config::get('falsy', true))->toBeFalse();
});

it('handles zero values correctly', function (): void {
    Config::set('zero', 0);
    expect(Config::has('zero'))->toBeTrue();
    expect(Config::get('zero'))->toBe(0);
    expect(Config::get('zero', 999))->toBe(0);
});

it('handles empty string values correctly', function (): void {
    Config::set('empty_string', '');
    expect(Config::has('empty_string'))->toBeTrue();
    expect(Config::get('empty_string'))->toBe('');
    expect(Config::get('empty_string', 'default'))->toBe('');
});

it('handles empty array values correctly', function (): void {
    Config::set('empty_array', []);
    expect(Config::has('empty_array'))->toBeTrue();
    expect(Config::get('empty_array'))->toBe([]);
});

it('distinguishes between non-existent and null values', function (): void {
    Config::set('exists_but_null', null);

    expect(Config::has('exists_but_null'))->toBeTrue();
    expect(Config::has('does_not_exist'))->toBeFalse();

    expect(Config::get('exists_but_null'))->toBeNull();
    expect(Config::get('does_not_exist'))->toBeNull();
});

it('handles overwriting array with scalar', function (): void {
    Config::set('config', ['nested' => ['value' => 123]]);
    expect(Config::get('config.nested.value'))->toBe(123);

    // Overwrite the whole nested structure with a scalar
    Config::set('config.nested', 'now-scalar');
    expect(Config::get('config.nested'))->toBe('now-scalar');
    expect(Config::get('config.nested.value'))->toBeNull();
});

it('handles overwriting scalar with array', function (): void {
    Config::set('config.item', 'scalar');
    expect(Config::get('config.item'))->toBe('scalar');

    // Overwrite scalar with array
    Config::set('config.item', ['key' => 'value']);
    expect(Config::get('config.item'))->toBe(['key' => 'value']);
    expect(Config::get('config.item.key'))->toBe('value');
});

it('handles numeric string keys', function (): void {
    Config::set('array.123', 'numeric-key');
    Config::set('array.456.nested', 'deep-numeric');

    expect(Config::get('array.123'))->toBe('numeric-key');
    expect(Config::get('array.456.nested'))->toBe('deep-numeric');
});

it('handles mixed array types', function (): void {
    Config::set('mixed', [
        'string' => 'value',
        'number' => 123,
        'float' => 45.67,
        'bool' => true,
        'null' => null,
        'array' => [1, 2, 3],
        'nested' => ['deep' => 'value'],
    ]);

    expect(Config::get('mixed.string'))->toBe('value');
    expect(Config::get('mixed.number'))->toBe(123);
    expect(Config::get('mixed.float'))->toBe(45.67);
    expect(Config::get('mixed.bool'))->toBeTrue();
    expect(Config::get('mixed.null'))->toBeNull();
    expect(Config::get('mixed.array'))->toBe([1, 2, 3]);
    expect(Config::get('mixed.nested.deep'))->toBe('value');
});

it('handles large configuration arrays', function (): void {
    $largeArray = [];
    for ($i = 0; $i < 1000; $i++) {
        $largeArray["key_$i"] = "value_$i";
    }

    Config::set('large', $largeArray);

    expect(Config::get('large.key_0'))->toBe('value_0');
    expect(Config::get('large.key_500'))->toBe('value_500');
    expect(Config::get('large.key_999'))->toBe('value_999');
});

it('handles deeply nested arrays', function (): void {
    $deep = ['value' => 'found'];
    for ($i = 0; $i < 50; $i++) {
        $deep = ['level' => $deep];
    }

    Config::set('deep', $deep);

    $key = 'deep' . str_repeat('.level', 50) . '.value';
    expect(Config::get($key))->toBe('found');
});

// === Concurrent Operations Tests ===

it('handles rapid sequential operations', function (): void {
    for ($i = 0; $i < 100; $i++) {
        Config::set("key_$i", "value_$i");
    }

    for ($i = 0; $i < 100; $i++) {
        expect(Config::get("key_$i"))->toBe("value_$i");
    }
});

it('handles interleaved set and get operations', function (): void {
    Config::set('a', 1);
    expect(Config::get('a'))->toBe(1);

    Config::set('b', 2);
    expect(Config::get('a'))->toBe(1);
    expect(Config::get('b'))->toBe(2);

    Config::set('a', 10);
    expect(Config::get('a'))->toBe(10);
    expect(Config::get('b'))->toBe(2);
});

// === Macro Edge Cases ===

it('handles macro that throws exception', function (): void {
    Config::macro('throwing', function () {
        throw new RuntimeException('Macro error');
    });

    expect(fn() => Config::resolve('throwing'))
        ->toThrow(RuntimeException::class, 'Macro error');
});

it('handles macro that returns null', function (): void {
    Config::macro('nullable', fn() => null);
    expect(Config::resolve('nullable'))->toBeNull();
});

it('handles macro that returns another callable', function (): void {
    Config::macro('factory', fn() => fn() => 'nested');

    $result = Config::resolve('factory');
    expect($result)->toBeCallable();
    expect($result())->toBe('nested');
});

it('handles macro accessing non-existent config', function (): void {
    Config::macro('dependent', function () {
        return Config::get('nonexistent', 'fallback');
    });

    expect(Config::resolve('dependent'))->toBe('fallback');
});

it('handles multiple macros with same callable', function (): void {
    $counter = 0;
    $callable = function () use (&$counter) {
        return ++$counter;
    };

    Config::macro('macro1', $callable);
    Config::macro('macro2', $callable);

    expect(Config::resolve('macro1'))->toBe(1);
    expect(Config::resolve('macro2'))->toBe(2);
});

// === Cache Edge Cases ===

it('handles caching empty configuration', function (): void {
    $cachePath = sys_get_temp_dir() . '/config_cache_' . uniqid() . '.php';

    try {
        Config::clear();
        expect(Config::cache($cachePath))->toBeTrue();

        Config::clear();
        expect(Config::loadCached($cachePath))->toBeTrue();
        expect(Config::all())->toBe([]);
    } finally {
        if (file_exists($cachePath)) {
            @unlink($cachePath);
        }
    }
});

it('handles cache with special characters in values', function (): void {
    $cachePath = sys_get_temp_dir() . '/config_cache_' . uniqid() . '.php';

    try {
        Config::set('special', [
            'quotes' => "It's \"quoted\"",
            'newlines' => "Line1\nLine2",
            'tabs' => "Tab\there",
            'unicode' => 'Привет 世界 🚀',
        ]);

        Config::cache($cachePath);
        Config::clear();
        Config::loadCached($cachePath);

        expect(Config::get('special.quotes'))->toBe("It's \"quoted\"");
        expect(Config::get('special.newlines'))->toBe("Line1\nLine2");
        expect(Config::get('special.tabs'))->toBe("Tab\there");
        expect(Config::get('special.unicode'))->toBe('Привет 世界 🚀');
    } finally {
        if (file_exists($cachePath)) {
            @unlink($cachePath);
        }
    }
});

// === Merge Edge Cases ===

it('merges nested arrays with different structures', function (): void {
    $dir1 = createTempConfigDir([
        'app.php' => [
            'features' => ['a', 'b'],
            'config' => ['x' => 1],
        ],
    ]);
    $dir2 = createTempConfigDir([
        'app.php' => [
            'features' => ['c'],
            'config' => ['y' => 2],
            'new' => 'value',
        ],
    ]);

    try {
        Config::loadFile($dir1 . DIRECTORY_SEPARATOR . 'app.php');
        Config::loadFile($dir2 . DIRECTORY_SEPARATOR . 'app.php');

        expect(Config::get('app.features'))->toBe(['a', 'b', 'c']);
        expect(Config::get('app.config'))->toBe(['x' => 1, 'y' => 2]);
        expect(Config::get('app.new'))->toBe('value');
    } finally {
        deleteDir($dir1);
        deleteDir($dir2);
    }
});

it('handles associative array keys that look numeric', function (): void {
    $dir = createTempConfigDir([
        'app.php' => [
            '0' => 'zero',
            '1' => 'one',
            'key' => 'value',
        ],
    ]);

    try {
        Config::load($dir);

        expect(Config::get('app.0'))->toBe('zero');
        expect(Config::get('app.1'))->toBe('one');
        expect(Config::get('app.key'))->toBe('value');
    } finally {
        deleteDir($dir);
    }
});

// === File Loading Edge Cases ===

it('handles files with side effects', function (): void {
    $dir = createTempConfigDir([]);
    $file = $dir . DIRECTORY_SEPARATOR . 'sideeffect.php';

    // Create file that has side effects but returns array
    file_put_contents($file, '<?php
        // This should not cause issues
        $someVar = "test";
        define("TEST_CONSTANT", "value");

        return ["key" => "value"];
    ');

    try {
        Config::loadFile($file);
        expect(Config::get('sideeffect.key'))->toBe('value');
        expect(defined('TEST_CONSTANT'))->toBeTrue();
    } finally {
        deleteDir($dir);
    }
});

it('handles multiple loads of same file with loadFile()', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['items' => ['A']],
    ]);

    try {
        $file = $dir . DIRECTORY_SEPARATOR . 'app.php';
        Config::loadFile($file);
        Config::loadFile($file); // Load again

        // Should merge, resulting in ['A', 'A']
        expect(Config::get('app.items'))->toBe(['A', 'A']);
    } finally {
        deleteDir($dir);
    }
});

// === Lock Edge Cases ===

it('allows lock and unlock multiple times', function (): void {
    Config::set('key', 'value');

    Config::lock();
    Config::unlock();
    Config::lock();
    Config::unlock();

    Config::set('key', 'new-value');
    expect(Config::get('key'))->toBe('new-value');
});

it('handles operations on locked config with different error messages', function (): void {
    Config::lock();

    expect(fn() => Config::set('key', 'value'))
        ->toThrow(RuntimeException::class, 'locked');

    expect(fn() => Config::forget('key'))
        ->toThrow(RuntimeException::class, 'locked');

    expect(fn() => Config::push('arr', 'val'))
        ->toThrow(RuntimeException::class, 'locked');
});

// === Environment Loading Edge Cases ===

it('handles environment name with special characters', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['name' => 'Base'],
    ]);

    try {
        // Should not crash with unusual environment names
        Config::load($dir, 'prod-us-west-2');
        expect(Config::get('app.name'))->toBe('Base');
    } finally {
        deleteDir($dir);
    }
});

// === Performance Edge Cases ===

it('handles many unique keys efficiently', function (): void {
    $start = microtime(true);

    for ($i = 0; $i < 1000; $i++) {
        Config::set("perf.key_$i", "value_$i");
    }

    $setTime = microtime(true) - $start;

    $start = microtime(true);

    for ($i = 0; $i < 1000; $i++) {
        Config::get("perf.key_$i");
    }

    $getTime = microtime(true) - $start;

    // Should complete in reasonable time (< 100ms each)
    expect($setTime)->toBeLessThan(0.1);
    expect($getTime)->toBeLessThan(0.1);
});

it('handles has() checks efficiently on large config', function (): void {
    for ($i = 0; $i < 100; $i++) {
        Config::set("large.section_$i.key", "value");
    }

    $start = microtime(true);

    for ($i = 0; $i < 100; $i++) {
        Config::has("large.section_$i.key");
    }

    $time = microtime(true) - $start;

    expect($time)->toBeLessThan(0.05); // Should be very fast
});
