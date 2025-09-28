<?php declare(strict_types=1);

namespace Core;

class Config
{
    protected static array $items = [];
    protected static array $loadedPaths = [];

    /**
     * Загружает конфигурационные файлы из указанной директории
     */
    public static function load(string $path): void
    {
        $realPath = realpath($path);

        if ($realPath === false) {
            throw new \InvalidArgumentException("Путь не существует: {$path}");
        }

        if (!is_dir($realPath)) {
            throw new \InvalidArgumentException("Указанный путь не является директорией: {$path}");
        }

        // Предотвращаем повторную загрузку того же пути
        if (in_array($realPath, self::$loadedPaths, true)) {
            return;
        }

        $pattern = $realPath . '/*.php';
        $files = glob($pattern);

        if ($files === false) {
            throw new \RuntimeException("Ошибка при поиске файлов по паттерну: {$pattern}");
        }

        foreach ($files as $file) {
            self::loadFile($file);
        }

        self::$loadedPaths[] = $realPath;
    }

    /**
     * Загружает отдельный конфигурационный файл
     */
    public static function loadFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Файл не найден: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new \InvalidArgumentException("Файл недоступен для чтения: {$filePath}");
        }

        $key = basename($filePath, '.php');

        // Проверяем, что файл возвращает массив
        $config = require $filePath;

        if (!is_array($config)) {
            throw new \RuntimeException("Конфигурационный файл должен возвращать массив: {$filePath}");
        }

        // Мерджим конфигурации, если ключ уже существует
        if (isset(self::$items[$key]) && is_array(self::$items[$key])) {
            self::$items[$key] = array_merge_recursive(self::$items[$key], $config);
        } else {
            self::$items[$key] = $config;
        }
    }

    /**
     * Получает значение конфигурации по ключу с поддержкой точечной нотации
     */
    public static function get(string $key, $default = null)
    {
        if (str_contains($key, '.')) {
            return self::getNestedValue($key, $default);
        }

        return self::$items[$key] ?? $default;
    }

    /**
     * Устанавливает значение конфигурации с поддержкой точечной нотации
     */
    public static function set(string $key, $value): void
    {
        if (str_contains($key, '.')) {
            self::setNestedValue($key, $value);
        } else {
            self::$items[$key] = $value;
        }
    }

    /**
     * Проверяет существование ключа конфигурации
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
     * Удаляет ключ из конфигурации
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
     * Возвращает все конфигурационные данные
     */
    public static function all(): array
    {
        return self::$items;
    }

    /**
     * Очищает все конфигурационные данные
     */
    public static function clear(): void
    {
        self::$items = [];
        self::$loadedPaths = [];
    }

    /**
     * Получает вложенное значение по точечной нотации
     */
    protected static function getNestedValue(string $key, $default)
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
     * Устанавливает вложенное значение по точечной нотации
     */
    protected static function setNestedValue(string $key, $value): void
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
     * Удаляет вложенное значение по точечной нотации
     */
    protected static function forgetNestedValue(string $key): void
    {
        $parts = explode('.', $key);
        $current = &self::$items;

        for ($i = 0; $i < count($parts) - 1; $i++) {
            $part = $parts[$i];

            if (!is_array($current) || !array_key_exists($part, $current)) {
                return; // Путь не существует
            }

            $current = &$current[$part];
        }

        if (is_array($current)) {
            unset($current[end($parts)]);
        }
    }
}
