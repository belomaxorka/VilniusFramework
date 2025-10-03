<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\Logger;

/**
 * Коллектор логов
 */
class LogsCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 65; // Между Memory (60) и Timers (70)
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

    public function collect(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $logs = Logger::getLogs();
        $stats = Logger::getStats();

        $this->data = [
            'logs' => $logs,
            'stats' => $stats,
            'total' => $stats['total'],
            'by_level' => $stats['by_level'],
        ];
    }

    public function render(): string
    {
        if (empty($this->data['logs'])) {
            return '<div style="padding: 20px; text-align: center; color: #757575;">No logs recorded</div>';
        }

        $html = '<div style="padding: 20px;">';
        $html .= '<h3 style="margin-top: 0;">📝 Application Logs</h3>';

        // Статистика
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px;">';
        
        foreach ($this->data['by_level'] as $level => $count) {
            if ($count > 0) {
                $color = $this->getLevelColor($level);
                $html .= '<div style="text-align: center;">';
                $html .= '<div style="font-size: 24px; font-weight: bold; color: ' . $color . ';">' . $count . '</div>';
                $html .= '<div style="font-size: 12px; color: #666; text-transform: uppercase;">' . $level . '</div>';
                $html .= '</div>';
            }
        }
        
        $html .= '</div>';
        $html .= '</div>';

        // Таблица логов
        $html .= '<div style="overflow-x: auto;">';
        $html .= '<table style="width: 100%; border-collapse: collapse; font-size: 13px;">';
        $html .= '<thead>';
        $html .= '<tr style="background: #37474f; color: white;">';
        $html .= '<th style="padding: 10px; text-align: left; width: 80px;">Level</th>';
        $html .= '<th style="padding: 10px; text-align: left; width: 140px;">Time</th>';
        $html .= '<th style="padding: 10px; text-align: left;">Message</th>';
        $html .= '<th style="padding: 10px; text-align: left; width: 100px;">Context</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($this->data['logs'] as $index => $log) {
            $bgColor = $index % 2 === 0 ? '#ffffff' : '#f9f9f9';
            $levelColor = $this->getLevelColor($log['level']);
            
            $html .= '<tr style="background: ' . $bgColor . '; border-bottom: 1px solid #e0e0e0;">';
            
            // Level
            $html .= '<td style="padding: 10px;">';
            $html .= '<span style="display: inline-block; padding: 4px 8px; border-radius: 3px; font-weight: bold; font-size: 11px; color: white; background: ' . $levelColor . ';">';
            $html .= strtoupper($log['level']);
            $html .= '</span>';
            $html .= '</td>';
            
            // Time
            $html .= '<td style="padding: 10px; font-family: monospace; font-size: 12px; color: #666;">';
            $html .= htmlspecialchars($log['timestamp']);
            $html .= '</td>';
            
            // Message
            $html .= '<td style="padding: 10px;">';
            $html .= '<div style="word-break: break-word;">' . htmlspecialchars($log['message']) . '</div>';
            $html .= '</td>';
            
            // Context
            $html .= '<td style="padding: 10px; text-align: center;">';
            if (!empty($log['context'])) {
                $contextJson = json_encode($log['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $html .= '<details style="cursor: pointer;">';
                $html .= '<summary style="color: #1976d2; cursor: pointer;">View</summary>';
                $html .= '<pre style="margin-top: 10px; padding: 10px; background: #f5f5f5; border-radius: 3px; text-align: left; font-size: 11px; max-height: 200px; overflow: auto;">';
                $html .= htmlspecialchars($contextJson);
                $html .= '</pre>';
                $html .= '</details>';
            } else {
                $html .= '<span style="color: #999;">—</span>';
            }
            $html .= '</td>';
            
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        
        $html .= '</div>';

        return $html;
    }

    public function getBadge(): ?string
    {
        $total = $this->data['total'] ?? 0;
        
        if ($total === 0) {
            return null;
        }

        // Всегда показываем общее количество логов
        return (string)$total;
    }

    public function getHeaderStats(): array
    {
        if (empty($this->data['logs'])) {
            return [];
        }

        $critical = $this->data['by_level']['critical'] ?? 0;
        $errors = $this->data['by_level']['error'] ?? 0;
        $warnings = $this->data['by_level']['warning'] ?? 0;
        $total = $this->data['total'];

        // Определяем цвет по важности
        $color = '#66bb6a'; // Зеленый по умолчанию
        $value = $total . ' logs';

        if ($critical > 0) {
            $color = '#ef5350'; // Красный
            $value = $critical . ' critical';
        } elseif ($errors > 0) {
            $color = '#ef5350'; // Красный
            $value = $errors . ' errors';
        } elseif ($warnings > 0) {
            $color = '#ffa726'; // Оранжевый
            $value = $warnings . ' warnings';
        }

        return [
            [
                'icon' => '📝',
                'value' => $value,
                'color' => $color,
            ]
        ];
    }

    /**
     * Получает цвет для уровня лога
     */
    private function getLevelColor(string $level): string
    {
        return match($level) {
            'debug' => '#78909c',     // Серо-синий
            'info' => '#42a5f5',      // Синий
            'warning' => '#ffa726',   // Оранжевый
            'error' => '#ef5350',     // Красный
            'critical' => '#c62828',  // Темно-красный
            default => '#999999',
        };
    }
}

