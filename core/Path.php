<?php declare(strict_types=1);

namespace Core;

/**
 * Path Utilities
 * 
 * Утилиты для работы с путями к файлам и директориям
 */
class Path
{
    /**
     * Нормализовать путь (заменить \ на /)
     * 
     * @param string $path Путь для нормализации
     * @return string Нормализованный путь
     */
    public static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Получить относительный путь (убрать ROOT)
     * 
     * @param string $path Абсолютный путь
     * @return string Относительный путь
     */
    public static function relative(string $path): string
    {
        $normalized = self::normalize($path);
        $root = self::normalize(ROOT);
        
        // Убираем ROOT из начала пути
        if (str_starts_with($normalized, $root)) {
            return substr($normalized, strlen($root) + 1); // +1 для слеша
        }
        
        return $normalized;
    }

    /**
     * Объединить части пути
     * 
     * @param string ...$parts Части пути
     * @return string Объединенный путь
     */
    public static function join(string ...$parts): string
    {
        $path = implode('/', $parts);
        return self::normalize($path);
    }

    /**
     * Получить расширение файла
     * 
     * @param string $path Путь к файлу
     * @return string Расширение (без точки)
     */
    public static function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Получить имя файла без расширения
     * 
     * @param string $path Путь к файлу
     * @return string Имя файла
     */
    public static function filename(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Получить имя файла с расширением
     * 
     * @param string $path Путь к файлу
     * @return string Имя файла с расширением
     */
    public static function basename(string $path): string
    {
        return basename($path);
    }

    /**
     * Получить директорию из пути
     * 
     * @param string $path Путь к файлу
     * @return string Путь к директории
     */
    public static function dirname(string $path): string
    {
        return self::normalize(dirname($path));
    }

    /**
     * Проверить, является ли путь абсолютным
     * 
     * @param string $path Путь для проверки
     * @return bool
     */
    public static function isAbsolute(string $path): bool
    {
        // Windows: C:\ или C:/
        if (preg_match('/^[a-zA-Z]:[\/\\\\]/', $path)) {
            return true;
        }
        
        // Unix: /
        if (str_starts_with($path, '/')) {
            return true;
        }
        
        return false;
    }

    /**
     * Проверить, существует ли файл или директория
     * 
     * @param string $path Путь для проверки
     * @return bool
     */
    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Проверить, является ли путь директорией
     * 
     * @param string $path Путь для проверки
     * @return bool
     */
    public static function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Проверить, является ли путь файлом
     * 
     * @param string $path Путь для проверки
     * @return bool
     */
    public static function isFile(string $path): bool
    {
        return is_file($path);
    }
}

