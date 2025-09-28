## Config (Configuration Management)

The `Core\Config` class provides a simple, file-based configuration management system for PHP applications. It supports loading configuration files from directories, dot notation for nested values, and merging configurations with automatic deduplication of loaded paths.

### Key Features
- Load configuration files from directories (all `*.php` files)
- Dot notation support for nested configuration access
- Automatic merging of duplicate configuration keys
- Prevention of duplicate file loading
- Runtime configuration modification
- Full CRUD operations (create, read, update, delete)

### Basic Usage

```php
use Core\Config;

// 1) Load all .php config files from a directory
Config::load(__DIR__ . '/config');

// 2) Access configuration values with dot notation
$dbHost = Config::get('database.host', 'localhost');
$appName = Config::get('app.name', 'MyApp');
$debug = Config::get('app.debug', false);

// 3) Set configuration values at runtime
Config::set('app.environment', 'production');
Config::set('cache.driver', 'redis');

// 4) Check if configuration exists
if (Config::has('database.connections.mysql')) {
    // Database config exists
}

// 5) Remove configuration keys
Config::forget('old.setting');

// 6) Get all configuration data
$allConfig = Config::all();
```

### Configuration File Structure

Configuration files should be placed in a directory and return an array. The filename (without `.php` extension) becomes the top-level configuration key.

**Example directory structure:**
```
config/
├── app.php
├── database.php
└── cache.php
```

**Example `config/app.php`:**
```php
<?php
return [
    'name' => 'My Application',
    'debug' => true,
    'environment' => 'local',
    'providers' => [
        'App\Providers\AppServiceProvider',
        'App\Providers\RouteServiceProvider',
    ],
];
```

**Example `config/database.php`:**
```php
<?php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'myapp',
            'username' => 'root',
            'password' => '',
        ],
        'sqlite' => [
            'database' => ':memory:',
        ],
    ],
];
```

### API Reference

#### Config::load(string $path): void
- Loads all `*.php` files from the specified directory
- Each file must return an array
- Filename (without `.php`) becomes the configuration key
- Prevents loading the same directory multiple times
- Throws `InvalidArgumentException` if path doesn't exist or isn't a directory
- Throws `RuntimeException` if glob pattern fails

#### Config::loadFile(string $filePath): void
- Loads a single configuration file
- File must return an array
- If a configuration key already exists, arrays are merged recursively
- Throws `InvalidArgumentException` if file doesn't exist or isn't readable
- Throws `RuntimeException` if file doesn't return an array

#### Config::get(string $key, mixed $default = null): mixed
- Retrieves a configuration value using dot notation
- Returns `$default` if key doesn't exist
- Supports nested access: `'database.connections.mysql.host'`

#### Config::set(string $key, mixed $value): void
- Sets a configuration value using dot notation
- Creates nested arrays automatically if they don't exist
- Supports nested assignment: `'cache.redis.host'`

#### Config::has(string $key): bool
- Checks if a configuration key exists
- Supports dot notation for nested keys
- Returns `true` only if the complete path exists

#### Config::forget(string $key): void
- Removes a configuration key
- Supports dot notation for nested keys
- Safely handles non-existent paths

#### Config::all(): array
- Returns all configuration data as an array
- Useful for debugging or exporting configuration

#### Config::clear(): void
- Clears all configuration data and loaded paths
- Resets the class to its initial state

### Dot Notation Examples

```php
// Given this configuration structure:
// [
//     'app' => [
//         'name' => 'MyApp',
//         'debug' => true,
//         'providers' => ['Provider1', 'Provider2']
//     ],
//     'database' => [
//         'connections' => [
//             'mysql' => ['host' => 'localhost']
//         ]
//     ]
// ]

// Access nested values
$appName = Config::get('app.name');                    // 'MyApp'
$debug = Config::get('app.debug');                     // true
$providers = Config::get('app.providers');             // ['Provider1', 'Provider2']
$dbHost = Config::get('database.connections.mysql.host'); // 'localhost'

// Set nested values
Config::set('app.environment', 'production');
Config::set('database.connections.mysql.port', 3306);

// Check existence
Config::has('app.name');                               // true
Config::has('app.nonexistent');                        // false
Config::has('database.connections.mysql.host');        // true

// Remove values
Config::forget('app.debug');
Config::forget('database.connections.sqlite');
```

### Configuration Merging

When loading multiple files with the same configuration key, arrays are merged recursively:

```php
// First file: config/app.php
return ['providers' => ['Provider1'], 'debug' => true];

// Second file: config/app.php (loaded again)
return ['providers' => ['Provider2'], 'environment' => 'prod'];

// Result after merging:
// [
//     'providers' => ['Provider1', 'Provider2'],
//     'debug' => true,
//     'environment' => 'prod'
// ]
```

### Error Handling

The Config class throws specific exceptions for different error conditions:

- `InvalidArgumentException`: Invalid paths, missing files, or unreadable files
- `RuntimeException`: Configuration files that don't return arrays, or glob pattern failures

```php
try {
    Config::load('/nonexistent/path');
} catch (InvalidArgumentException $e) {
    // Handle invalid path
}

try {
    Config::loadFile('/path/to/invalid.php');
} catch (RuntimeException $e) {
    // Handle file that doesn't return array
}
```

### Best Practices

1. **File Organization**: Keep configuration files organized by feature or component
2. **Naming Convention**: Use descriptive filenames that match your configuration structure
3. **Default Values**: Always provide sensible defaults when using `Config::get()`
4. **Environment-Specific**: Consider loading different configuration directories for different environments
5. **Validation**: Validate configuration values after loading if needed

### Integration with Env Class

The Config class works well with the `Core\Env` class for environment-based configuration:

```php
use Core\Config;
use Core\Env;

// Load environment variables
Env::load(__DIR__ . '/.env');

// Load configuration files
Config::load(__DIR__ . '/config');

// Override config with environment variables
Config::set('database.connections.mysql.host', Env::get('DB_HOST', 'localhost'));
Config::set('database.connections.mysql.port', Env::get('DB_PORT', 3306));
Config::set('app.debug', Env::get('APP_DEBUG', false));
```

### Performance Considerations

- Configuration files are loaded once per directory (duplicate loading is prevented)
- All configuration data is stored in memory for fast access
- Dot notation parsing is lightweight but consider caching frequently accessed values
- Use `Config::clear()` in tests to ensure clean state between test runs

### Namespace and Location
- Class: `Core\Config`
- File: `core/Config.php`
