<?php declare(strict_types=1);

namespace Core;

use InvalidArgumentException;
use RuntimeException;

class Config
{
    protected static array $items = [];
    protected static array $loadedPaths = [];
    protected static bool $loadedFromCache = false;
    protected static array $macros = [];
    protected static bool $locked = false;

    /**
     * Loads configuration files from the specified directory
     *
     * @param string $path Path to the directory containing config files
     * @param string|null $environment Optional environment name to load environment-specific configs
     * @param bool $recursive Whether to load configuration files recursively from subdirectories
     * @throws InvalidArgumentException If path doesn't exist or is not a directory
     * @throws RuntimeException If glob pattern fails
     */
    public static function load(string $path, ?string $environment = null, bool $recursive = false): void
    {
        $realPath = realpath($path);

        if ($realPath === false) {
            throw new InvalidArgumentException("Path does not exist: {$path}");
        }

        if (!is_dir($realPath)) {
            throw new InvalidArgumentException("Specified path is not a directory: {$path}");
        }

        // Prevent loading the same path multiple times
        if (in_array($realPath, self::$loadedPaths, true)) {
            return;
        }

        if ($recursive) {
            self::loadRecursive($realPath);
        } else {
            $pattern = $realPath . '/*.php';
            $files = glob($pattern);

            if ($files === false) {
                throw new RuntimeException("Error searching for files with pattern: {$pattern}");
            }

            foreach ($files as $file) {
                self::loadFile($file);
            }
        }

        self::$loadedPaths[] = $realPath;

        // Load environment-specific configurations if environment is specified
        if ($environment !== null) {
            self::loadEnvironmentConfigs($realPath, $environment);
        }
    }

    /**
     * Recursively loads configuration files from directory and subdirectories
     *
     * Directory structure defines the configuration namespace:
     * - config/app.php → 'app'
     * - config/services/mail.php → 'services.mail'
     * - config/external/api/github.php → 'external.api.github'
     *
     * @param string $basePath Base directory to load from
     * @param string $namespace Current namespace prefix
     */
    protected static function loadRecursive(string $basePath, string $namespace = ''): void
    {
        $items = scandir($basePath);

        if ($items === false) {
            throw new RuntimeException("Failed to scan directory: {$basePath}");
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $basePath . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                // Recursively load subdirectories
                $newNamespace = $namespace === '' ? $item : $namespace . '.' . $item;
                self::loadRecursive($fullPath, $newNamespace);
            } elseif (is_file($fullPath) && pathinfo($fullPath, PATHINFO_EXTENSION) === 'php') {
                // Load PHP config file
                $filename = pathinfo($fullPath, PATHINFO_FILENAME);
                $key = $namespace === '' ? $filename : $namespace . '.' . $filename;
                
                // Load with the computed namespace key
                $config = require $fullPath;

                if (!is_array($config)) {
                    throw new RuntimeException("Configuration file must return an array: {$fullPath}");
                }

                // Set using nested key structure
                self::setByPath($key, $config);
            }
        }
    }

    /**
     * Sets a configuration value by path, handling nested structures
     *
     * @param string $path Dot-notation path
     * @param array $value Configuration array to set
     */
    protected static function setByPath(string $path, array $value): void
    {
        $parts = explode('.', $path);
        $current = &self::$items;

        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                // Last part: merge if exists, otherwise set
                if (isset($current[$part]) && is_array($current[$part])) {
                    $current[$part] = self::mergeConfigs($current[$part], $value);
                } else {
                    $current[$part] = $value;
                }
            } else {
                if (!isset($current[$part]) || !is_array($current[$part])) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
        }
    }

    /**
     * Merges two configuration arrays intelligently
     *
     * Unlike array_merge_recursive, this function properly overwrites scalar values
     * instead of creating arrays from them, while still merging associative arrays
     * and appending to numeric (list) arrays.
     *
     * @param array $base Base configuration array
     * @param array $override Override configuration array
     * @return array Merged configuration
     */
    protected static function mergeConfigs(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                // Both are arrays
                if (array_is_list($base[$key]) && array_is_list($value)) {
                    // Both are lists (numeric arrays) - append values
                    $base[$key] = array_merge($base[$key], $value);
                } else {
                    // At least one is associative - merge recursively
                    $base[$key] = self::mergeConfigs($base[$key], $value);
                }
            } else {
                // Override the value (scalar or array replaces anything)
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * Loads environment-specific configuration files
     *
     * Supports two approaches:
     * 1. Subdirectory: config/production/app.php
     * 2. Suffix: config/app.production.php
     *
     * @param string $basePath Base configuration directory path
     * @param string $environment Environment name (e.g., 'local', 'production', 'testing')
     */
    protected static function loadEnvironmentConfigs(string $basePath, string $environment): void
    {
        // Approach 1: Load from environment subdirectory (e.g., config/production/)
        $envDir = $basePath . DIRECTORY_SEPARATOR . $environment;
        if (is_dir($envDir)) {
            $pattern = $envDir . '/*.php';
            $files = glob($pattern);

            if ($files !== false) {
                foreach ($files as $file) {
                    self::loadFile($file);
                }
            }
        }

        // Approach 2: Load files with environment suffix (e.g., app.production.php)
        $pattern = $basePath . '/*.' . $environment . '.php';
        $files = glob($pattern);

        if ($files !== false) {
            foreach ($files as $file) {
                // Extract base name without environment suffix
                $basename = basename($file, '.' . $environment . '.php');
                self::loadFileWithKey($file, $basename);
            }
        }
    }

    /**
     * Loads a single configuration file with a specific key
     *
     * @param string $filePath Path to the configuration file
     * @param string $key The configuration key to use
     * @throws InvalidArgumentException If file doesn't exist or is not readable
     * @throws RuntimeException If configuration file doesn't return an array
     */
    protected static function loadFileWithKey(string $filePath, string $key): void
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new InvalidArgumentException("File is not readable: {$filePath}");
        }

        // Check that the file returns an array
        $config = require $filePath;

        if (!is_array($config)) {
            throw new RuntimeException("Configuration file must return an array: {$filePath}");
        }

        // Merge configurations if key already exists
        if (isset(self::$items[$key]) && is_array(self::$items[$key])) {
            self::$items[$key] = self::mergeConfigs(self::$items[$key], $config);
        } else {
            self::$items[$key] = $config;
        }
    }

    /**
     * Loads a single configuration file
     *
     * @param string $filePath Path to the configuration file
     * @throws InvalidArgumentException If file doesn't exist or is not readable
     * @throws RuntimeException If configuration file doesn't return an array
     */
    public static function loadFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new InvalidArgumentException("File is not readable: {$filePath}");
        }

        $key = basename($filePath, '.php');

        // Check that the file returns an array
        $config = require $filePath;

        if (!is_array($config)) {
            throw new \RuntimeException("Configuration file must return an array: {$filePath}");
        }

        // Merge configurations if key already exists
        if (isset(self::$items[$key]) && is_array(self::$items[$key])) {
            self::$items[$key] = self::mergeConfigs(self::$items[$key], $config);
        } else {
            self::$items[$key] = $config;
        }
    }

    /**
     * Gets a configuration value by key with dot notation support
     *
     * @param string $key The configuration key (supports dot notation like 'database.host')
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The configuration value or default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (str_contains($key, '.')) {
            return self::getNestedValue($key, $default);
        }

        return array_key_exists($key, self::$items) ? self::$items[$key] : $default;
    }

    /**
     * Sets a configuration value with dot notation support
     *
     * @param string $key The configuration key (supports dot notation)
     * @param mixed $value The value to set
     * @throws RuntimeException If configuration is locked
     */
    public static function set(string $key, mixed $value): void
    {
        self::ensureNotLocked();

        if (str_contains($key, '.')) {
            self::setNestedValue($key, $value);
        } else {
            self::$items[$key] = $value;
        }
    }

    /**
     * Checks if a configuration key exists
     *
     * @param string $key The configuration key to check
     * @return bool True if key exists, false otherwise
     */
    public static function has(string $key): bool
    {
        if (str_contains($key, '.')) {
            $parts = explode('.', $key);
            $value = self::$items;

            foreach ($parts as $part) {
                if (!is_array($value) || !array_key_exists($part, $value)) {
                    return false;
                }
                $value = $value[$part];
            }

            return true;
        }

        return array_key_exists($key, self::$items);
    }

    /**
     * Removes a configuration key
     *
     * @param string $key The configuration key to remove (supports dot notation)
     * @throws RuntimeException If configuration is locked
     */
    public static function forget(string $key): void
    {
        self::ensureNotLocked();

        if (str_contains($key, '.')) {
            self::forgetNestedValue($key);
        } else {
            unset(self::$items[$key]);
        }
    }

    /**
     * Returns all configuration data
     *
     * @return array All configuration items
     */
    public static function all(): array
    {
        return self::$items;
    }

    /**
     * Clears all configuration data and loaded paths
     *
     * Note: This also unlocks the configuration.
     */
    public static function clear(): void
    {
        self::$items = [];
        self::$loadedPaths = [];
        self::$loadedFromCache = false;
        self::$macros = [];
        self::$locked = false;
    }

    /**
     * Pushes a value onto the end of an array configuration value
     *
     * @param string $key The configuration key (supports dot notation)
     * @param mixed $value The value to push
     * @throws RuntimeException If the configuration value is not an array or if configuration is locked
     */
    public static function push(string $key, mixed $value): void
    {
        self::ensureNotLocked();

        $array = self::get($key, []);

        if (!is_array($array)) {
            throw new RuntimeException("Configuration value at '{$key}' is not an array");
        }

        $array[] = $value;
        
        // Temporarily unlock to allow set() to work
        $wasLocked = self::$locked;
        self::$locked = false;
        self::set($key, $array);
        self::$locked = $wasLocked;
    }

    /**
     * Prepends a value to the beginning of an array configuration value
     *
     * @param string $key The configuration key (supports dot notation)
     * @param mixed $value The value to prepend
     * @throws RuntimeException If the configuration value is not an array or if configuration is locked
     */
    public static function prepend(string $key, mixed $value): void
    {
        self::ensureNotLocked();

        $array = self::get($key, []);

        if (!is_array($array)) {
            throw new RuntimeException("Configuration value at '{$key}' is not an array");
        }

        array_unshift($array, $value);
        
        // Temporarily unlock to allow set() to work
        $wasLocked = self::$locked;
        self::$locked = false;
        self::set($key, $array);
        self::$locked = $wasLocked;
    }

    /**
     * Gets a value from configuration and removes it
     *
     * @param string $key The configuration key (supports dot notation)
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The pulled value or default
     * @throws RuntimeException If configuration is locked
     */
    public static function pull(string $key, mixed $default = null): mixed
    {
        self::ensureNotLocked();

        $value = self::get($key, $default);
        
        // Temporarily unlock to allow forget() to work
        $wasLocked = self::$locked;
        self::$locked = false;
        self::forget($key);
        self::$locked = $wasLocked;

        return $value;
    }

    /**
     * Registers a macro (callable) for lazy evaluation
     *
     * @param string $key The configuration key (supports dot notation)
     * @param callable $callback The callback to execute when resolved
     * @throws RuntimeException If configuration is locked
     */
    public static function macro(string $key, callable $callback): void
    {
        self::ensureNotLocked();

        self::$macros[$key] = $callback;
        
        // Temporarily unlock to allow set() to work
        $wasLocked = self::$locked;
        self::$locked = false;
        self::set($key, $callback);
        self::$locked = $wasLocked;
    }

    /**
     * Resolves a configuration value, executing it if it's a callable/macro
     *
     * @param string $key The configuration key (supports dot notation)
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The resolved value or default
     */
    public static function resolve(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);

        if (is_callable($value) && self::isMacro($key)) {
            return $value();
        }

        return $value;
    }

    /**
     * Checks if a configuration key is a macro
     *
     * @param string $key The configuration key to check
     * @return bool True if key is a macro, false otherwise
     */
    public static function isMacro(string $key): bool
    {
        return array_key_exists($key, self::$macros);
    }

    /**
     * Resolves all macros in the configuration recursively
     *
     * @return array All configuration with macros resolved
     */
    public static function resolveAll(): array
    {
        return self::resolveArray(self::$items);
    }

    /**
     * Recursively resolves macros in an array
     *
     * @param array $array The array to resolve
     * @return array The resolved array
     */
    protected static function resolveArray(array $array): array
    {
        $resolved = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $resolved[$key] = self::resolveArray($value);
            } elseif (is_callable($value) && self::isMacroValue($value)) {
                $resolved[$key] = $value();
            } else {
                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }

    /**
     * Checks if a value is a registered macro
     *
     * @param mixed $value The value to check
     * @return bool True if value is a macro, false otherwise
     */
    protected static function isMacroValue(mixed $value): bool
    {
        foreach (self::$macros as $macro) {
            if ($value === $macro) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets a nested value using dot notation
     *
     * @param string $key The dot-notation key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The nested value or default
     */
    protected static function getNestedValue(string $key, mixed $default): mixed
    {
        $parts = explode('.', $key);
        $value = self::$items;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Sets a nested value using dot notation
     *
     * @param string $key The dot-notation key
     * @param mixed $value The value to set
     */
    protected static function setNestedValue(string $key, mixed $value): void
    {
        $parts = explode('.', $key);
        $current = &self::$items;

        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $current[$part] = $value;
            } else {
                if (!isset($current[$part]) || !is_array($current[$part])) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
        }
    }

    /**
     * Removes a nested value using dot notation
     *
     * @param string $key The dot-notation key
     */
    protected static function forgetNestedValue(string $key): void
    {
        $parts = explode('.', $key);
        $current = &self::$items;

        for ($i = 0; $i < count($parts) - 1; $i++) {
            $part = $parts[$i];

            if (!is_array($current) || !array_key_exists($part, $current)) {
                return; // Path doesn't exist
            }

            $current = &$current[$part];
        }

        if (is_array($current)) {
            unset($current[end($parts)]);
        }
    }

    /**
     * Caches all configuration data to a file
     *
     * Note: Macros (callables) cannot be cached and will be excluded.
     *
     * @param string $cachePath Path to the cache file
     * @return bool True if cache was successfully created
     * @throws RuntimeException If unable to write cache file
     */
    public static function cache(string $cachePath): bool
    {
        $cacheDir = dirname($cachePath);

        if (!is_dir($cacheDir)) {
            if (!mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
                throw new RuntimeException("Unable to create cache directory: {$cacheDir}");
            }
        }

        if (!is_writable($cacheDir)) {
            throw new RuntimeException("Cache directory is not writable: {$cacheDir}");
        }

        // Remove callables from items before caching (they can't be serialized with var_export)
        $cacheableItems = self::removeCachableCallables(self::$items);

        $data = [
            'items' => $cacheableItems,
            'loadedPaths' => self::$loadedPaths,
            'timestamp' => time(),
        ];

        $content = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL;
        $content .= '// Configuration cache generated at ' . date('Y-m-d H:i:s') . PHP_EOL;
        $content .= '// Do not modify this file manually' . PHP_EOL;
        $content .= '// Note: Macros (callables) are not cached' . PHP_EOL . PHP_EOL;
        $content .= 'return ' . var_export($data, true) . ';' . PHP_EOL;

        $result = file_put_contents($cachePath, $content, LOCK_EX);

        if ($result === false) {
            throw new RuntimeException("Failed to write cache file: {$cachePath}");
        }

        return true;
    }

    /**
     * Recursively removes callables from array for caching
     *
     * @param array $array The array to process
     * @return array Array without callables
     */
    protected static function removeCachableCallables(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::removeCachableCallables($value);
            } elseif (!is_callable($value)) {
                $result[$key] = $value;
            }
            // Skip callables - they can't be cached
        }

        return $result;
    }

    /**
     * Loads configuration from a cached file
     *
     * Note: Macros are not restored from cache.
     *
     * @param string $cachePath Path to the cache file
     * @return bool True if cache was successfully loaded, false if cache doesn't exist
     * @throws RuntimeException If cache file is corrupted or invalid
     */
    public static function loadCached(string $cachePath): bool
    {
        if (!file_exists($cachePath)) {
            return false;
        }

        if (!is_readable($cachePath)) {
            throw new RuntimeException("Cache file is not readable: {$cachePath}");
        }

        $data = require $cachePath;

        if (!is_array($data) || !isset($data['items']) || !isset($data['loadedPaths'])) {
            throw new RuntimeException("Cache file is corrupted or invalid: {$cachePath}");
        }

        self::$items = $data['items'];
        self::$loadedPaths = $data['loadedPaths'];
        self::$loadedFromCache = true;

        return true;
    }

    /**
     * Checks if configuration was loaded from cache
     *
     * @return bool True if loaded from cache, false otherwise
     */
    public static function isLoadedFromCache(): bool
    {
        return self::$loadedFromCache;
    }

    /**
     * Checks if a cache file exists
     *
     * @param string $cachePath Path to the cache file
     * @return bool True if cache file exists and is readable
     */
    public static function isCached(string $cachePath): bool
    {
        return file_exists($cachePath) && is_readable($cachePath);
    }

    /**
     * Clears the cache file
     *
     * @param string $cachePath Path to the cache file
     * @return bool True if cache was deleted or doesn't exist, false otherwise
     */
    public static function clearCache(string $cachePath): bool
    {
        if (!file_exists($cachePath)) {
            return true;
        }

        return @unlink($cachePath);
    }

    /**
     * Gets cache metadata
     *
     * @param string $cachePath Path to the cache file
     * @return array|null Array with 'timestamp' and 'size' keys, or null if cache doesn't exist
     */
    public static function getCacheInfo(string $cachePath): ?array
    {
        if (!file_exists($cachePath)) {
            return null;
        }

        $data = require $cachePath;

        if (!is_array($data)) {
            return null;
        }

        return [
            'timestamp' => $data['timestamp'] ?? null,
            'size' => filesize($cachePath),
            'created_at' => $data['timestamp'] ? date('Y-m-d H:i:s', $data['timestamp']) : null,
        ];
    }

    /**
     * Locks the configuration to prevent modifications
     *
     * Once locked, any attempt to modify configuration will throw an exception.
     * This is useful for ensuring configuration immutability after initialization.
     */
    public static function lock(): void
    {
        self::$locked = true;
    }

    /**
     * Unlocks the configuration to allow modifications
     *
     * Use with caution - this should primarily be used in testing scenarios.
     */
    public static function unlock(): void
    {
        self::$locked = false;
    }

    /**
     * Checks if configuration is currently locked
     *
     * @return bool True if locked, false otherwise
     */
    public static function isLocked(): bool
    {
        return self::$locked;
    }

    /**
     * Ensures configuration is not locked, throws exception if it is
     *
     * @throws RuntimeException If configuration is locked
     */
    protected static function ensureNotLocked(): void
    {
        if (self::$locked) {
            throw new RuntimeException("Cannot modify configuration: Configuration is locked");
        }
    }
}
