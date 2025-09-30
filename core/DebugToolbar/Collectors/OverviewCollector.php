<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\Environment;
use Core\MemoryProfiler;
use Core\QueryDebugger;
use Core\DebugContext;
use Core\Debug;

/**
 * Коллектор общей информации (Overview)
 */
class OverviewCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 10; // Самый первый
    }

    public function getName(): string
    {
        return 'overview';
    }

    public function getTitle(): string
    {
        return 'Overview';
    }

    public function getIcon(): string
    {
        return '📊';
    }

    public function collect(): void
    {
        $this->data = [
            'time' => $this->getExecutionTime(),
            'memory' => $this->getMemoryUsage(),
            'peak_memory' => $this->getPeakMemory(),
            'queries' => $this->getQueriesCount(),
            'slow_queries' => $this->getSlowQueriesCount(),
            'query_time' => $this->getQueryTime(),
            'contexts' => $this->getContextsCount(),
            'dumps' => $this->getDumpsCount(),
        ];
    }

    public function render(): string
    {
        $html = '<div style="padding: 20px;">';
        $html .= '<h3 style="margin-top: 0;">📊 Request Overview</h3>';

        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';

        // Performance
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
        $html .= '<h4 style="margin: 0 0 10px 0; color: #1976d2;">⚡ Performance</h4>';
        $html .= '<div><strong>Total Time:</strong> ' . $this->formatTime($this->data['time']) . '</div>';
        if ($this->data['query_time'] > 0) {
            $html .= '<div><strong>Query Time:</strong> ' . $this->formatTime($this->data['query_time']) . '</div>';
        }
        $html .= '</div>';

        // Memory
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
        $html .= '<h4 style="margin: 0 0 10px 0; color: #388e3c;">💾 Memory</h4>';
        $html .= '<div><strong>Current:</strong> ' . $this->formatBytes($this->data['memory']) . '</div>';
        $html .= '<div><strong>Peak:</strong> ' . $this->formatBytes($this->data['peak_memory']) . '</div>';
        $html .= '</div>';

        // Database
        if ($this->data['queries'] > 0) {
            $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
            $html .= '<h4 style="margin: 0 0 10px 0; color: #f57c00;">🗄️ Database</h4>';
            $html .= '<div><strong>Queries:</strong> ' . $this->data['queries'] . '</div>';
            $html .= '<div><strong>Slow:</strong> ' . $this->data['slow_queries'] . '</div>';
            $html .= '</div>';
        }

        // Debug
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
        $html .= '<h4 style="margin: 0 0 10px 0; color: #7b1fa2;">🐛 Debug</h4>';
        $html .= '<div><strong>Dumps:</strong> ' . $this->data['dumps'] . '</div>';
        $html .= '<div><strong>Contexts:</strong> ' . $this->data['contexts'] . '</div>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function getHeaderStats(): array
    {
        $stats = [];

        // Time
        $timeColor = $this->data['time'] > 1000 ? '#ef5350' : '#66bb6a';
        $stats[] = [
            'icon' => '⏱️',
            'value' => $this->formatTime($this->data['time']),
            'color' => $timeColor,
        ];

        // Memory
        $memoryPercent = $this->getMemoryPercent();
        $memoryColor = $this->getColorByThreshold($memoryPercent, 50, 75);
        $stats[] = [
            'icon' => '💾',
            'value' => $this->formatBytes($this->data['peak_memory']),
            'color' => $memoryColor,
        ];

        return $stats;
    }

    private function getExecutionTime(): float
    {
        if (defined('VILNIUS_START')) {
            return (microtime(true) - VILNIUS_START) * 1000;
        }
        if (defined('APP_START')) {
            return (microtime(true) - APP_START) * 1000;
        }
        return 0;
    }

    private function getMemoryUsage(): int
    {
        if (class_exists('\Core\MemoryProfiler')) {
            return MemoryProfiler::current();
        }
        return memory_get_usage(true);
    }

    private function getPeakMemory(): int
    {
        if (class_exists('\Core\MemoryProfiler')) {
            return MemoryProfiler::peak();
        }
        return memory_get_peak_usage(true);
    }

    private function getQueriesCount(): int
    {
        if (class_exists('\Core\QueryDebugger')) {
            $stats = QueryDebugger::getStats();
            return $stats['total'] ?? 0;
        }
        return 0;
    }

    private function getSlowQueriesCount(): int
    {
        if (class_exists('\Core\QueryDebugger')) {
            $stats = QueryDebugger::getStats();
            return $stats['slow'] ?? 0;
        }
        return 0;
    }

    private function getQueryTime(): float
    {
        if (class_exists('\Core\QueryDebugger')) {
            $stats = QueryDebugger::getStats();
            return $stats['total_time'] ?? 0;
        }
        return 0;
    }

    private function getContextsCount(): int
    {
        if (class_exists('\Core\DebugContext')) {
            return DebugContext::count();
        }
        return 0;
    }

    private function getDumpsCount(): int
    {
        if (class_exists('\Core\Debug')) {
            return count(Debug::getOutput(true));
        }
        return 0;
    }

    private function getMemoryPercent(): float
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1' || !$this->data['peak_memory']) {
            return 0;
        }

        $limitBytes = $this->parseMemoryLimit($limit);
        return ($this->data['peak_memory'] / $limitBytes) * 100;
    }

    private function parseMemoryLimit(string $limit): int
    {
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
}
