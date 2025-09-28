<?php declare(strict_types=1);

namespace Core;

use Exception;
use RuntimeException;

final class HelperLoader
{
    private static ?self $instance = null;
    private array $loadedHelpers = [];
    private string $helpersPath;

    private function __construct()
    {
        $this->helpersPath = __DIR__ . '/helpers/';
    }

    private function __clone(): void
    {
    }

    /**
     * @throws Exception
     */
    public function __wakeup(): void
    {
        throw new Exception("Cannot unserialize singleton");
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Load helper file by name
     *
     * @param string $name Helper file name (without .php extension)
     * @return bool True if loaded successfully, false if already loaded
     */
    public function load(string $name): bool
    {
        if ($this->isLoaded($name)) {
            return false; // Already loaded
        }

        $filePath = $this->helpersPath . $name . '.php';
        if (!is_file($filePath)) {
            throw new RuntimeException("Helper file not found: {$filePath}");
        }

        require_once $filePath;
        $this->loadedHelpers[$name] = true;

        return true;
    }

    /**
     * Load multiple helper files at once
     *
     * @param array $names Array of helper names
     * @return bool True if all loaded successfully
     */
    public function loadMultiple(array $names): bool
    {
        $results = [];
        foreach ($names as $name) {
            $results[] = $this->load($name);
        }

        return !in_array(false, $results, true);
    }

    public function isLoaded(string $name): bool
    {
        return isset($this->loadedHelpers[$name]);
    }

    public function getLoaded(): array
    {
        return array_keys($this->loadedHelpers);
    }

    public function getAvailable(): array
    {
        $files = glob($this->helpersPath . '*.php');
        return array_map(fn($file) => basename($file, '.php'), $files);
    }

    public function reload(string $name): bool
    {
        unset($this->loadedHelpers[$name]);
        return $this->load($name);
    }

    public function reset(): self
    {
        $this->loadedHelpers = [];
        return $this;
    }

    public static function loadHelper(string $name): bool
    {
        return self::getInstance()->load($name);
    }

    public static function loadHelpers(array $names): bool
    {
        return self::getInstance()->loadMultiple($names);
    }

    public static function isHelperLoaded(string $name): bool
    {
        return self::getInstance()->isLoaded($name);
    }
}
