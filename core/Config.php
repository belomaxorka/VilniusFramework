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

    /**
     * Loads configuration files from the specified directory
     *
     * @param string $path Path to the directory containing config files
     * @throws InvalidArgumentException If path doesn't exist or is not a directory
     * @throws RuntimeException If glob pattern fails
     */
    public static function load(string $path): void
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

        $pattern = $realPath . '/*.php';
        $files = glob($pattern);

        if ($files === false) {
            throw new RuntimeException("Error searching for files with pattern: {$pattern}");
        }

        foreach ($files as $file) {
            self::loadFile($file);
        }

        self::$loadedPaths[] = $realPath;
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
            self::$items[$key] = array_merge_recursive(self::$items[$key], $config);
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

        return self::$items[$key] ?? $default;
    }

    /**
     * Sets a configuration value with dot notation support
     *
     * @param string $key The configuration key (supports dot notation)
     * @param mixed $value The value to set
     */
    public static function set(string $key, mixed $value): void
    {
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
     */
    public static function forget(string $key): void
    {
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
     */
    public static function clear(): void
    {
        self::$items = [];
        self::$loadedPaths = [];
        self::$loadedFromCache = false;
        self::$macros = [];
    }

    /**
     * Pushes a value onto the end of an array configuration value
     *
     * @param string $key The configuration key (supports dot notation)
     * @param mixed $value The value to push
     * @throws RuntimeException If the configuration value is not an array
     */
    public static function push(string $key, mixed $value): void
    {
        $array = self::get($key, []);

        if (!is_array($array)) {
            throw new RuntimeException("Configuration value at '{$key}' is not an array");
        }

        $array[] = $value;
        self::set($key, $array);
    }

    /**
     * Prepends a value to the beginning of an array configuration value
     *
     * @param string $key The configuration key (supports dot notation)
     * @param mixed $value The value to prepend
     * @throws RuntimeException If the configuration value is not an array
     */
    public static function prepend(string $key, mixed $value): void
    {
        $array = self::get($key, []);

        if (!is_array($array)) {
            throw new RuntimeException("Configuration value at '{$key}' is not an array");
        }

        array_unshift($array, $value);
        self::set($key, $array);
    }

    /**
     * Gets a value from configuration and removes it
     *
     * @param string $key The configuration key (supports dot notation)
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The pulled value or default
     */
    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::forget($key);

        return $value;
    }

    /**
     * Registers a macro (callable) for lazy evaluation
     *
     * @param string $key The configuration key (supports dot notation)
     * @param callable $callback The callback to execute when resolved
     */
    public static function macro(string $key, callable $callback): void
    {
        self::$macros[$key] = $callback;
        self::set($key, $callback);
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

        $data = [
            'items' => self::$items,
            'loadedPaths' => self::$loadedPaths,
            'macros' => self::$macros,
            'timestamp' => time(),
        ];

        $content = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL;
        $content .= '// Configuration cache generated at ' . date('Y-m-d H:i:s') . PHP_EOL;
        $content .= '// Do not modify this file manually' . PHP_EOL . PHP_EOL;
        $content .= 'return ' . var_export($data, true) . ';' . PHP_EOL;

        $result = file_put_contents($cachePath, $content, LOCK_EX);

        if ($result === false) {
            throw new RuntimeException("Failed to write cache file: {$cachePath}");
        }

        return true;
    }

    /**
     * Loads configuration from a cached file
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
        self::$macros = $data['macros'] ?? [];
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
}
