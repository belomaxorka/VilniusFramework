<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\DebugToolbar\ColorPalette;
use Core\TemplateEngine;

/**
 * Коллектор отрендеренных шаблонов
 */
class TemplatesCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 72; // Между CacheCollector (75) и TimersCollector (70)
    }

    public function getName(): string
    {
        return 'templates';
    }

    public function getTitle(): string
    {
        return 'Templates';
    }

    public function getIcon(): string
    {
        return '🎨';
    }

    public function isEnabled(): bool
    {
        return class_exists('\Core\TemplateEngine');
    }

    public function collect(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $templates = TemplateEngine::getRenderedTemplates();
        $stats = TemplateEngine::getRenderStats();
        $undefinedVars = TemplateEngine::getUndefinedVars();

        $this->data = [
            'templates' => $templates,
            'stats' => $stats,
            'undefined_vars' => $undefinedVars,
            'total' => $stats['total'],
        ];
    }

    public function render(): string
    {
        if (empty($this->data['templates'])) {
            return $this->renderEmptyState('No templates rendered');
        }

        $html = '<div style="padding: 20px;">';
        $html .= '<h3 style="margin-top: 0;">🎨 Rendered Templates</h3>';

        // Статистика
        $stats = $this->data['stats'];
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">';
        
        $html .= '<div>';
        $html .= '<div style="font-size: 24px; font-weight: bold; color: #1976d2;">' . $stats['total'] . '</div>';
        $html .= '<div style="font-size: 12px; color: #666;">TEMPLATES</div>';
        $html .= '</div>';
        
        $html .= '<div>';
        $html .= '<div style="font-size: 24px; font-weight: bold; color: #388e3c;">' . $this->formatTime($stats['total_time']) . '</div>';
        $html .= '<div style="font-size: 12px; color: #666;">TOTAL TIME</div>';
        $html .= '</div>';
        
        $html .= '<div>';
        $html .= '<div style="font-size: 24px; font-weight: bold; color: #7b1fa2;">' . $this->formatBytes($stats['total_size']) . '</div>';
        $html .= '<div style="font-size: 12px; color: #666;">OUTPUT SIZE</div>';
        $html .= '</div>';
        
        $html .= '<div>';
        $cachePercent = $stats['total'] > 0 ? round(($stats['from_cache'] / $stats['total']) * 100) : 0;
        $html .= '<div style="font-size: 24px; font-weight: bold; color: #f57c00;">' . $cachePercent . '%</div>';
        $html .= '<div style="font-size: 12px; color: #666;">FROM CACHE</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';

        // Undefined переменные (если есть)
        if (!empty($this->data['undefined_vars'])) {
            $html .= '<div style="background: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; margin-bottom: 20px;">';
            $html .= '<div style="font-weight: bold; color: #e65100; margin-bottom: 10px;">⚠️ Undefined Variables (' . count($this->data['undefined_vars']) . ')</div>';
            
            foreach ($this->data['undefined_vars'] as $varName => $info) {
                $html .= '<div style="margin-bottom: 5px;">';
                $html .= '<code style="background: #ffe0b2; padding: 2px 6px; border-radius: 3px; font-size: 12px;">';
                $html .= '$' . htmlspecialchars($varName);
                $html .= '</code>';
                $html .= ' <span style="color: #666; font-size: 12px;">(' . $info['count'] . ' times)</span>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }

        // Список шаблонов
        $html .= '<div style="overflow-x: auto;">';
        
        foreach ($this->data['templates'] as $index => $template) {
            $isOdd = $index % 2 === 1;
            $bgColor = $isOdd ? '#f9f9f9' : '#ffffff';
            
            $html .= '<div style="background: ' . $bgColor . '; padding: 15px; border-left: 3px solid #1976d2; margin-bottom: 10px;">';
            
            // Заголовок шаблона
            $html .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">';
            $html .= '<div style="font-weight: bold; color: #1976d2; font-size: 14px;">';
            $html .= htmlspecialchars($template['template']);
            $html .= '</div>';
            
            // Badges
            $html .= '<div style="display: flex; gap: 10px;">';
            
            // Время
            $timeColor = $template['time'] > 50 ? '#ef5350' : ($template['time'] > 20 ? '#ffa726' : '#66bb6a');
            $html .= '<span style="background: ' . $timeColor . '; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">';
            $html .= $this->formatTime($template['time']);
            $html .= '</span>';
            
            // Cache badge
            if ($template['from_cache']) {
                $html .= '<span style="background: #66bb6a; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">';
                $html .= '🗃️ CACHED';
                $html .= '</span>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
            
            // Детали в grid
            $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 12px; color: #666;">';
            
            $html .= '<div>';
            $html .= '<strong>Variables:</strong> ' . $template['variables_count'];
            $html .= '</div>';
            
            $html .= '<div>';
            $html .= '<strong>Memory:</strong> ' . $this->formatBytes($template['memory']);
            $html .= '</div>';
            
            $html .= '<div>';
            $html .= '<strong>Size:</strong> ' . $this->formatBytes($template['size']);
            $html .= '</div>';
            
            $html .= '</div>';
            
            // Переменные (в details)
            if (!empty($template['variables'])) {
                $html .= '<details style="margin-top: 10px;">';
                $html .= '<summary style="cursor: pointer; color: #1976d2; font-size: 12px;">View Variables (' . count($template['variables']) . ')</summary>';
                $html .= '<div style="margin-top: 10px; padding: 10px; background: white; border-radius: 3px; border: 1px solid #e0e0e0;">';
                
                $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 5px;">';
                foreach ($template['variables'] as $var) {
                    $html .= '<code style="font-size: 11px; color: #666;">' . htmlspecialchars($var) . '</code>';
                }
                $html .= '</div>';
                
                $html .= '</div>';
                $html .= '</details>';
            }
            
            $html .= '</div>';
        }
        
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

        // Показываем количество undefined vars если есть
        $undefinedCount = count($this->data['undefined_vars'] ?? []);
        if ($undefinedCount > 0) {
            return (string)$undefinedCount;
        }

        return (string)$total;
    }

    public function getHeaderStats(): array
    {
        if (empty($this->data['templates'])) {
            return [];
        }

        $stats = $this->data['stats'];
        $undefinedCount = count($this->data['undefined_vars'] ?? []);
        
        // Если есть undefined переменные - показываем их
        if ($undefinedCount > 0) {
            return [
                [
                    'icon' => '🎨',
                    'value' => $undefinedCount . ' undefined vars',
                    'color' => '#ffa726', // Оранжевый
                ]
            ];
        }

        // Иначе показываем общую статистику
        $totalTime = $stats['total_time'];
        $timeColor = $this->getTimeColor($totalTime, 50, 100);

        return [
            [
                'icon' => '🎨',
                'value' => $stats['total'] . ' templates (' . $this->formatTime($totalTime) . ')',
                'color' => $timeColor,
            ]
        ];
    }
}

