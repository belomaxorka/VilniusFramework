<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;

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
        ];
    }

    public function getBadge(): ?string
    {
        $count = count(self::$operations);
        return $count > 0 ? (string)$count : null;
    }

    public function render(): string
    {
        if (empty(self::$operations)) {
            return '<div style="padding: 20px; text-align: center; color: #757575;">No cache operations</div>';
        }

        $stats = $this->data['stats'];

        $html = '<div style="padding: 10px;">';

        // Статистика
        $html .= '<div style="background: #f5f5f5; padding: 10px; margin-bottom: 10px; border-radius: 4px;">';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; font-size: 12px;">';
        $html .= '<div><strong>Total:</strong> ' . $stats['total'] . '</div>';
        $html .= '<div><strong>Hits:</strong> <span style="color: #66bb6a;">' . $stats['hits'] . '</span></div>';
        $html .= '<div><strong>Misses:</strong> <span style="color: #ffa726;">' . $stats['misses'] . '</span></div>';
        $html .= '<div><strong>Writes:</strong> ' . $stats['writes'] . '</div>';
        $html .= '<div><strong>Deletes:</strong> ' . $stats['deletes'] . '</div>';
        if ($stats['total'] > 0) {
            $hitRate = ($stats['hits'] / ($stats['hits'] + $stats['misses'])) * 100;
            $html .= '<div><strong>Hit Rate:</strong> ' . number_format($hitRate, 1) . '%</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        // Список операций
        $html .= '<div style="max-height: 350px; overflow-y: auto;">';
        foreach (self::$operations as $index => $op) {
            $bgColor = $this->getOperationColor($op['type']);

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

        return [[
            'icon' => '🗃️',
            'value' => $count . ' cache ops (' . $stats['hits'] . ' hits)',
            'color' => '#66bb6a',
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
            $stats[$op['type'] . 's']++;
        }

        return $stats;
    }

    private function getOperationColor(string $type): string
    {
        return match ($type) {
            'hit' => '#66bb6a',
            'miss' => '#ffa726',
            'write' => '#42a5f5',
            'delete' => '#ef5350',
            default => '#757575',
        };
    }

    private function formatValue(mixed $value): string
    {
        if (is_string($value)) {
            $preview = mb_substr($value, 0, 50);
            return htmlspecialchars($preview) . (mb_strlen($value) > 50 ? '...' : '');
        }
        if (is_array($value)) {
            return 'Array (' . count($value) . ' items)';
        }
        if (is_object($value)) {
            return 'Object (' . get_class($value) . ')';
        }
        return var_export($value, true);
    }
}
