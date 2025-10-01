<?php declare(strict_types=1);

namespace Core;

class MemoryProfiler
{
    private static array $snapshots = [];
    private static ?int $startMemory = null;

    /**
     * Начать профилирование памяти
     */
    public static function start(): void
    {
        if (!Environment::isDebug()) {
            return;
        }

        self::$startMemory = memory_get_usage(false);
        self::$snapshots = [];

        self::snapshot('start', 'Memory profiling started');
    }

    /**
     * Сделать снимок памяти
     */
    public static function snapshot(string $name, ?string $label = null): array
    {
        if (!Environment::isDebug()) {
            return [];
        }

        $current = memory_get_usage(false);
        $peak = memory_get_peak_usage(false);

        $snapshot = [
            'name' => $name,
            'label' => $label,
            'memory' => $current,
            'peak' => $peak,
            'diff' => 0,
            'diff_from_start' => 0,
            'timestamp' => microtime(true),
        ];

        // Вычисляем разницу с предыдущим снимком
        if (!empty(self::$snapshots)) {
            $previous = end(self::$snapshots);
            $snapshot['diff'] = $current - $previous['memory'];
        }

        // Разница от начала
        if (self::$startMemory !== null) {
            $snapshot['diff_from_start'] = $current - self::$startMemory;
        }

        self::$snapshots[] = $snapshot;

        return $snapshot;
    }

    /**
     * Получить текущее использование памяти
     * 
     * Возвращает реальное использование памяти на уровне системы (параметр false).
     * Это включает память, выделенную системой для PHP процесса, включая
     * внутренние буферы, кеши и накладные расходы.
     * 
     * Примечание: Значения будут выше, чем при использовании memory_get_usage(true),
     * так как учитываются внутренние структуры PHP. Это полезно для мониторинга
     * фактического потребления памяти на уровне операционной системы.
     * 
     * @return int Использование памяти в байтах (системное потребление)
     * @see memory_get_usage()
     */
    public static function current(): int
    {
        return memory_get_usage(false);
    }

    /**
     * Получить пиковое использование памяти
     * 
     * Возвращает максимальное (пиковое) использование памяти на уровне системы.
     * Это реальное количество памяти, которое было выделено системой для PHP
     * процесса в течение выполнения скрипта.
     * 
     * @return int Пиковое использование памяти в байтах (системное потребление)
     * @see memory_get_peak_usage()
     */
    public static function peak(): int
    {
        return memory_get_peak_usage(false);
    }

    /**
     * Получить все снимки
     */
    public static function getSnapshots(): array
    {
        return self::$snapshots;
    }

    /**
     * Получить количество снимков
     */
    public static function count(): int
    {
        return count(self::$snapshots);
    }

    /**
     * Очистить снимки
     */
    public static function clear(): void
    {
        self::$snapshots = [];
        self::$startMemory = null;
    }

    /**
     * Вывести профиль памяти
     */
    public static function dump(): void
    {
        if (!Environment::isDebug() || empty(self::$snapshots)) {
            return;
        }

        $current = memory_get_usage(false);
        $peak = memory_get_peak_usage(false);
        $limit = self::getMemoryLimit();

        $output = '<div style="background: #e3f2fd; border: 1px solid #2196f3; margin: 10px; padding: 15px; border-radius: 5px; font-family: monospace;">';
        $output .= '<h4 style="color: #1565c0; margin-top: 0;">💾 Memory Profile</h4>';

        // Общая информация
        $output .= '<div style="background: white; padding: 10px; border-radius: 3px; margin-bottom: 10px;">';
        $output .= '<div style="display: flex; justify-content: space-between; margin-bottom: 5px;">';
        $output .= '<strong>Current Memory:</strong> <span style="color: #1976d2;">' . self::formatBytes($current) . '</span>';
        $output .= '</div>';
        $output .= '<div style="display: flex; justify-content: space-between; margin-bottom: 5px;">';
        $output .= '<strong>Peak Memory:</strong> <span style="color: #d32f2f;">' . self::formatBytes($peak) . '</span>';
        $output .= '</div>';
        $output .= '<div style="display: flex; justify-content: space-between;">';
        $output .= '<strong>Memory Limit:</strong> <span style="color: #757575;">' . self::formatBytes($limit) . '</span>';
        $output .= '</div>';

        // Progress bar
        if ($limit > 0) {
            $percentage = min(100, ($peak / $limit) * 100);
            $color = $percentage > 80 ? '#d32f2f' : ($percentage > 50 ? '#f57c00' : '#388e3c');

            $output .= '<div style="margin-top: 10px;">';
            $output .= '<div style="background: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden;">';
            $output .= '<div style="background: ' . $color . '; width: ' . $percentage . '%; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-size: 11px;">';
            $output .= number_format($percentage, 1) . '%';
            $output .= '</div></div>';
            $output .= '</div>';
        }

        $output .= '</div>';

        // Таблица снимков
        if (count(self::$snapshots) > 1) {
            $output .= '<div style="background: white; padding: 10px; border-radius: 3px;">';
            $output .= '<strong>Memory Snapshots:</strong><br>';
            $output .= '<table style="width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 12px;">';
            $output .= '<tr style="border-bottom: 1px solid #e0e0e0;">';
            $output .= '<th style="text-align: left; padding: 5px;">#</th>';
            $output .= '<th style="text-align: left; padding: 5px;">Name</th>';
            $output .= '<th style="text-align: left; padding: 5px;">Label</th>';
            $output .= '<th style="text-align: right; padding: 5px;">Memory</th>';
            $output .= '<th style="text-align: right; padding: 5px;">Diff</th>';
            $output .= '<th style="text-align: right; padding: 5px;">Total Diff</th>';
            $output .= '</tr>';

            foreach (self::$snapshots as $index => $snapshot) {
                $diffColor = $snapshot['diff'] > 0 ? '#d32f2f' : ($snapshot['diff'] < 0 ? '#388e3c' : '#757575');
                $diffSign = $snapshot['diff'] > 0 ? '+' : '';

                $output .= '<tr style="' . ($index % 2 ? 'background: #f5f5f5;' : '') . '">';
                $output .= '<td style="padding: 5px;">#' . ($index + 1) . '</td>';
                $output .= '<td style="padding: 5px;">' . htmlspecialchars($snapshot['name']) . '</td>';
                $output .= '<td style="padding: 5px; color: #757575;">' . htmlspecialchars($snapshot['label'] ?? '-') . '</td>';
                $output .= '<td style="padding: 5px; text-align: right;">' . self::formatBytes($snapshot['memory']) . '</td>';
                $output .= '<td style="padding: 5px; text-align: right; color: ' . $diffColor . ';">' . $diffSign . self::formatBytes($snapshot['diff']) . '</td>';
                $output .= '<td style="padding: 5px; text-align: right; color: #1976d2;">' . ($snapshot['diff_from_start'] >= 0 ? '+' : '') . self::formatBytes($snapshot['diff_from_start']) . '</td>';
                $output .= '</tr>';
            }

            $output .= '</table>';
            $output .= '</div>';
        }

        $output .= '</div>';

        // Когда debug включен - отправляем в toolbar, иначе в логи
        if (Environment::isDebug()) {
            Debug::addOutput($output);
        } else {
            Logger::debug("Memory: Current=" . self::formatBytes($current) . ", Peak=" . self::formatBytes($peak));
        }
    }

    /**
     * Measure - профилировать выполнение функции
     */
    public static function measure(string $name, callable $callback): mixed
    {
        if (!Environment::isDebug()) {
            return $callback();
        }

        $before = memory_get_usage(false);
        self::snapshot($name . '_start', 'Before ' . $name);

        try {
            $result = $callback();
        } finally {
            $after = memory_get_usage(false);
            self::snapshot($name . '_end', 'After ' . $name);

            $diff = $after - $before;
            $diffFormatted = self::formatBytes(abs($diff));
            $sign = $diff >= 0 ? '+' : '-';

            // Когда debug включен - отправляем в toolbar
            if (Environment::isDebug()) {
                $color = $diff > 1048576 ? '#d32f2f' : '#757575'; // красный если > 1MB
                Debug::addOutput(
                    '<div style="background: #f3e5f5; border: 1px solid #9c27b0; margin: 10px; padding: 10px; border-radius: 5px; font-family: monospace;">' .
                    '<strong>💾 Memory:</strong> <span style="color: ' . $color . ';">' . htmlspecialchars($name) . ' ' . $sign . $diffFormatted . '</span>' .
                    '</div>'
                );
            }
        }

        return $result;
    }

    /**
     * Форматировать байты в читаемый вид
     * 
     * @deprecated Используйте \Core\Utils\FormatHelper::formatBytes()
     * @param int $bytes Количество байтов
     * @param int $precision Точность форматирования
     * @return string Отформатированная строка
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        return \Core\Utils\FormatHelper::formatBytes($bytes, $precision);
    }

    /**
     * Получить лимит памяти из php.ini
     * 
     * Парсит значение memory_limit из конфигурации PHP и возвращает в байтах.
     * Поддерживает суффиксы K, M, G (регистронезависимые).
     * 
     * @return int Лимит памяти в байтах, 0 если неограниченно или некорректный формат
     */
    public static function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');

        // Неограниченная память или не определено
        if ($limit === '-1' || $limit === false) {
            return 0;
        }

        $limit = trim($limit);
        
        // Защита от пустой строки
        if (empty($limit)) {
            return 0;
        }

        // Проверяем корректность формата: число + опциональный суффикс K/M/G
        if (!preg_match('/^(\d+)([KMG])?$/i', $limit, $matches)) {
            // Некорректный формат - возвращаем 0
            return 0;
        }

        $value = (int)$matches[1];
        $suffix = isset($matches[2]) ? strtolower($matches[2]) : '';

        // Конвертируем в байты
        switch ($suffix) {
            case 'g':
                $value *= 1024;
                // fallthrough intentional
            case 'm':
                $value *= 1024;
                // fallthrough intentional
            case 'k':
                $value *= 1024;
                break;
        }

        return $value;
    }

    /**
     * Получить процент использованной памяти
     */
    public static function getUsagePercentage(): float
    {
        $limit = self::getMemoryLimit();

        if ($limit === 0) {
            return 0.0;
        }

        $current = memory_get_usage(false);
        return ($current / $limit) * 100;
    }

    /**
     * Проверить, не превышен ли порог памяти
     */
    public static function isThresholdExceeded(int $thresholdPercent = 80): bool
    {
        return self::getUsagePercentage() > $thresholdPercent;
    }
}
