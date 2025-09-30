<?php declare(strict_types=1);

namespace Core;

use ArrayAccess;
use Countable;

/**
 * Configuration management interface
 */
interface ConfigInterface extends ArrayAccess, Countable
{
    /**
     * Gets a configuration value by key
     *
     * @param string $key The configuration key (supports dot notation)
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The configuration value or default
     */
    public static function get(string $key, mixed $default = null): mixed;

    /**
     * Sets a configuration value
     *
     * @param string $key The configuration key (supports dot notation)
     * @param mixed $value The value to set
     * @throws \RuntimeException If configuration is locked
     */
    public static function set(string $key, mixed $value): void;

    /**
     * Checks if a configuration key exists
     *
     * @param string $key The configuration key to check
     * @return bool True if key exists, false otherwise
     */
    public static function has(string $key): bool;

    /**
     * Loads configuration files from directory
     *
     * @param string $path Path to the directory containing config files
     * @param string|null $environment Optional environment name
     * @param bool $recursive Whether to load recursively
     * @throws \InvalidArgumentException If path doesn't exist or is not a directory
     */
    public static function load(string $path, ?string $environment = null, bool $recursive = false): void;

    /**
     * Returns all configuration data
     *
     * @return array All configuration items
     */
    public static function all(): array;

    /**
     * Clears all configuration data
     */
    public static function clear(): void;
}
