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
