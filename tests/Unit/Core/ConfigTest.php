<?php declare(strict_types=1);

use Core\Config;

beforeEach(function (): void {
    Config::clear();
});

it('sets and gets simple keys', function (): void {
    Config::set('app', ['name' => 'MyApp']);
    expect(Config::get('app'))->toBe(['name' => 'MyApp']);
});

it('supports dot notation for set and get', function (): void {
    Config::set('database.connections.mysql.host', 'localhost');
    expect(Config::get('database.connections.mysql.host'))->toBe('localhost');
});

it('returns default when key missing', function (): void {
    expect(Config::get('nonexistent.key', 'default'))
        ->toBe('default');
});

it('checks existence with has()', function (): void {
    Config::set('cache.redis.host', '127.0.0.1');
    expect(Config::has('cache.redis.host'))->toBeTrue();
    expect(Config::has('cache.redis.port'))->toBeFalse();
});

it('forgets nested keys', function (): void {
    Config::set('app.debug', true);
    Config::forget('app.debug');
    expect(Config::has('app.debug'))->toBeFalse();
});

it('returns all configuration items', function (): void {
    Config::set('a', 1);
    Config::set('b.c', 2);
    expect(Config::all())->toBe(['a' => 1, 'b' => ['c' => 2]]);
});

it('loads configuration from a directory', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['name' => 'MyApp', 'debug' => true],
        'database.php' => [
            'default' => 'mysql',
            'connections' => [
                'mysql' => ['host' => 'localhost', 'port' => 3306],
            ],
        ],
    ]);

    try {
        Config::load($dir);
        expect(Config::get('app.name'))->toBe('MyApp');
        expect(Config::get('database.connections.mysql.port'))->toBe(3306);
    } finally {
        deleteDir($dir);
    }
});

it('prevents duplicate directory loading', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['providers' => ['A']],
    ]);

    try {
        Config::load($dir);
        Config::load($dir);
        // If loaded twice with recursive merge, we would see ['A','A'].
        expect(Config::get('app.providers'))->toBe(['A']);
    } finally {
        deleteDir($dir);
    }
});

it('merges config when loading files with same key', function (): void {
    $dir1 = createTempConfigDir([
        'app.php' => ['providers' => ['P1'], 'debug' => true],
    ]);
    $dir2 = createTempConfigDir([
        'app.php' => ['providers' => ['P2'], 'environment' => 'prod'],
    ]);

    try {
        Config::loadFile($dir1 . DIRECTORY_SEPARATOR . 'app.php');
        Config::loadFile($dir2 . DIRECTORY_SEPARATOR . 'app.php');

        expect(Config::get('app.providers'))->toBe(['P1', 'P2']);
        expect(Config::get('app.debug'))->toBeTrue();
        expect(Config::get('app.environment'))->toBe('prod');
    } finally {
        deleteDir($dir1);
        deleteDir($dir2);
    }
});

it('throws when loading non-existing directory', function (): void {
    expect(fn() => Config::load('Z:/definitely/nonexistent/path'))
        ->toThrow(InvalidArgumentException::class);
});

it('throws when loading path that is not a directory', function (): void {
    $file = tempnam(sys_get_temp_dir(), 'cfg');
    try {
        file_put_contents($file, 'temporary');
        expect(fn() => Config::load($file))
            ->toThrow(InvalidArgumentException::class);
    } finally {
        if ($file !== false && file_exists($file)) {
            @unlink($file);
        }
    }
});

it('throws when loading missing file', function (): void {
    expect(fn() => Config::loadFile('Z:/missing/file.php'))
        ->toThrow(InvalidArgumentException::class);
});

it('throws when configuration file does not return an array', function (): void {
    $dir = createTempConfigDir([]);
    $file = $dir . DIRECTORY_SEPARATOR . 'invalid.php';
    file_put_contents($file, "<?php return 'not-an-array';");

    try {
        expect(fn() => Config::loadFile($file))
            ->toThrow(RuntimeException::class);
    } finally {
        deleteDir($dir);
    }
});

it('clear() resets data and loaded paths enabling reload of same directory', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['providers' => ['A']],
    ]);

    try {
        Config::load($dir);
        expect(Config::get('app.providers'))->toBe(['A']);

        // Change file contents after initial load
        $file = $dir . DIRECTORY_SEPARATOR . 'app.php';
        file_put_contents($file, '<?php return ' . var_export(['providers' => ['A', 'B']], true) . ';');

        // Without clear(), load($dir) would be ignored due to loadedPaths deduplication
        Config::clear();
        Config::load($dir);
        expect(Config::get('app.providers'))->toBe(['A', 'B']);
    } finally {
        deleteDir($dir);
    }
});

it('has and forget work for top-level keys without dot notation', function (): void {
    Config::set('top', 123);
    expect(Config::has('top'))->toBeTrue();
    expect(Config::get('top'))->toBe(123);
    Config::forget('top');
    expect(Config::has('top'))->toBeFalse();
    expect(Config::get('top', 'default'))
        ->toBe('default');
});

// === Cache Tests ===

it('caches configuration to file', function (): void {
    $cachePath = sys_get_temp_dir() . '/config_cache_' . uniqid() . '.php';

    try {
        Config::set('app.name', 'TestApp');
        Config::set('database.host', 'localhost');

        expect(Config::cache($cachePath))->toBeTrue();
        expect(file_exists($cachePath))->toBeTrue();

        // Verify cache file content
        $cached = require $cachePath;
        expect($cached)->toBeArray();
        expect($cached['items']['app']['name'])->toBe('TestApp');
        expect($cached['items']['database']['host'])->toBe('localhost');
        expect($cached['timestamp'])->toBeInt();
    } finally {
        if (file_exists($cachePath)) {
            @unlink($cachePath);
        }
    }
});

it('loads configuration from cache', function (): void {
    $cachePath = sys_get_temp_dir() . '/config_cache_' . uniqid() . '.php';

    try {
        Config::set('app.name', 'TestApp');
        Config::set('database.host', 'localhost');
        Config::cache($cachePath);

        Config::clear();
        expect(Config::get('app.name'))->toBeNull();

        expect(Config::loadCached($cachePath))->toBeTrue();
        expect(Config::get('app.name'))->toBe('TestApp');
        expect(Config::get('database.host'))->toBe('localhost');
        expect(Config::isLoadedFromCache())->toBeTrue();
    } finally {
        if (file_exists($cachePath)) {
            @unlink($cachePath);
        }
    }
});

it('returns false when loading non-existent cache', function (): void {
    expect(Config::loadCached('/nonexistent/cache.php'))->toBeFalse();
});

it('throws when cache file is corrupted', function (): void {
    $cachePath = sys_get_temp_dir() . '/config_cache_' . uniqid() . '.php';

    try {
        file_put_contents($cachePath, '<?php return "invalid";');
        expect(fn() => Config::loadCached($cachePath))
            ->toThrow(RuntimeException::class, 'corrupted or invalid');
    } finally {
        if (file_exists($cachePath)) {
            @unlink($cachePath);
        }
    }
});

it('checks if cache exists', function (): void {
    $cachePath = sys_get_temp_dir() . '/config_cache_' . uniqid() . '.php';

    try {
        expect(Config::isCached($cachePath))->toBeFalse();

        Config::set('app.name', 'TestApp');
        Config::cache($cachePath);

        expect(Config::isCached($cachePath))->toBeTrue();
    } finally {
        if (file_exists($cachePath)) {
            @unlink($cachePath);
        }
    }
});

it('clears cache file', function (): void {
    $cachePath = sys_get_temp_dir() . '/config_cache_' . uniqid() . '.php';

    try {
        Config::set('app.name', 'TestApp');
        Config::cache($cachePath);
        expect(file_exists($cachePath))->toBeTrue();

        expect(Config::clearCache($cachePath))->toBeTrue();
        expect(file_exists($cachePath))->toBeFalse();

        // Clearing non-existent cache should return true
        expect(Config::clearCache($cachePath))->toBeTrue();
    } finally {
        if (file_exists($cachePath)) {
            @unlink($cachePath);
        }
    }
});

it('gets cache info', function (): void {
    $cachePath = sys_get_temp_dir() . '/config_cache_' . uniqid() . '.php';

    try {
        expect(Config::getCacheInfo($cachePath))->toBeNull();

        Config::set('app.name', 'TestApp');
        Config::cache($cachePath);

        $info = Config::getCacheInfo($cachePath);
        expect($info)->toBeArray();
        expect($info['timestamp'])->toBeInt();
        expect($info['size'])->toBeInt();
        expect($info['size'])->toBeGreaterThan(0);
        expect($info['created_at'])->toBeString();
    } finally {
        if (file_exists($cachePath)) {
            @unlink($cachePath);
        }
    }
});

it('creates cache directory if not exists', function (): void {
    $cacheDir = sys_get_temp_dir() . '/config_test_' . uniqid();
    $cachePath = $cacheDir . '/cache.php';

    try {
        expect(is_dir($cacheDir))->toBeFalse();

        Config::set('app.name', 'TestApp');
        Config::cache($cachePath);

        expect(is_dir($cacheDir))->toBeTrue();
        expect(file_exists($cachePath))->toBeTrue();
    } finally {
        if (file_exists($cachePath)) {
            @unlink($cachePath);
        }
        if (is_dir($cacheDir)) {
            @rmdir($cacheDir);
        }
    }
});

it('clear() resets loadedFromCache flag', function (): void {
    $cachePath = sys_get_temp_dir() . '/config_cache_' . uniqid() . '.php';

    try {
        Config::set('app.name', 'TestApp');
        Config::cache($cachePath);

        Config::clear();
        Config::loadCached($cachePath);
        expect(Config::isLoadedFromCache())->toBeTrue();

        Config::clear();
        expect(Config::isLoadedFromCache())->toBeFalse();
    } finally {
        if (file_exists($cachePath)) {
            @unlink($cachePath);
        }
    }
});

// === Array Methods Tests ===

it('pushes value to array', function (): void {
    Config::set('app.providers', ['Provider1']);
    Config::push('app.providers', 'Provider2');
    Config::push('app.providers', 'Provider3');

    expect(Config::get('app.providers'))->toBe(['Provider1', 'Provider2', 'Provider3']);
});

it('pushes to non-existent key creates array', function (): void {
    Config::push('new.array', 'FirstValue');
    expect(Config::get('new.array'))->toBe(['FirstValue']);
});

it('throws when pushing to non-array value', function (): void {
    Config::set('app.name', 'MyApp');
    expect(fn() => Config::push('app.name', 'value'))
        ->toThrow(RuntimeException::class, 'not an array');
});

it('prepends value to array', function (): void {
    Config::set('app.middleware', ['MiddlewareC']);
    Config::prepend('app.middleware', 'MiddlewareB');
    Config::prepend('app.middleware', 'MiddlewareA');

    expect(Config::get('app.middleware'))->toBe(['MiddlewareA', 'MiddlewareB', 'MiddlewareC']);
});

it('prepends to non-existent key creates array', function (): void {
    Config::prepend('new.array', 'FirstValue');
    expect(Config::get('new.array'))->toBe(['FirstValue']);
});

it('throws when prepending to non-array value', function (): void {
    Config::set('app.name', 'MyApp');
    expect(fn() => Config::prepend('app.name', 'value'))
        ->toThrow(RuntimeException::class, 'not an array');
});

it('pulls value and removes it', function (): void {
    Config::set('temp.value', 'temporary');
    
    $value = Config::pull('temp.value');
    
    expect($value)->toBe('temporary');
    expect(Config::has('temp.value'))->toBeFalse();
});

it('pulls with default when key missing', function (): void {
    $value = Config::pull('nonexistent.key', 'default_value');
    expect($value)->toBe('default_value');
});

it('pulls nested value', function (): void {
    Config::set('app.temp.secret', 'sensitive_data');
    
    $value = Config::pull('app.temp.secret', null);
    
    expect($value)->toBe('sensitive_data');
    expect(Config::has('app.temp.secret'))->toBeFalse();
    expect(Config::has('app.temp'))->toBeTrue(); // Parent still exists
});

// === Macro Tests ===

it('registers and resolves a macro', function (): void {
    $counter = 0;
    
    Config::macro('app.timestamp', function () use (&$counter) {
        $counter++;
        return time();
    });

    expect(Config::isMacro('app.timestamp'))->toBeTrue();
    
    // Resolving should execute the callable
    $result = Config::resolve('app.timestamp');
    expect($result)->toBeInt();
    expect($counter)->toBe(1);
    
    // Each resolve executes it again (not memoized by default)
    Config::resolve('app.timestamp');
    expect($counter)->toBe(2);
});

it('get returns callable without executing for macros', function (): void {
    Config::macro('app.factory', fn() => 'created');
    
    $value = Config::get('app.factory');
    expect($value)->toBeCallable();
});

it('resolve executes macro only if registered', function (): void {
    // Regular callable set without macro() should NOT be executed
    Config::set('app.callback', fn() => 'should-not-execute');
    
    $result = Config::resolve('app.callback');
    expect($result)->toBeCallable(); // Returns the callable itself
});

it('resolves nested macro with dot notation', function (): void {
    Config::macro('database.connection.factory', function () {
        return [
            'host' => 'localhost',
            'port' => 3306,
        ];
    });

    $result = Config::resolve('database.connection.factory');
    expect($result)->toBe([
        'host' => 'localhost',
        'port' => 3306,
    ]);
});

it('resolves all macros recursively', function (): void {
    Config::set('app.name', 'MyApp');
    Config::macro('app.version', fn() => '1.0.0');
    Config::macro('database.host', fn() => 'localhost');
    Config::set('database.port', 3306);

    $resolved = Config::resolveAll();
    
    expect($resolved['app']['name'])->toBe('MyApp');
    expect($resolved['app']['version'])->toBe('1.0.0');
    expect($resolved['database']['host'])->toBe('localhost');
    expect($resolved['database']['port'])->toBe(3306);
});

it('clear removes macros', function (): void {
    Config::macro('app.factory', fn() => 'value');
    expect(Config::isMacro('app.factory'))->toBeTrue();
    
    Config::clear();
    expect(Config::isMacro('app.factory'))->toBeFalse();
});

it('caches and loads macros', function (): void {
    $cachePath = sys_get_temp_dir() . '/config_cache_' . uniqid() . '.php';

    try {
        Config::macro('app.factory', fn() => 'lazy-value');
        Config::cache($cachePath);

        Config::clear();
        expect(Config::isMacro('app.factory'))->toBeFalse();

        Config::loadCached($cachePath);
        expect(Config::isMacro('app.factory'))->toBeTrue();
        expect(Config::resolve('app.factory'))->toBe('lazy-value');
    } finally {
        if (file_exists($cachePath)) {
            @unlink($cachePath);
        }
    }
});

it('resolves with default when macro does not exist', function (): void {
    $result = Config::resolve('nonexistent.macro', 'default-value');
    expect($result)->toBe('default-value');
});

it('macro can access external state', function (): void {
    $externalValue = 'external';
    
    Config::macro('app.dynamic', function () use ($externalValue) {
        return "Value: {$externalValue}";
    });

    expect(Config::resolve('app.dynamic'))->toBe('Value: external');
});

// === Environment-specific Config Tests ===

it('loads environment-specific configs from subdirectory', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['name' => 'MyApp', 'debug' => false, 'version' => '1.0'],
        'database.php' => ['host' => 'localhost', 'port' => 3306],
    ]);

    // Create production subdirectory with overrides
    $prodDir = $dir . DIRECTORY_SEPARATOR . 'production';
    mkdir($prodDir, 0755, true);
    file_put_contents(
        $prodDir . DIRECTORY_SEPARATOR . 'app.php',
        '<?php return ' . var_export(['debug' => false, 'log_level' => 'error'], true) . ';'
    );
    file_put_contents(
        $prodDir . DIRECTORY_SEPARATOR . 'database.php',
        '<?php return ' . var_export(['host' => 'prod-server.com'], true) . ';'
    );

    try {
        Config::load($dir, 'production');

        // Base config values
        expect(Config::get('app.name'))->toBe('MyApp');
        expect(Config::get('app.version'))->toBe('1.0');
        
        // Overridden by environment
        expect(Config::get('app.debug'))->toBe(false);
        expect(Config::get('app.log_level'))->toBe('error');
        expect(Config::get('database.host'))->toBe('prod-server.com');
        
        // Not overridden
        expect(Config::get('database.port'))->toBe(3306);
    } finally {
        deleteDir($dir);
    }
});

it('loads environment-specific configs with suffix', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['name' => 'MyApp', 'debug' => true],
        'database.php' => ['host' => 'localhost'],
    ]);

    // Create local environment files with suffix
    file_put_contents(
        $dir . DIRECTORY_SEPARATOR . 'app.local.php',
        '<?php return ' . var_export(['debug' => true, 'dev_mode' => true], true) . ';'
    );
    file_put_contents(
        $dir . DIRECTORY_SEPARATOR . 'database.local.php',
        '<?php return ' . var_export(['host' => '127.0.0.1', 'logging' => true], true) . ';'
    );

    try {
        Config::load($dir, 'local');

        expect(Config::get('app.name'))->toBe('MyApp');
        expect(Config::get('app.debug'))->toBe(true);
        expect(Config::get('app.dev_mode'))->toBe(true);
        expect(Config::get('database.host'))->toBe('127.0.0.1');
        expect(Config::get('database.logging'))->toBe(true);
    } finally {
        deleteDir($dir);
    }
});

it('loads both subdirectory and suffix environment configs', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['name' => 'MyApp', 'priority' => 'base'],
    ]);

    // Subdirectory approach
    $testDir = $dir . DIRECTORY_SEPARATOR . 'testing';
    mkdir($testDir, 0755, true);
    file_put_contents(
        $testDir . DIRECTORY_SEPARATOR . 'app.php',
        '<?php return ' . var_export(['priority' => 'subdirectory', 'from_dir' => true], true) . ';'
    );

    // Suffix approach (loaded after subdirectory, so has higher priority)
    file_put_contents(
        $dir . DIRECTORY_SEPARATOR . 'app.testing.php',
        '<?php return ' . var_export(['priority' => 'suffix', 'from_suffix' => true], true) . ';'
    );

    try {
        Config::load($dir, 'testing');

        // Suffix has priority (loaded last)
        expect(Config::get('app.priority'))->toBe('suffix');
        expect(Config::get('app.from_dir'))->toBe(true);
        expect(Config::get('app.from_suffix'))->toBe(true);
        expect(Config::get('app.name'))->toBe('MyApp');
    } finally {
        deleteDir($dir);
    }
});

it('works without environment parameter (backward compatible)', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['name' => 'MyApp'],
    ]);

    try {
        Config::load($dir); // No environment parameter
        expect(Config::get('app.name'))->toBe('MyApp');
    } finally {
        deleteDir($dir);
    }
});

it('ignores non-existent environment configs gracefully', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['name' => 'MyApp'],
    ]);

    try {
        Config::load($dir, 'nonexistent-env');
        // Should load base configs without errors
        expect(Config::get('app.name'))->toBe('MyApp');
    } finally {
        deleteDir($dir);
    }
});

// === Immutable/Lock Tests ===

it('locks configuration to prevent modifications', function (): void {
    Config::set('app.name', 'MyApp');
    expect(Config::isLocked())->toBeFalse();
    
    Config::lock();
    expect(Config::isLocked())->toBeTrue();
    
    expect(fn() => Config::set('app.name', 'NewName'))
        ->toThrow(RuntimeException::class, 'Configuration is locked');
});

it('prevents set() when locked', function (): void {
    Config::set('app.name', 'MyApp');
    Config::lock();
    
    expect(fn() => Config::set('app.version', '2.0'))
        ->toThrow(RuntimeException::class, 'Configuration is locked');
});

it('prevents forget() when locked', function (): void {
    Config::set('app.name', 'MyApp');
    Config::lock();
    
    expect(fn() => Config::forget('app.name'))
        ->toThrow(RuntimeException::class, 'Configuration is locked');
});

it('prevents push() when locked', function (): void {
    Config::set('app.providers', ['Provider1']);
    Config::lock();
    
    expect(fn() => Config::push('app.providers', 'Provider2'))
        ->toThrow(RuntimeException::class, 'Configuration is locked');
});

it('prevents prepend() when locked', function (): void {
    Config::set('app.middleware', ['Middleware1']);
    Config::lock();
    
    expect(fn() => Config::prepend('app.middleware', 'Middleware0'))
        ->toThrow(RuntimeException::class, 'Configuration is locked');
});

it('prevents pull() when locked', function (): void {
    Config::set('temp.value', 'temporary');
    Config::lock();
    
    expect(fn() => Config::pull('temp.value'))
        ->toThrow(RuntimeException::class, 'Configuration is locked');
});

it('prevents macro() when locked', function (): void {
    Config::lock();
    
    expect(fn() => Config::macro('app.factory', fn() => 'value'))
        ->toThrow(RuntimeException::class, 'Configuration is locked');
});

it('allows get() and has() when locked', function (): void {
    Config::set('app.name', 'MyApp');
    Config::lock();
    
    // Read operations should work
    expect(Config::get('app.name'))->toBe('MyApp');
    expect(Config::has('app.name'))->toBeTrue();
});

it('allows resolve() when locked', function (): void {
    Config::macro('app.factory', fn() => 'value');
    Config::lock();
    
    // Resolving should work even when locked
    expect(Config::resolve('app.factory'))->toBe('value');
});

it('unlocks configuration', function (): void {
    Config::lock();
    expect(Config::isLocked())->toBeTrue();
    
    Config::unlock();
    expect(Config::isLocked())->toBeFalse();
    
    // Should be able to modify again
    Config::set('app.name', 'MyApp');
    expect(Config::get('app.name'))->toBe('MyApp');
});

it('clear() unlocks configuration', function (): void {
    Config::set('app.name', 'MyApp');
    Config::lock();
    expect(Config::isLocked())->toBeTrue();
    
    Config::clear();
    expect(Config::isLocked())->toBeFalse();
    
    // Should be able to set again
    Config::set('app.name', 'NewApp');
    expect(Config::get('app.name'))->toBe('NewApp');
});

it('typical workflow: load, configure, lock', function (): void {
    $dir = createTempConfigDir([
        'app.php' => ['name' => 'MyApp', 'version' => '1.0'],
    ]);

    try {
        // 1. Load configuration
        Config::load($dir);
        expect(Config::get('app.name'))->toBe('MyApp');
        
        // 2. Make runtime modifications
        Config::set('app.initialized', true);
        expect(Config::get('app.initialized'))->toBeTrue();
        
        // 3. Lock for production safety
        Config::lock();
        
        // 4. Reading still works
        expect(Config::get('app.name'))->toBe('MyApp');
        expect(Config::get('app.initialized'))->toBeTrue();
        
        // 5. Modifications are prevented
        expect(fn() => Config::set('app.hacked', true))
            ->toThrow(RuntimeException::class);
    } finally {
        deleteDir($dir);
    }
});
