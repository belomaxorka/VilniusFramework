<?php declare(strict_types=1);

namespace Core\Services;

use Core\Contracts\ConfigInterface;
use ArrayAccess;
use Countable;
use InvalidArgumentException;
use RuntimeException;

/**
 * Configuration Repository
 * 
 * Instance-based реализация для работы с конфигурацией
 */
class ConfigRepository implements ConfigInterface, ArrayAccess, Countable
{
    protected array $items = [];
    protected array $loadedPaths = [];
    protected array $macros = [];
    protected bool $locked = false;
    protected array $resolvingMacros = [];
    protected array $memoizedMacros = [];
    protected array $memoizedValues = [];
    protected array $realpathCache = [];
    protected array $pathExplodeCache = [];

    /**
     * Cached realpath() for better performance
     */
    protected function cachedRealpath(string $path): string|false
    {
        if (!isset($this->realpathCache[$path])) {
            $this->realpathCache[$path] = realpath($path);
        }
        return $this->realpathCache[$path];
    }

    /**
     * Cached explode() for dot notation paths
     */
    protected function explodePath(string $key): array
    {
        if (!isset($this->pathExplodeCache[$key])) {
            $this->pathExplodeCache[$key] = explode('.', $key);
        }
        return $this->pathExplodeCache[$key];
    }

    public function load(string $path, ?string $environment = null, bool $recursive = false): void
    {
        $realPath = $this->cachedRealpath($path);

        if ($realPath === false) {
            throw new InvalidArgumentException("Path does not exist: {$path}");
        }

        if (!is_dir($realPath)) {
            throw new InvalidArgumentException("Specified path is not a directory: {$path}");
        }

        if (in_array($realPath, $this->loadedPaths, true)) {
            return;
        }

        if ($recursive) {
            $this->loadRecursive($realPath);
        } else {
            $pattern = $realPath . '/*.php';
            $files = glob($pattern);

            if ($files === false) {
                throw new RuntimeException("Error searching for files with pattern: {$pattern}");
            }

            foreach ($files as $file) {
                $this->loadFile($file);
            }
        }

        $this->loadedPaths[] = $realPath;

        if ($environment !== null) {
            $this->loadEnvironmentConfigs($realPath, $environment);
        }
    }

    protected function loadRecursive(string $basePath, string $namespace = ''): void
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
                $newNamespace = $namespace === '' ? $item : $namespace . '.' . $item;
                $this->loadRecursive($fullPath, $newNamespace);
            } elseif (is_file($fullPath) && pathinfo($fullPath, PATHINFO_EXTENSION) === 'php') {
                $filename = pathinfo($fullPath, PATHINFO_FILENAME);
                $key = $namespace === '' ? $filename : $namespace . '.' . $filename;

                $config = require $fullPath;

                if (!is_array($config)) {
                    throw new RuntimeException("Configuration file must return an array: {$fullPath}");
                }

                $this->setByPath($key, $config);
            }
        }
    }

    protected function setByPath(string $path, array $value): void
    {
        $parts = $this->explodePath($path);
        $current = &$this->items;

        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                if (isset($current[$part]) && is_array($current[$part])) {
                    $current[$part] = $this->mergeConfigs($current[$part], $value);
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

    protected function mergeConfigs(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                if (array_is_list($base[$key]) && array_is_list($value)) {
                    $base[$key] = array_merge($base[$key], $value);
                } else {
                    $base[$key] = $this->mergeConfigs($base[$key], $value);
                }
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    protected function loadEnvironmentConfigs(string $basePath, string $environment): void
    {
        $envDir = $basePath . DIRECTORY_SEPARATOR . $environment;
        $suffixPattern = $basePath . DIRECTORY_SEPARATOR . '*.' . $environment . '.php';
        
        if (is_dir($envDir)) {
            $dirPattern = $envDir . '/*.php';
            $dirFiles = glob($dirPattern);
            
            if ($dirFiles !== false) {
                foreach ($dirFiles as $file) {
                    $this->loadFile($file);
                }
            }
        }

        $suffixFiles = glob($suffixPattern);
        if ($suffixFiles !== false) {
            foreach ($suffixFiles as $file) {
                $basename = basename($file, '.' . $environment . '.php');
                $this->loadFileWithKey($file, $basename);
            }
        }
    }

    protected function loadFileWithKey(string $filePath, string $key): void
    {
        if (!is_readable($filePath)) {
            throw new InvalidArgumentException("File not found or not readable: {$filePath}");
        }

        $config = require $filePath;

        if (!is_array($config)) {
            throw new RuntimeException("Configuration file must return an array: {$filePath}");
        }

        if (isset($this->items[$key]) && is_array($this->items[$key])) {
            $this->items[$key] = $this->mergeConfigs($this->items[$key], $config);
        } else {
            $this->items[$key] = $config;
        }
    }

    public function loadFile(string $filePath): void
    {
        if (!is_readable($filePath)) {
            throw new InvalidArgumentException("File not found or not readable: {$filePath}");
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $key = basename($filePath, '.' . $extension);

        $config = match ($extension) {
            'php' => require $filePath,
            'json' => $this->loadJsonFile($filePath),
            default => throw new RuntimeException("Unsupported file format: {$extension}")
        };

        if (!is_array($config)) {
            throw new RuntimeException("Configuration file must contain an array: {$filePath}");
        }

        if (isset($this->items[$key]) && is_array($this->items[$key])) {
            $this->items[$key] = $this->mergeConfigs($this->items[$key], $config);
        } else {
            $this->items[$key] = $config;
        }
    }

    protected function loadJsonFile(string $filePath): array
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new RuntimeException("Failed to read JSON file: {$filePath}");
        }

        $config = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON in file {$filePath}: " . json_last_error_msg());
        }

        if (!is_array($config)) {
            throw new RuntimeException("JSON file must contain an object/array: {$filePath}");
        }

        return $config;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (str_contains($key, '.')) {
            return $this->getNestedValue($key, $default);
        }

        return array_key_exists($key, $this->items) ? $this->items[$key] : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->ensureNotLocked();

        if (str_contains($key, '.')) {
            $this->setNestedValue($key, $value);
        } else {
            $this->items[$key] = $value;
        }
    }

    public function has(string $key): bool
    {
        if (str_contains($key, '.')) {
            $parts = $this->explodePath($key);
            $value = $this->items;

            foreach ($parts as $part) {
                if (!is_array($value) || !array_key_exists($part, $value)) {
                    return false;
                }
                $value = $value[$part];
            }

            return true;
        }

        return array_key_exists($key, $this->items);
    }

    public function forget(string $key): void
    {
        $this->ensureNotLocked();

        if (str_contains($key, '.')) {
            $this->forgetNestedValue($key);
        } else {
            unset($this->items[$key]);
        }
    }

    public function all(): array
    {
        return $this->items;
    }

    public function clear(): void
    {
        $this->items = [];
        $this->loadedPaths = [];
        $this->macros = [];
        $this->locked = false;
        $this->resolvingMacros = [];
        $this->memoizedMacros = [];
        $this->memoizedValues = [];
        $this->realpathCache = [];
        $this->pathExplodeCache = [];
    }

    public function getRequired(string $key): mixed
    {
        if (!$this->has($key)) {
            throw new RuntimeException("Required configuration key missing: {$key}");
        }

        return $this->get($key);
    }

    public function getMany(array $keys, mixed $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function push(string $key, mixed $value): void
    {
        $this->ensureNotLocked();

        $array = $this->get($key, []);

        if (!is_array($array)) {
            throw new RuntimeException("Configuration value at '{$key}' is not an array");
        }

        $array[] = $value;

        $wasLocked = $this->locked;
        $this->locked = false;
        $this->set($key, $array);
        $this->locked = $wasLocked;
    }

    public function resolve(string $key, mixed $default = null): mixed
    {
        if (isset($this->resolvingMacros[$key])) {
            throw new RuntimeException("Circular macro reference detected: {$key}");
        }

        $value = $this->get($key, $default);

        if (is_callable($value) && isset($this->macros[$key])) {
            $this->resolvingMacros[$key] = true;
            try {
                $result = $value();
            } finally {
                unset($this->resolvingMacros[$key]);
            }
            return $result;
        }

        return $value;
    }

    public function lock(): void
    {
        $this->locked = true;
    }

    public function unlock(): void
    {
        $this->locked = false;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    protected function ensureNotLocked(): void
    {
        if ($this->locked) {
            throw new RuntimeException("Cannot modify configuration: Configuration is locked");
        }
    }

    protected function getNestedValue(string $key, mixed $default): mixed
    {
        $parts = $this->explodePath($key);
        $value = $this->items;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    protected function setNestedValue(string $key, mixed $value): void
    {
        $parts = $this->explodePath($key);
        $current = &$this->items;

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

    protected function forgetNestedValue(string $key): void
    {
        $parts = $this->explodePath($key);
        $current = &$this->items;

        for ($i = 0; $i < count($parts) - 1; $i++) {
            $part = $parts[$i];

            if (!is_array($current) || !array_key_exists($part, $current)) {
                return;
            }

            $current = &$current[$part];
        }

        if (is_array($current)) {
            unset($current[end($parts)]);
        }
    }

    // ArrayAccess Implementation
    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string)$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string)$offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set((string)$offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->forget((string)$offset);
    }

    // Countable Implementation
    public function count(): int
    {
        return count($this->items);
    }

    // Методы для кеширования

    protected bool $loadedFromCache = false;

    public function cache(string $cachePath): bool
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

        // Remove callables from items before caching
        $cacheableItems = $this->removeCachableCallables($this->items);

        // Collect file modification times
        $fileModTimes = $this->collectFileModificationTimes($this->loadedPaths);

        $data = [
            'items' => $cacheableItems,
            'loadedPaths' => $this->loadedPaths,
            'fileModTimes' => $fileModTimes,
            'timestamp' => time(),
        ];

        $serialized = serialize($data);
        
        $content = '<?php declare(strict_types=1);' . PHP_EOL . PHP_EOL;
        $content .= '// Configuration cache generated at ' . date('Y-m-d H:i:s') . PHP_EOL;
        $content .= '// Do not modify this file manually' . PHP_EOL;
        $content .= 'return unserialize(' . var_export($serialized, true) . ');' . PHP_EOL;

        $result = file_put_contents($cachePath, $content, LOCK_EX);

        return $result !== false;
    }

    public function loadCached(string $cachePath): bool
    {
        if (!is_readable($cachePath)) {
            return false;
        }

        $data = require $cachePath;

        if (!is_array($data) || !isset($data['items']) || !isset($data['loadedPaths'])) {
            throw new RuntimeException("Cache file is corrupted or invalid: {$cachePath}");
        }

        // Check if cache is still valid
        if (isset($data['fileModTimes']) && !$this->isCacheValid($data['fileModTimes'])) {
            return false;
        }

        $this->items = $data['items'];
        $this->loadedPaths = $data['loadedPaths'];
        $this->loadedFromCache = true;

        return true;
    }

    public function isLoadedFromCache(): bool
    {
        return $this->loadedFromCache;
    }

    public function isCached(string $cachePath): bool
    {
        return is_readable($cachePath);
    }

    public function clearCache(string $cachePath): bool
    {
        if (!file_exists($cachePath)) {
            return true;
        }

        return @unlink($cachePath);
    }

    public function getCacheInfo(string $cachePath): ?array
    {
        if (!is_readable($cachePath)) {
            return null;
        }

        $mtime = @filemtime($cachePath);
        $size = @filesize($cachePath);

        return [
            'timestamp' => $mtime ?: null,
            'size' => $size ?: 0,
            'created_at' => $mtime ? date('Y-m-d H:i:s', $mtime) : null,
        ];
    }

    protected function removeCachableCallables(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->removeCachableCallables($value);
            } elseif (!is_callable($value)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    protected function collectFileModificationTimes(array $loadedPaths): array
    {
        $modTimes = [];

        foreach ($loadedPaths as $path) {
            if (is_dir($path)) {
                $files = glob($path . '/*.php');
                if ($files !== false) {
                    foreach ($files as $file) {
                        $modTimes[$file] = @filemtime($file) ?: 0;
                    }
                }
            } elseif (is_file($path)) {
                $modTimes[$path] = @filemtime($path) ?: 0;
            }
        }

        return $modTimes;
    }

    protected function isCacheValid(array $cachedModTimes): bool
    {
        foreach ($cachedModTimes as $file => $cachedTime) {
            if (!file_exists($file)) {
                return false;
            }

            $currentTime = @filemtime($file);
            if ($currentTime === false || $currentTime > $cachedTime) {
                return false;
            }
        }

        return true;
    }
}

