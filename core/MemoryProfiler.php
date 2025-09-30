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

        self::$startMemory = memory_get_usage(true);
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

        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

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
     */
    public static function current(): int
    {
        return memory_get_usage(true);
    }

    /**
     * Получить пиковое использование памяти
     */
    public static function peak(): int
    {
        return memory_get_peak_usage(true);
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

        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
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

        $before = memory_get_usage(true);
        self::snapshot($name . '_start', 'Before ' . $name);

        try {
            $result = $callback();
        } finally {
            $after = memory_get_usage(true);
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
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $bytes = abs($bytes);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);

        $value = $bytes / pow(1024, $pow);

        return number_format($value, $precision) . ' ' . $units[$pow];
    }

    /**
     * Получить лимит памяти из php.ini
     */
    public static function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');

        if ($limit === '-1') {
            return 0; // неограниченно
        }

        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int)$limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
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

        $current = memory_get_usage(true);
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
