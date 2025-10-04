<?php declare(strict_types=1);

namespace Core\DebugToolbar;

/**
 * Цветовая палитра для Debug Toolbar
 * 
 * Централизованное хранилище цветов Material Design,
 * используемых в коллекторах Debug Toolbar.
 */
class ColorPalette
{
    // Основные цвета статусов
    public const SUCCESS = '#66bb6a';      // Material Design Green 400
    public const WARNING = '#ffa726';      // Material Design Orange 400
    public const ERROR = '#ef5350';        // Material Design Red 400
    public const INFO = '#2196f3';         // Material Design Blue 400
    public const CRITICAL = '#c62828';     // Material Design Red 800
    
    // Дополнительные цвета
    public const PRIMARY = '#1976d2';      // Material Design Blue 700
    public const SECONDARY = '#9c27b0';    // Material Design Purple 600
    public const ACCENT = '#ff9800';       // Material Design Orange 500
    public const LIGHT = '#42a5f5';        // Material Design Blue 400
    public const DARK = '#37474f';         // Material Design Blue Grey 800
    
    // Нейтральные цвета
    public const GREY = '#757575';         // Material Design Grey 600
    public const GREY_LIGHT = '#e0e0e0';   // Material Design Grey 300
    public const GREY_DARK = '#424242';    // Material Design Grey 800
    
    // HTTP методы
    public const HTTP_GET = '#4caf50';     // Green 500
    public const HTTP_POST = '#2196f3';    // Blue 500
    public const HTTP_PUT = '#ff9800';     // Orange 500
    public const HTTP_PATCH = '#9c27b0';   // Purple 600
    public const HTTP_DELETE = '#f44336';  // Red 500
    public const HTTP_OPTIONS = '#607d8b'; // Blue Grey 500
    public const HTTP_HEAD = '#795548';    // Brown 500
    
    // HTTP статусы
    public const HTTP_SUCCESS_COLOR = '#66bb6a';  // 2xx - Green
    public const HTTP_REDIRECT_COLOR = '#42a5f5'; // 3xx - Blue
    public const HTTP_CLIENT_ERROR_COLOR = '#ffa726'; // 4xx - Orange
    public const HTTP_SERVER_ERROR_COLOR = '#ef5350'; // 5xx - Red
    
    // Уровни логов
    public const LOG_DEBUG = '#78909c';    // Blue Grey 400
    public const LOG_INFO = '#42a5f5';     // Blue 400
    public const LOG_WARNING = '#ffa726';  // Orange 400
    public const LOG_ERROR = '#ef5350';    // Red 400
    public const LOG_CRITICAL = '#c62828'; // Red 800
    
    // Cache операции
    public const CACHE_HIT = '#66bb6a';    // Green 400
    public const CACHE_MISS = '#ffa726';   // Orange 400
    public const CACHE_WRITE = '#42a5f5';  // Blue 400
    public const CACHE_DELETE = '#ef5350'; // Red 400
    
    /**
     * Получить цвет для HTTP метода
     * 
     * @param string $method HTTP метод (GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD)
     * @return string Hex-код цвета
     */
    public static function getHttpMethodColor(string $method): string
    {
        return match (strtoupper($method)) {
            'GET' => self::HTTP_GET,
            'POST' => self::HTTP_POST,
            'PUT' => self::HTTP_PUT,
            'PATCH' => self::HTTP_PATCH,
            'DELETE' => self::HTTP_DELETE,
            'OPTIONS' => self::HTTP_OPTIONS,
            'HEAD' => self::HTTP_HEAD,
            default => self::GREY,
        };
    }
    
    /**
     * Получить цвет для HTTP статус кода
     * 
     * @param int $statusCode HTTP статус код
     * @return string Hex-код цвета
     */
    public static function getHttpStatusColor(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 200 && $statusCode < 300 => self::HTTP_SUCCESS_COLOR,
            $statusCode >= 300 && $statusCode < 400 => self::HTTP_REDIRECT_COLOR,
            $statusCode >= 400 && $statusCode < 500 => self::HTTP_CLIENT_ERROR_COLOR,
            $statusCode >= 500 => self::HTTP_SERVER_ERROR_COLOR,
            default => self::GREY,
        };
    }
    
    /**
     * Получить цвет для уровня лога
     * 
     * @param string $level Уровень лога (debug, info, warning, error, critical)
     * @return string Hex-код цвета
     */
    public static function getLogLevelColor(string $level): string
    {
        return match (strtolower($level)) {
            'debug' => self::LOG_DEBUG,
            'info' => self::LOG_INFO,
            'warning' => self::LOG_WARNING,
            'error' => self::LOG_ERROR,
            'critical' => self::LOG_CRITICAL,
            default => self::GREY,
        };
    }
    
    /**
     * Получить цвет для cache операции
     * 
     * @param string $operation Тип операции (hit, miss, write, delete)
     * @return string Hex-код цвета
     */
    public static function getCacheOperationColor(string $operation): string
    {
        return match (strtolower($operation)) {
            'hit' => self::CACHE_HIT,
            'miss' => self::CACHE_MISS,
            'write' => self::CACHE_WRITE,
            'delete' => self::CACHE_DELETE,
            default => self::GREY,
        };
    }
    
    /**
     * Получить цвет по порогам (зелёный/оранжевый/красный)
     * 
     * @param float $value Текущее значение
     * @param float $warning Порог предупреждения
     * @param float $critical Критический порог
     * @return string Hex-код цвета
     */
    public static function getThresholdColor(float $value, float $warning, float $critical): string
    {
        if ($value >= $critical) {
            return self::ERROR;
        }
        if ($value >= $warning) {
            return self::WARNING;
        }
        return self::SUCCESS;
    }
    
    /**
     * Получить цвет для времени выполнения (быстро/средне/медленно)
     * 
     * @param float $timeMs Время в миллисекундах
     * @param float $fast Порог быстрого выполнения (по умолчанию 100ms)
     * @param float $medium Порог среднего выполнения (по умолчанию 500ms)
     * @return string Hex-код цвета
     */
    public static function getTimeColor(float $timeMs, float $fast = 100, float $medium = 500): string
    {
        if ($timeMs < $fast) {
            return self::SUCCESS;
        }
        if ($timeMs < $medium) {
            return self::WARNING;
        }
        return self::ERROR;
    }
}

