<?php declare(strict_types=1);

namespace Core;

use InvalidArgumentException;
use RuntimeException;

class Config
{
    protected static array $items = [];
    protected static array $loadedPaths = [];

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
}
