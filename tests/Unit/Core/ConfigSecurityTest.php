<?php declare(strict_types=1);

use Core\Config;

beforeEach(function (): void {
    Config::clear();
});

// === Path Traversal Security Tests ===

it('allows loading files from allowed base paths', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['name' => 'MyApp'],
    ]);

    try {
        Config::setAllowedBasePaths([$dir]);
        Config::load($dir);
        
        expect(Config::get('app.name'))->toBe('MyApp');
    } finally {
        deleteDir($dir);
    }
});

it('prevents loading files outside allowed base paths', function (): void {
    $allowedDir = createTempConfigDir([
        'safe.php' => ['safe' => true],
    ]);
    
    $forbiddenDir = createTempConfigDir([
        'unsafe.php' => ['unsafe' => true],
    ]);

    try {
        Config::setAllowedBasePaths([$allowedDir]);
        
        // Should work
        Config::load($allowedDir);
        expect(Config::get('safe.safe'))->toBeTrue();
        
        // Should throw
        expect(fn() => Config::load($forbiddenDir))
            ->toThrow(RuntimeException::class, 'Path traversal detected');
    } finally {
        deleteDir($allowedDir);
        deleteDir($forbiddenDir);
    }
});

it('prevents loading single file outside allowed base paths', function (): void {
    $allowedDir = createTempConfigDir([
        'safe.php' => ['safe' => true],
    ]);
    
    $forbiddenDir = createTempConfigDir([
        'unsafe.php' => ['unsafe' => true],
    ]);

    try {
        Config::setAllowedBasePaths([$allowedDir]);
        
        $forbiddenFile = $forbiddenDir . DIRECTORY_SEPARATOR . 'unsafe.php';
        
        expect(fn() => Config::loadFile($forbiddenFile))
            ->toThrow(RuntimeException::class, 'Path traversal');
    } finally {
        deleteDir($allowedDir);
        deleteDir($forbiddenDir);
    }
});

it('allows multiple allowed base paths', function (): void {
    $dir1 = createTempConfigDir([
        'app.php' => ['name' => 'App1'],
    ]);
    
    $dir2 = createTempConfigDir([
        'database.php' => ['host' => 'localhost'],
    ]);

    try {
        Config::setAllowedBasePaths([$dir1, $dir2]);
        
        Config::load($dir1);
        Config::load($dir2);
        
        expect(Config::get('app.name'))->toBe('App1');
        expect(Config::get('database.host'))->toBe('localhost');
    } finally {
        deleteDir($dir1);
        deleteDir($dir2);
    }
});

it('works without allowed base paths configured (backward compatible)', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['name' => 'MyApp'],
    ]);

    try {
        // No setAllowedBasePaths() call - should work normally
        Config::load($dir);
        expect(Config::get('app.name'))->toBe('MyApp');
    } finally {
        deleteDir($dir);
    }
});

it('throws when setting invalid base path', function (): void {
    expect(fn() => Config::setAllowedBasePaths(['/nonexistent/path']))
        ->toThrow(InvalidArgumentException::class, 'Invalid base path');
});

it('clears allowed base paths on clear()', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['name' => 'MyApp'],
    ]);

    try {
        Config::setAllowedBasePaths([$dir]);
        Config::clear();
        
        // After clear, base paths are reset, so should work
        Config::load($dir);
        expect(Config::get('app.name'))->toBe('MyApp');
    } finally {
        deleteDir($dir);
    }
});

// === getRequired() Tests ===

it('gets required value successfully', function (): void {
    Config::set('api.key', 'secret123');
    
    $value = Config::getRequired('api.key');
    expect($value)->toBe('secret123');
});

it('throws when required value is missing', function (): void {
    expect(fn() => Config::getRequired('missing.key'))
        ->toThrow(RuntimeException::class, 'Required configuration key missing');
});

it('throws even when default would normally be returned', function (): void {
    // Unlike get(), getRequired() should throw for missing keys
    expect(fn() => Config::getRequired('nonexistent.key'))
        ->toThrow(RuntimeException::class, 'missing');
});

it('returns null for existing key with null value', function (): void {
    Config::set('nullable', null);
    
    expect(Config::getRequired('nullable'))->toBeNull();
});

it('returns false for existing key with false value', function (): void {
    Config::set('falsy', false);
    
    expect(Config::getRequired('falsy'))->toBeFalse();
});

it('returns zero for existing key with zero value', function (): void {
    Config::set('zero', 0);
    
    expect(Config::getRequired('zero'))->toBe(0);
});

it('works with nested keys', function (): void {
    Config::set('database.connections.mysql.host', 'localhost');
    
    $value = Config::getRequired('database.connections.mysql.host');
    expect($value)->toBe('localhost');
});

// === getMany() Tests ===

it('gets multiple values at once', function (): void {
    Config::set('app.name', 'MyApp');
    Config::set('app.version', '1.0');
    Config::set('app.debug', true);
    
    $values = Config::getMany(['app.name', 'app.version', 'app.debug']);
    
    expect($values)->toBe([
        'app.name' => 'MyApp',
        'app.version' => '1.0',
        'app.debug' => true,
    ]);
});

it('returns defaults for missing keys', function (): void {
    Config::set('app.name', 'MyApp');
    
    $values = Config::getMany(['app.name', 'app.version', 'app.debug'], 'default');
    
    expect($values)->toBe([
        'app.name' => 'MyApp',
        'app.version' => 'default',
        'app.debug' => 'default',
    ]);
});

it('works with nested keys', function (): void {
    Config::set('database.host', 'localhost');
    Config::set('database.port', 3306);
    Config::set('cache.driver', 'redis');
    
    $values = Config::getMany([
        'database.host',
        'database.port',
        'cache.driver',
    ]);
    
    expect($values['database.host'])->toBe('localhost');
    expect($values['database.port'])->toBe(3306);
    expect($values['cache.driver'])->toBe('redis');
});

it('handles empty array', function (): void {
    $values = Config::getMany([]);
    expect($values)->toBe([]);
});

it('preserves key order', function (): void {
    Config::set('a', 1);
    Config::set('b', 2);
    Config::set('c', 3);
    
    $values = Config::getMany(['c', 'a', 'b']);
    
    expect(array_keys($values))->toBe(['c', 'a', 'b']);
});

// === Circular Macro Reference Tests ===

it('detects circular macro references', function (): void {
    Config::macro('circular', function () {
        return Config::resolve('circular');
    });
    
    expect(fn() => Config::resolve('circular'))
        ->toThrow(RuntimeException::class, 'Circular macro reference detected');
});

it('detects indirect circular macro references', function (): void {
    Config::macro('macro1', function () {
        return Config::resolve('macro2');
    });
    
    Config::macro('macro2', function () {
        return Config::resolve('macro1');
    });
    
    expect(fn() => Config::resolve('macro1'))
        ->toThrow(RuntimeException::class, 'Circular macro reference');
});

it('allows non-circular macro chains', function (): void {
    Config::set('base', 'value');
    
    Config::macro('level1', function () {
        return 'L1-' . Config::get('base');
    });
    
    Config::macro('level2', function () {
        return 'L2-' . Config::resolve('level1');
    });
    
    // Should work fine - no circular reference
    $result = Config::resolve('level2');
    expect($result)->toBe('L2-L1-value');
});

it('clears resolving macros after exception', function (): void {
    $called = false;
    
    Config::macro('failing', function () use (&$called) {
        if (!$called) {
            $called = true;
            throw new RuntimeException('First call fails');
        }
        return 'success';
    });
    
    // First call throws
    try {
        Config::resolve('failing');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('First call fails');
    }
    
    // Second call should work (not be blocked by circular detection)
    $result = Config::resolve('failing');
    expect($result)->toBe('success');
});

it('handles deep macro chains without false positives', function (): void {
    Config::set('base', 1);
    
    for ($i = 1; $i <= 10; $i++) {
        $prev = $i - 1;
        Config::macro("level$i", function () use ($i, $prev) {
            if ($prev === 0) {
                return Config::get('base') * $i;
            }
            return Config::resolve("level$prev") * $i;
        });
    }
    
    // Should calculate 1 * 1 * 2 * 3 * 4 * 5 * 6 * 7 * 8 * 9 * 10 = 3628800
    $result = Config::resolve('level10');
    expect($result)->toBe(3628800);
});

// === Combined Security Tests ===

it('enforces security and returns required values', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['api_key' => 'secret'],
    ]);

    try {
        Config::setAllowedBasePaths([$dir]);
        Config::load($dir);
        
        $key = Config::getRequired('app.api_key');
        expect($key)->toBe('secret');
    } finally {
        deleteDir($dir);
    }
});

it('works with getMany and security restrictions', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['name' => 'App', 'version' => '1.0'],
    ]);

    try {
        Config::setAllowedBasePaths([$dir]);
        Config::load($dir);
        
        $values = Config::getMany(['app.name', 'app.version']);
        expect($values['app.name'])->toBe('App');
        expect($values['app.version'])->toBe('1.0');
    } finally {
        deleteDir($dir);
    }
});
