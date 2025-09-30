<?php declare(strict_types=1);

use Core\Config;

beforeEach(function (): void {
    Config::clear();
});

// === ArrayAccess Tests ===

it('supports ArrayAccess for reading', function (): void {
    Config::set('app.name', 'MyApp');
    Config::set('database.host', 'localhost');
    
    $config = Config::getInstance();
    
    expect($config['app.name'])->toBe('MyApp');
    expect($config['database.host'])->toBe('localhost');
});

it('supports ArrayAccess for writing', function (): void {
    $config = Config::getInstance();
    
    $config['app.name'] = 'MyApp';
    $config['database.host'] = 'localhost';
    
    expect(Config::get('app.name'))->toBe('MyApp');
    expect(Config::get('database.host'))->toBe('localhost');
});

it('supports ArrayAccess isset()', function (): void {
    $config = Config::getInstance();
    
    Config::set('app.name', 'MyApp');
    
    expect(isset($config['app.name']))->toBeTrue();
    expect(isset($config['nonexistent']))->toBeFalse();
});

it('supports ArrayAccess unset()', function (): void {
    $config = Config::getInstance();
    
    Config::set('app.name', 'MyApp');
    expect(Config::has('app.name'))->toBeTrue();
    
    unset($config['app.name']);
    
    expect(Config::has('app.name'))->toBeFalse();
});

it('ArrayAccess works with nested keys', function (): void {
    $config = Config::getInstance();
    
    $config['database.connections.mysql.host'] = 'localhost';
    $config['database.connections.mysql.port'] = 3306;
    
    expect($config['database.connections.mysql.host'])->toBe('localhost');
    expect($config['database.connections.mysql.port'])->toBe(3306);
});

it('ArrayAccess respects locked configuration', function (): void {
    $config = Config::getInstance();
    
    Config::set('app.name', 'MyApp');
    Config::lock();
    
    // Reading should work
    expect($config['app.name'])->toBe('MyApp');
    
    // Writing should throw
    expect(fn() => $config['app.version'] = '1.0')
        ->toThrow(RuntimeException::class, 'locked');
});

// === Countable Tests ===

it('counts top-level configuration items', function (): void {
    $config = Config::getInstance();
    
    expect(count($config))->toBe(0);
    
    Config::set('app', ['name' => 'MyApp']);
    expect(count($config))->toBe(1);
    
    Config::set('database', ['host' => 'localhost']);
    expect(count($config))->toBe(2);
    
    Config::set('cache', ['driver' => 'redis']);
    expect(count($config))->toBe(3);
});

it('count reflects forgotten items', function (): void {
    $config = Config::getInstance();
    
    Config::set('a', 1);
    Config::set('b', 2);
    Config::set('c', 3);
    
    expect(count($config))->toBe(3);
    
    Config::forget('b');
    expect(count($config))->toBe(2);
});

it('getInstance returns same instance', function (): void {
    $config1 = Config::getInstance();
    $config2 = Config::getInstance();
    
    expect($config1)->toBe($config2);
});

// === Memoized Macros Tests ===

it('executes memoized macro only once', function (): void {
    $counter = 0;
    
    Config::memoizedMacro('expensive.operation', function () use (&$counter) {
        $counter++;
        return 'result-' . $counter;
    });
    
    // First call
    $result1 = Config::resolve('expensive.operation');
    expect($result1)->toBe('result-1');
    expect($counter)->toBe(1);
    
    // Second call - should return cached result
    $result2 = Config::resolve('expensive.operation');
    expect($result2)->toBe('result-1'); // Same result
    expect($counter)->toBe(1); // Counter didn't increase
    
    // Third call
    $result3 = Config::resolve('expensive.operation');
    expect($result3)->toBe('result-1');
    expect($counter)->toBe(1);
});

it('regular macro executes every time', function (): void {
    $counter = 0;
    
    Config::macro('regular.operation', function () use (&$counter) {
        $counter++;
        return 'result-' . $counter;
    });
    
    expect(Config::resolve('regular.operation'))->toBe('result-1');
    expect(Config::resolve('regular.operation'))->toBe('result-2');
    expect(Config::resolve('regular.operation'))->toBe('result-3');
    expect($counter)->toBe(3);
});

it('memoized macro can return different types', function (): void {
    Config::memoizedMacro('returns.array', fn() => ['a', 'b', 'c']);
    Config::memoizedMacro('returns.null', fn() => null);
    Config::memoizedMacro('returns.false', fn() => false);
    Config::memoizedMacro('returns.zero', fn() => 0);
    
    expect(Config::resolve('returns.array'))->toBe(['a', 'b', 'c']);
    expect(Config::resolve('returns.null'))->toBeNull();
    expect(Config::resolve('returns.false'))->toBeFalse();
    expect(Config::resolve('returns.zero'))->toBe(0);
    
    // Verify they're cached (call again)
    expect(Config::resolve('returns.array'))->toBe(['a', 'b', 'c']);
});

it('clears memoized values on clear()', function (): void {
    $counter = 0;
    
    Config::memoizedMacro('counter', function () use (&$counter) {
        return ++$counter;
    });
    
    expect(Config::resolve('counter'))->toBe(1);
    expect(Config::resolve('counter'))->toBe(1); // Cached
    
    Config::clear();
    
    // After clear, macro is gone
    expect(Config::has('counter'))->toBeFalse();
});

it('memoized macro works with nested keys', function (): void {
    $called = false;
    
    Config::memoizedMacro('app.services.list', function () use (&$called) {
        $called = true;
        return ['ServiceA', 'ServiceB'];
    });
    
    expect($called)->toBeFalse();
    
    $result = Config::resolve('app.services.list');
    expect($result)->toBe(['ServiceA', 'ServiceB']);
    expect($called)->toBeTrue();
    
    // Call again - should not execute callback
    $called = false;
    $result = Config::resolve('app.services.list');
    expect($result)->toBe(['ServiceA', 'ServiceB']);
    expect($called)->toBeFalse(); // Not called again
});

it('prevents setting memoized macro when locked', function (): void {
    Config::lock();
    
    expect(fn() => Config::memoizedMacro('test', fn() => 'value'))
        ->toThrow(RuntimeException::class, 'locked');
});

// === JSON File Support Tests ===

it('loads JSON configuration file', function (): void {
    $dir = sys_get_temp_dir() . '/config_json_' . uniqid();
    mkdir($dir, 0755, true);
    
    $jsonFile = $dir . '/app.json';
    file_put_contents($jsonFile, json_encode([
        'name' => 'MyApp',
        'version' => '1.0',
        'debug' => true,
        'settings' => [
            'timezone' => 'UTC',
            'locale' => 'en_US',
        ],
    ]));
    
    try {
        Config::loadFile($jsonFile);
        
        expect(Config::get('app.name'))->toBe('MyApp');
        expect(Config::get('app.version'))->toBe('1.0');
        expect(Config::get('app.debug'))->toBeTrue();
        expect(Config::get('app.settings.timezone'))->toBe('UTC');
        expect(Config::get('app.settings.locale'))->toBe('en_US');
    } finally {
        @unlink($jsonFile);
        @rmdir($dir);
    }
});

it('throws on invalid JSON', function (): void {
    $dir = sys_get_temp_dir() . '/config_json_' . uniqid();
    mkdir($dir, 0755, true);
    
    $jsonFile = $dir . '/invalid.json';
    file_put_contents($jsonFile, '{"invalid": json}'); // Invalid JSON
    
    try {
        expect(fn() => Config::loadFile($jsonFile))
            ->toThrow(RuntimeException::class, 'Invalid JSON');
    } finally {
        @unlink($jsonFile);
        @rmdir($dir);
    }
});

it('throws on JSON file that does not contain object/array', function (): void {
    $dir = sys_get_temp_dir() . '/config_json_' . uniqid();
    mkdir($dir, 0755, true);
    
    $jsonFile = $dir . '/scalar.json';
    file_put_contents($jsonFile, '"just a string"');
    
    try {
        expect(fn() => Config::loadFile($jsonFile))
            ->toThrow(RuntimeException::class, 'must contain an object/array');
    } finally {
        @unlink($jsonFile);
        @rmdir($dir);
    }
});

it('merges JSON configurations with same key', function (): void {
    $dir = sys_get_temp_dir() . '/config_json_' . uniqid();
    mkdir($dir, 0755, true);
    
    $json1 = $dir . '/app.json';
    
    file_put_contents($json1, json_encode([
        'name' => 'App1',
        'features' => ['feature1'],
    ]));
    
    try {
        // Load same file twice to test merging
        Config::loadFile($json1);
        
        // Update file and load again
        file_put_contents($json1, json_encode([
            'name' => 'App2',
            'features' => ['feature2'],
            'new_key' => 'value',
        ]));
        
        Config::loadFile($json1);
        
        // Name should be overwritten
        expect(Config::get('app.name'))->toBe('App2');
        // Features should be merged (both arrays)
        expect(Config::get('app.features'))->toBe(['feature1', 'feature2']);
        // New key should be added
        expect(Config::get('app.new_key'))->toBe('value');
    } finally {
        @unlink($json1);
        @rmdir($dir);
    }
});

it('throws on unsupported file format', function (): void {
    $dir = sys_get_temp_dir() . '/config_test_' . uniqid();
    mkdir($dir, 0755, true);
    
    $xmlFile = $dir . '/config.xml';
    file_put_contents($xmlFile, '<config></config>');
    
    try {
        expect(fn() => Config::loadFile($xmlFile))
            ->toThrow(RuntimeException::class, 'Unsupported file format');
    } finally {
        @unlink($xmlFile);
        @rmdir($dir);
    }
});

it('loads both PHP and JSON files', function (): void {
    $dir = sys_get_temp_dir() . '/config_mixed_' . uniqid();
    mkdir($dir, 0755, true);
    
    $phpFile = $dir . '/app.php';
    $jsonFile = $dir . '/database.json';
    
    file_put_contents($phpFile, '<?php return ["name" => "MyApp"];');
    file_put_contents($jsonFile, json_encode(['host' => 'localhost', 'port' => 3306]));
    
    try {
        Config::loadFile($phpFile);
        Config::loadFile($jsonFile);
        
        expect(Config::get('app.name'))->toBe('MyApp');
        expect(Config::get('database.host'))->toBe('localhost');
        expect(Config::get('database.port'))->toBe(3306);
    } finally {
        @unlink($phpFile);
        @unlink($jsonFile);
        @rmdir($dir);
    }
});

it('handles UTF-8 in JSON files', function (): void {
    $dir = sys_get_temp_dir() . '/config_json_' . uniqid();
    mkdir($dir, 0755, true);
    
    $jsonFile = $dir . '/i18n.json';
    file_put_contents($jsonFile, json_encode([
        'greeting' => 'Привет мир',
        'emoji' => '🚀 🎉',
        'chinese' => '你好世界',
    ], JSON_UNESCAPED_UNICODE));
    
    try {
        Config::loadFile($jsonFile);
        
        expect(Config::get('i18n.greeting'))->toBe('Привет мир');
        expect(Config::get('i18n.emoji'))->toBe('🚀 🎉');
        expect(Config::get('i18n.chinese'))->toBe('你好世界');
    } finally {
        @unlink($jsonFile);
        @rmdir($dir);
    }
});

// === Combined Advanced Features Tests ===

it('uses ArrayAccess with memoized macros', function (): void {
    $config = Config::getInstance();
    $counter = 0;
    
    Config::memoizedMacro('computed.value', function () use (&$counter) {
        return ++$counter;
    });
    
    // ArrayAccess returns the callable
    expect($config['computed.value'])->toBeCallable();
    
    // Resolve the macro by key
    expect(Config::resolve('computed.value'))->toBe(1);
    expect(Config::resolve('computed.value'))->toBe(1); // Cached
});

it('loads JSON and accesses via ArrayAccess', function (): void {
    $dir = sys_get_temp_dir() . '/config_json_' . uniqid();
    mkdir($dir, 0755, true);
    
    $jsonFile = $dir . '/app.json';
    file_put_contents($jsonFile, json_encode(['name' => 'MyApp']));
    
    try {
        Config::loadFile($jsonFile);
        
        $config = Config::getInstance();
        expect($config['app.name'])->toBe('MyApp');
    } finally {
        @unlink($jsonFile);
        @rmdir($dir);
    }
});

it('combines getMany with ArrayAccess', function (): void {
    $config = Config::getInstance();
    
    $config['app.name'] = 'MyApp';
    $config['app.version'] = '1.0';
    $config['database.host'] = 'localhost';
    
    $values = Config::getMany(['app.name', 'app.version', 'database.host']);
    
    expect($values)->toBe([
        'app.name' => 'MyApp',
        'app.version' => '1.0',
        'database.host' => 'localhost',
    ]);
});
