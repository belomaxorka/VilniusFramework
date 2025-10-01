<?php declare(strict_types=1);

namespace Core\Utils;

/**
 * Помощник для форматирования различных типов данных
 */
class FormatHelper
{
    /**
     * Форматировать байты в человекочитаемый формат
     * 
     * Преобразует количество байтов в удобный для чтения формат с единицами измерения.
     * 
     * @param int $bytes Количество байтов (может быть отрицательным)
     * @param int $precision Точность (количество знаков после запятой)
     * @return string Отформатированная строка (например, "1.50 MB")
     * 
     * @example
     * FormatHelper::formatBytes(1024)       // "1.00 KB"
     * FormatHelper::formatBytes(1536, 1)    // "1.5 KB"
     * FormatHelper::formatBytes(1048576)    // "1.00 MB"
     * FormatHelper::formatBytes(-1024)      // "1.00 KB" (abs)
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        // Обработка нулевого значения
        if ($bytes === 0) {
            return '0 B';
        }

        // Работаем с абсолютным значением для корректного форматирования
        $bytes = abs($bytes);
        
        // Единицы измерения (до петабайтов)
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        // Вычисляем степень 1024 для определения единицы
        $pow = floor(log($bytes) / log(1024));
        
        // Ограничиваем максимальную единицу
        $pow = min($pow, count($units) - 1);

        // Вычисляем значение в выбранной единице
        $value = $bytes / pow(1024, $pow);

        // Форматируем с заданной точностью
        return number_format($value, $precision) . ' ' . $units[$pow];
    }

    /**
     * Форматировать время в человекочитаемый формат
     * 
     * Преобразует время в миллисекундах в удобный формат с автоматическим
     * выбором единиц измерения (микросекунды, миллисекунды, секунды).
     * 
     * @param float $timeMs Время в миллисекундах
     * @param int $precision Точность (количество знаков после запятой)
     * @return string Отформатированная строка (например, "1.50 ms")
     * 
     * @example
     * FormatHelper::formatTime(0.5)    // "500.00 μs"
     * FormatHelper::formatTime(15.5)   // "15.50 ms"
     * FormatHelper::formatTime(1500)   // "1.50 s"
     */
    public static function formatTime(float $timeMs, int $precision = 2): string
    {
        if ($timeMs < 1) {
            // Меньше 1 миллисекунды - показываем в микросекундах
            return number_format($timeMs * 1000, $precision) . ' μs';
        } elseif ($timeMs < 1000) {
            // Меньше 1 секунды - показываем в миллисекундах
            return number_format($timeMs, $precision) . ' ms';
        } else {
            // Больше 1 секунды - показываем в секундах
            return number_format($timeMs / 1000, $precision) . ' s';
        }
    }

    /**
     * Форматировать число с разделителями тысяч
     * 
     * @param int|float $number Число для форматирования
     * @param int $decimals Количество знаков после запятой
     * @return string Отформатированное число
     * 
     * @example
     * FormatHelper::formatNumber(1234567)      // "1,234,567"
     * FormatHelper::formatNumber(1234.5678, 2) // "1,234.57"
     */
    public static function formatNumber(int|float $number, int $decimals = 0): string
    {
        return number_format($number, $decimals);
    }

    /**
     * Форматировать процент
     * 
     * @param float $value Значение (0-100)
     * @param int $decimals Количество знаков после запятой
     * @return string Отформатированный процент
     * 
     * @example
     * FormatHelper::formatPercent(75.5)      // "75.50%"
     * FormatHelper::formatPercent(75.5, 1)   // "75.5%"
     */
    public static function formatPercent(float $value, int $decimals = 2): string
    {
        return number_format($value, $decimals) . '%';
    }

    /**
     * Получить цвет по порогу
     * 
     * Возвращает цвет (зелёный/оранжевый/красный) на основе значения
     * и заданных порогов предупреждения и критического уровня.
     * 
     * @param float $value Текущее значение
     * @param float $warning Порог предупреждения
     * @param float $critical Критический порог
     * @return string Hex-код цвета
     * 
     * @example
     * FormatHelper::getColorByThreshold(50, 60, 80)  // "#66bb6a" (green)
     * FormatHelper::getColorByThreshold(70, 60, 80)  // "#ffa726" (orange)
     * FormatHelper::getColorByThreshold(85, 60, 80)  // "#ef5350" (red)
     */
    public static function getColorByThreshold(float $value, float $warning, float $critical): string
    {
        if ($value >= $critical) {
            return '#ef5350'; // Material Design Red 400
        }
        if ($value >= $warning) {
            return '#ffa726'; // Material Design Orange 400
        }
        return '#66bb6a'; // Material Design Green 400
    }
}

