<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\Logger;

/**
 * Коллектор логов
 */
class LogsCollector extends AbstractCollector
{
    private static array $logs = [];

    public function __construct()
    {
        $this->priority = 70;
    }

    public function getName(): string
    {
        return 'logs';
    }

    public function getTitle(): string
    {
        return 'Logs';
    }

    public function getIcon(): string
    {
        return '📝';
    }

    public function isEnabled(): bool
    {
        return class_exists('\Core\Logger');
    }

    /**
     * Добавить лог в коллектор (вызывается из Logger)
     */
    public static function addLog(string $level, string $message, array $context = []): void
    {
        self::$logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => microtime(true),
            'memory' => memory_get_usage(true),
        ];
    }

    public function collect(): void
    {
        $this->data = [
            'logs' => self::$logs,
            'total' => count(self::$logs),
            'by_level' => $this->groupByLevel(self::$logs),
        ];
    }

    public function getBadge(): ?string
    {
        $count = count(self::$logs);
        return $count > 0 ? (string)$count : null;
    }

    public function render(): string
    {
        if (empty($this->data['logs'])) {
            return '<div style="padding: 20px; text-align: center; color: #757575;">No logs recorded during this request</div>';
        }

        $html = '<div style="padding: 20px;">';

        // Statistics
        $html .= '<h3 style="margin-top: 0;">📝 Logs</h3>';
        $html .= $this->renderStatistics();

        // Logs list
        $html .= $this->renderLogs();

        $html .= '</div>';

        return $html;
    }

    public function getHeaderStats(): array
    {
        $count = count(self::$logs);
        if ($count === 0) {
            return [];
        }

        // Подсчитываем error/critical логи
        $errorCount = 0;
        foreach (self::$logs as $log) {
            if (in_array($log['level'], ['error', 'critical', 'alert', 'emergency'])) {
                $errorCount++;
            }
        }

        $color = $errorCount > 0 ? '#f44336' : '#2196f3';

        return [[
            'icon' => '📝',
            'value' => $count . ' logs' . ($errorCount > 0 ? ' (' . $errorCount . ' errors)' : ''),
            'color' => $color,
        ]];
    }

    /**
     * Группировка логов по уровню
     */
    private function groupByLevel(array $logs): array
    {
        $grouped = [];

        foreach ($logs as $log) {
            $level = $log['level'];
            if (!isset($grouped[$level])) {
                $grouped[$level] = 0;
            }
            $grouped[$level]++;
        }

        return $grouped;
    }

    /**
     * Рендер статистики
     */
    private function renderStatistics(): string
    {
        $html = '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
        $html .= '<div style="display: flex; gap: 15px; flex-wrap: wrap;">';

        $html .= '<div>';
        $html .= '<div style="color: #757575; font-size: 12px; margin-bottom: 5px;">Total Logs</div>';
        $html .= '<div style="font-size: 24px; font-weight: bold; color: #1976d2;">' . $this->data['total'] . '</div>';
        $html .= '</div>';

        foreach ($this->data['by_level'] as $level => $count) {
            $color = $this->getLevelColor($level);
            $html .= '<div>';
            $html .= '<div style="color: #757575; font-size: 12px; margin-bottom: 5px;">' . ucfirst($level) . '</div>';
            $html .= '<div style="font-size: 24px; font-weight: bold; color: ' . $color . ';">' . $count . '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Рендер списка логов
     */
    private function renderLogs(): string
    {
        $html = '<div style="max-height: 500px; overflow-y: auto;">';

        $startTime = defined('APP_START') ? APP_START : (defined('VILNIUS_START') ? VILNIUS_START : $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));

        foreach ($this->data['logs'] as $index => $log) {
            $color = $this->getLevelColor($log['level']);
            $bgColor = $this->getLevelBackgroundColor($log['level']);
            $relativeTime = ($log['timestamp'] - $startTime) * 1000;

            $html .= '<div style="background: ' . $bgColor . '; border-left: 4px solid ' . $color . '; padding: 12px; margin-bottom: 8px; border-radius: 4px;">';

            // Header
            $html .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">';
            $html .= '<div>';
            $html .= '<span style="background: ' . $color . '; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; text-transform: uppercase;">'
                . htmlspecialchars($log['level']) . '</span>';
            $html .= '<span style="color: #757575; font-size: 12px; margin-left: 10px;">+'
                . number_format($relativeTime, 2) . 'ms</span>';
            $html .= '</div>';
            $html .= '<div style="color: #757575; font-size: 11px; font-family: monospace;">'
                . $this->formatBytes($log['memory']) . '</div>';
            $html .= '</div>';

            // Message
            $html .= '<div style="font-family: monospace; font-size: 13px; margin-bottom: 5px; word-break: break-word;">';
            $html .= htmlspecialchars($log['message']);
            $html .= '</div>';

            // Context (if exists)
            if (!empty($log['context'])) {
                $contextId = 'context_' . $index;
                $html .= '<div style="margin-top: 8px;">';
                $html .= '<button onclick="document.getElementById(\'' . $contextId . '\').style.display = document.getElementById(\'' . $contextId . '\').style.display === \'none\' ? \'block\' : \'none\'" ';
                $html .= 'style="background: #e0e0e0; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 11px;">';
                $html .= 'Toggle Context (' . count($log['context']) . ')';
                $html .= '</button>';
                $html .= '<pre id="' . $contextId . '" style="display: none; background: #f9f9f9; padding: 10px; border-radius: 3px; margin-top: 8px; font-size: 11px; overflow-x: auto; max-height: 200px;">';
                $html .= htmlspecialchars(json_encode($log['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $html .= '</pre>';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Получить цвет для уровня лога
     */
    private function getLevelColor(string $level): string
    {
        return match (strtolower($level)) {
            'emergency', 'alert', 'critical', 'error' => '#f44336',
            'warning' => '#ff9800',
            'notice' => '#2196f3',
            'info' => '#4caf50',
            'debug' => '#9e9e9e',
            default => '#607d8b',
        };
    }

    /**
     * Получить фоновый цвет для уровня лога
     */
    private function getLevelBackgroundColor(string $level): string
    {
        return match (strtolower($level)) {
            'emergency', 'alert', 'critical', 'error' => '#ffebee',
            'warning' => '#fff3e0',
            'notice' => '#e3f2fd',
            'info' => '#e8f5e9',
            'debug' => '#f5f5f5',
            default => '#eceff1',
        };
    }

    /**
     * Очистить логи (для тестирования)
     */
    public static function clear(): void
    {
        self::$logs = [];
    }
}

