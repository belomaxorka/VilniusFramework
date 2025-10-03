<?php declare(strict_types=1);

namespace Core\Cache\Drivers;

use Core\Cache\AbstractCacheDriver;
use Core\Cache\Exceptions\CacheException;
use DateInterval;

/**
 * File-based cache driver
 */
class FileDriver extends AbstractCacheDriver
{
    protected string $path;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->path = $config['path'] ?? CACHE_DIR . '/data';

        // Создаем директорию, если не существует
        if (!is_dir($this->path)) {
            if (!mkdir($this->path, 0755, true) && !is_dir($this->path)) {
                throw new CacheException("Failed to create cache directory: {$this->path}");
            }
        }

        if (!is_writable($this->path)) {
            throw new CacheException("Cache directory is not writable: {$this->path}");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $startTime = microtime(true);
        $originalKey = $key;
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            $this->logGet($originalKey, false, null, $startTime);
            return $default;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            $this->logGet($originalKey, false, null, $startTime);
            return $default;
        }

        $data = $this->unserialize($content);

        // Проверяем срок годности
        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            $this->delete($key);
            $this->logGet($originalKey, false, null, $startTime);
            return $default;
        }

        $value = $data['value'] ?? $default;
        $this->logGet($originalKey, true, $value, $startTime);
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $startTime = microtime(true);
        $originalKey = $key;
        $file = $this->getFilePath($key);
        $ttl = $this->normalizeTtl($ttl);

        $data = [
            'value' => $value,
            'expires_at' => $ttl !== null ? time() + $ttl : null,
        ];

        $content = $this->serialize($data);

        // Создаем поддиректории если нужно
        $dir = dirname($file);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        // Атомарная запись через временный файл
        $tmpFile = $file . '.' . uniqid('tmp', true);
        if (@file_put_contents($tmpFile, $content, LOCK_EX) === false) {
            return false;
        }

        $result = @rename($tmpFile, $file);
        $this->logSet($originalKey, $value, $startTime);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $startTime = microtime(true);
        $originalKey = $key;
        $file = $this->getFilePath($key);

        if (file_exists($file)) {
            $result = @unlink($file);
            $this->logDelete($originalKey, $startTime);
            return $result;
        }

        $this->logDelete($originalKey, $startTime);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this->clearDirectory($this->path);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return false;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return false;
        }

        $data = $this->unserialize($content);

        // Проверяем срок годности
        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, int $value = 1): int|false
    {
        $current = $this->get($key, 0);

        if (!is_numeric($current)) {
            return false;
        }

        $new = (int)$current + $value;
        $this->set($key, $new);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, int $value = 1): int|false
    {
        return $this->increment($key, -$value);
    }

    /**
     * Получить путь к файлу кэша
     */
    protected function getFilePath(string $key): string
    {
        $key = $this->getKey($key);
        $hash = sha1($key);

        // Создаем структуру директорий для лучшего распределения
        $parts = [
            substr($hash, 0, 2),
            substr($hash, 2, 2),
        ];

        return $this->path . '/' . implode('/', $parts) . '/' . $hash;
    }

    /**
     * Очистить директорию рекурсивно
     */
    protected function clearDirectory(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        return true;
    }

    /**
     * Очистить просроченные файлы
     */
    public function gc(): int
    {
        $deleted = 0;
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($items as $item) {
            if ($item->isFile()) {
                $content = @file_get_contents($item->getPathname());
                if ($content !== false) {
                    $data = $this->unserialize($content);
                    if (isset($data['expires_at']) && $data['expires_at'] < time()) {
                        if (@unlink($item->getPathname())) {
                            $deleted++;
                        }
                    }
                }
            }
        }

        return $deleted;
    }
}

