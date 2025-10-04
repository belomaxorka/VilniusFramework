<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\DebugToolbar\ColorPalette;

/**
 * Коллектор операций с кэшем
 * Пример кастомного коллектора
 */
class CacheCollector extends AbstractCollector
{
    private static array $operations = [];

    public function __construct()
    {
        $this->priority = 75;
    }

    public function getName(): string
    {
        return 'cache';
    }

    public function getTitle(): string
    {
        return 'Cache';
    }

    public function getIcon(): string
    {
        return '🗃️';
    }

    public function collect(): void
    {
        $this->data = [
            'operations' => self::$operations,
            'stats' => $this->calculateStats(),
            'driver' => $this->getCurrentDriver(),
        ];
    }

    /**
     * Получить текущий используемый драйвер
     */
    private function getCurrentDriver(): array
    {
        if (!class_exists('\Core\Cache')) {
            return ['name' => 'N/A', 'type' => 'unknown'];
        }

        try {
            $manager = \Core\Cache::getManager();
            $defaultDriver = $manager->getDefaultDriver();
            $config = $manager->getDriverConfig($defaultDriver);
            
            return [
                'name' => $defaultDriver,
                'type' => $config['driver'] ?? 'unknown',
                'config' => $config,
            ];
        } catch (\Exception $e) {
            return ['name' => 'N/A', 'type' => 'error', 'error' => $e->getMessage()];
        }
    }

    public function getBadge(): ?string
    {
        return $this->countBadge('operations');
    }

    public function render(): string
    {
        $stats = $this->data['stats'];
        $driver = $this->data['driver'];

        $html = '<div style="padding: 10px;">';

        // Информация о драйвере
        $html .= '<div style="background: #e3f2fd; padding: 10px; margin-bottom: 10px; border-radius: 4px; border-left: 4px solid #2196f3;">';
        $html .= '<div style="font-size: 12px;">';
        $html .= '<strong>Driver:</strong> ' . htmlspecialchars($driver['name']) . ' (' . htmlspecialchars($driver['type']) . ')';
        $html .= '</div>';
        $html .= '</div>';

        if (empty(self::$operations)) {
            $html .= '<div style="padding: 20px; text-align: center; color: #757575;">No cache operations</div>';
            $html .= '</div>';
            return $html;
        }

        // Статистика
        $html .= '<div style="background: #f5f5f5; padding: 10px; margin-bottom: 10px; border-radius: 4px;">';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; font-size: 12px;">';
        $html .= '<div><strong>Total:</strong> ' . $stats['total'] . '</div>';
        $html .= '<div><strong>Hits:</strong> <span style="color: ' . ColorPalette::SUCCESS . ';">' . $stats['hits'] . '</span></div>';
        $html .= '<div><strong>Misses:</strong> <span style="color: ' . ColorPalette::WARNING . ';">' . $stats['misses'] . '</span></div>';
        $html .= '<div><strong>Writes:</strong> ' . $stats['writes'] . '</div>';
        $html .= '<div><strong>Deletes:</strong> ' . $stats['deletes'] . '</div>';
        if (($stats['hits'] + $stats['misses']) > 0) {
            $hitRate = ($stats['hits'] / ($stats['hits'] + $stats['misses'])) * 100;
            $hitRateColor = ColorPalette::getThresholdColor($hitRate, 50, 80);
            $html .= '<div><strong>Hit Rate:</strong> <span style="color: ' . $hitRateColor . ';">' . number_format($hitRate, 1) . '%</span></div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        // Список операций
        $html .= '<div style="max-height: 350px; overflow-y: auto;">';
        foreach (self::$operations as $index => $op) {
            $bgColor = ColorPalette::getCacheOperationColor($op['type']);

            $html .= '<div style="background: white; border-left: 4px solid ' . $bgColor . '; padding: 8px; margin-bottom: 6px; border-radius: 4px; font-size: 12px;">';

            $html .= '<div style="display: flex; justify-content: space-between; align-items: center;">';
            $html .= '<div>';
            $html .= '<strong style="color: ' . $bgColor . ';">' . strtoupper($op['type']) . '</strong> ';
            $html .= '<code>' . htmlspecialchars($op['key']) . '</code>';
            $html .= '</div>';
            $html .= '<span style="color: #757575; font-size: 10px;">' . $this->formatTime($op['time']) . '</span>';
            $html .= '</div>';

            if (isset($op['value'])) {
                $html .= '<div style="font-size: 11px; color: #757575; margin-top: 4px;">Value: ' . $this->formatValue($op['value']) . '</div>';
            }

            $html .= '</div>';
        }
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    public function getHeaderStats(): array
    {
        $count = count(self::$operations);
        if ($count === 0) {
            return [];
        }

        $stats = $this->data['stats'] ?? $this->calculateStats();
        
        // Вычисляем hit rate для цвета
        $hitRate = 100;
        if (($stats['hits'] + $stats['misses']) > 0) {
            $hitRate = ($stats['hits'] / ($stats['hits'] + $stats['misses'])) * 100;
        }
        
        $color = ColorPalette::getThresholdColor($hitRate, 50, 80);

        return [[
            'icon' => '🗃️',
            'value' => $count . ' ops (' . $stats['hits'] . 'H/' . $stats['misses'] . 'M)',
            'color' => $color,
        ]];
    }

    /**
     * Логировать операцию с кэшем
     */
    public static function logOperation(string $type, string $key, mixed $value = null, float $time = 0.0): void
    {
        self::$operations[] = [
            'type' => $type,
            'key' => $key,
            'value' => $value,
            'time' => $time,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Логировать hit (попадание в кэш)
     */
    public static function logHit(string $key, mixed $value = null, float $time = 0.0): void
    {
        self::logOperation('hit', $key, $value, $time);
    }

    /**
     * Логировать miss (промах кэша)
     */
    public static function logMiss(string $key, float $time = 0.0): void
    {
        self::logOperation('miss', $key, null, $time);
    }

    /**
     * Логировать write (запись в кэш)
     */
    public static function logWrite(string $key, mixed $value = null, float $time = 0.0): void
    {
        self::logOperation('write', $key, $value, $time);
    }

    /**
     * Логировать delete (удаление из кэша)
     */
    public static function logDelete(string $key, float $time = 0.0): void
    {
        self::logOperation('delete', $key, null, $time);
    }

    private function calculateStats(): array
    {
        $stats = [
            'total' => count(self::$operations),
            'hits' => 0,
            'misses' => 0,
            'writes' => 0,
            'deletes' => 0,
        ];

        foreach (self::$operations as $op) {
            switch ($op['type']) {
                case 'hit':
                    $stats['hits']++;
                    break;
                case 'miss':
                    $stats['misses']++;
                    break;
                case 'write':
                    $stats['writes']++;
                    break;
                case 'delete':
                    $stats['deletes']++;
                    break;
            }
        }

        return $stats;
    }

}
