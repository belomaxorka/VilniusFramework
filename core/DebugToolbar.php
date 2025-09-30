<?php declare(strict_types=1);

namespace Core;

class DebugToolbar
{
    private static bool $enabled = true;
    private static string $position = 'bottom'; // bottom | top
    private static bool $collapsed = true;

    /**
     * Рендерить toolbar
     */
    public static function render(): string
    {
        if (!Environment::isDebug() || !self::$enabled) {
            return '';
        }

        $stats = self::collectStats();
        $tabs = self::collectTabs();

        return self::renderHtml($stats, $tabs);
    }

    /**
     * Включить/выключить toolbar
     */
    public static function enable(bool $enabled = true): void
    {
        self::$enabled = $enabled;
    }

    /**
     * Установить позицию (bottom | top)
     */
    public static function setPosition(string $position): void
    {
        self::$position = $position;
    }

    /**
     * Свернуть/развернуть по умолчанию
     */
    public static function setCollapsed(bool $collapsed): void
    {
        self::$collapsed = $collapsed;
    }

    /**
     * Собрать статистику
     */
    private static function collectStats(): array
    {
        $stats = [
            'time' => 0,
            'memory' => 0,
            'peak_memory' => 0,
            'queries' => 0,
            'slow_queries' => 0,
            'contexts' => 0,
            'dumps' => 0,
        ];

        // Memory
        if (class_exists('\Core\MemoryProfiler')) {
            $stats['memory'] = \Core\MemoryProfiler::current();
            $stats['peak_memory'] = \Core\MemoryProfiler::peak();
        }

        // Queries
        if (class_exists('\Core\QueryDebugger')) {
            $queryStats = \Core\QueryDebugger::getStats();
            $stats['queries'] = $queryStats['total'] ?? 0;
            $stats['slow_queries'] = $queryStats['slow'] ?? 0;
            $stats['query_time'] = $queryStats['total_time'] ?? 0;
        }

        // Contexts
        if (class_exists('\Core\DebugContext')) {
            $stats['contexts'] = \Core\DebugContext::count();
        }

        // Debug output
        if (class_exists('\Core\Debug')) {
            $stats['dumps'] = count(\Core\Debug::getOutput(true)); // raw array
        }

        // Total time (от старта скрипта)
        if (defined('VILNIUS_START')) {
            $stats['time'] = (microtime(true) - VILNIUS_START) * 1000;
        } elseif (defined('APP_START')) {
            $stats['time'] = (microtime(true) - APP_START) * 1000;
        }

        return $stats;
    }

    /**
     * Собрать вкладки
     */
    private static function collectTabs(): array
    {
        $tabs = [];

        // Tab: Overview
        $tabs['overview'] = [
            'title' => 'Overview',
            'icon' => '📊',
            'content' => self::renderOverview(),
            'badge' => null,
        ];

        // Tab: Dumps
        if (class_exists('\Core\Debug')) {
            $dumps = \Core\Debug::getOutput(true);
            $tabs['dumps'] = [
                'title' => 'Dumps',
                'icon' => '🔍',
                'content' => self::renderDumps($dumps),
                'badge' => count($dumps) > 0 ? count($dumps) : null,
            ];
        }

        // Tab: Queries
        if (class_exists('\Core\QueryDebugger')) {
            $queries = \Core\QueryDebugger::getQueries();
            $tabs['queries'] = [
                'title' => 'Queries',
                'icon' => '🗄️',
                'content' => self::renderQueries($queries),
                'badge' => count($queries) > 0 ? count($queries) : null,
            ];
        }

        // Tab: Timers
        if (class_exists('\Core\DebugTimer')) {
            // Получаем таймеры через reflection (так как нет публичного метода)
            $tabs['timers'] = [
                'title' => 'Timers',
                'icon' => '⏱️',
                'content' => self::renderTimers(),
                'badge' => null,
            ];
        }

        // Tab: Memory
        if (class_exists('\Core\MemoryProfiler')) {
            $tabs['memory'] = [
                'title' => 'Memory',
                'icon' => '💾',
                'content' => self::renderMemory(),
                'badge' => null,
            ];
        }

        // Tab: Contexts
        if (class_exists('\Core\DebugContext')) {
            $contexts = \Core\DebugContext::getAll();
            $tabs['contexts'] = [
                'title' => 'Contexts',
                'icon' => '📁',
                'content' => self::renderContexts($contexts),
                'badge' => count($contexts) > 0 ? count($contexts) : null,
            ];
        }

        return $tabs;
    }

    /**
     * Рендерить HTML
     */
    private static function renderHtml(array $stats, array $tabs): string
    {
        $positionClass = self::$position === 'top' ? 'top-0' : 'bottom-0';
        $collapsedClass = self::$collapsed ? 'collapsed' : '';

        $html = '<div id="debug-toolbar" class="' . $collapsedClass . '" style="' . self::getBaseStyles() . '">';
        
        // Header with stats
        $html .= '<div class="debug-toolbar-header" style="' . self::getHeaderStyles() . '" onclick="debugToolbarToggle()">';
        $html .= self::renderHeader($stats);
        $html .= '</div>';

        // Content with tabs
        $html .= '<div class="debug-toolbar-content" style="' . self::getContentStyles() . '">';
        
        // Tab navigation
        $html .= '<div class="debug-toolbar-tabs" style="' . self::getTabsStyles() . '">';
        foreach ($tabs as $key => $tab) {
            $activeClass = $key === 'overview' ? 'active' : '';
            $badge = $tab['badge'] ? '<span class="badge">' . $tab['badge'] . '</span>' : '';
            $html .= '<button class="debug-tab ' . $activeClass . '" data-tab="' . $key . '" onclick="debugToolbarSwitchTab(\'' . $key . '\')" style="' . self::getTabButtonStyles() . '">';
            $html .= $tab['icon'] . ' ' . $tab['title'] . $badge;
            $html .= '</button>';
        }
        $html .= '</div>';

        // Tab panels
        $html .= '<div class="debug-toolbar-panels" style="' . self::getPanelsStyles() . '">';
        foreach ($tabs as $key => $tab) {
            $activeClass = $key === 'overview' ? 'active' : '';
            $html .= '<div class="debug-panel ' . $activeClass . '" data-panel="' . $key . '" style="' . self::getPanelStyles() . '">';
            $html .= $tab['content'];
            $html .= '</div>';
        }
        $html .= '</div>';

        $html .= '</div>';
        
        // JavaScript
        $html .= self::renderJavaScript();
        
        $html .= '</div>';

        return $html;
    }

    /**
     * Рендерить заголовок
     */
    private static function renderHeader(array $stats): string
    {
        $html = '<div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">';
        
        $html .= '<div style="font-weight: bold; color: #fff;">🐛 Debug Toolbar</div>';
        
        // Time
        $timeColor = $stats['time'] > 1000 ? '#ef5350' : '#66bb6a';
        $html .= '<div style="display: flex; align-items: center; gap: 5px;">';
        $html .= '<span>⏱️</span>';
        $html .= '<span style="color: ' . $timeColor . ';">' . number_format($stats['time'], 2) . 'ms</span>';
        $html .= '</div>';
        
        // Memory
        $memoryPercent = $stats['peak_memory'] > 0 && ini_get('memory_limit') !== '-1' 
            ? ($stats['peak_memory'] / self::parseMemoryLimit(ini_get('memory_limit'))) * 100 
            : 0;
        $memoryColor = $memoryPercent > 75 ? '#ef5350' : ($memoryPercent > 50 ? '#ffa726' : '#66bb6a');
        $html .= '<div style="display: flex; align-items: center; gap: 5px;">';
        $html .= '<span>💾</span>';
        $html .= '<span style="color: ' . $memoryColor . ';">' . self::formatBytes($stats['peak_memory']) . '</span>';
        $html .= '</div>';
        
        // Queries
        if ($stats['queries'] > 0) {
            $queryColor = $stats['slow_queries'] > 0 ? '#ef5350' : '#66bb6a';
            $html .= '<div style="display: flex; align-items: center; gap: 5px;">';
            $html .= '<span>🗄️</span>';
            $html .= '<span style="color: ' . $queryColor . ';">' . $stats['queries'] . ' queries';
            if ($stats['slow_queries'] > 0) {
                $html .= ' (' . $stats['slow_queries'] . ' slow)';
            }
            $html .= '</span>';
            $html .= '</div>';
        }
        
        // Contexts
        if ($stats['contexts'] > 0) {
            $html .= '<div style="display: flex; align-items: center; gap: 5px;">';
            $html .= '<span>📁</span>';
            $html .= '<span>' . $stats['contexts'] . ' contexts</span>';
            $html .= '</div>';
        }
        
        // Dumps
        if ($stats['dumps'] > 0) {
            $html .= '<div style="display: flex; align-items: center; gap: 5px;">';
            $html .= '<span>🔍</span>';
            $html .= '<span>' . $stats['dumps'] . ' dumps</span>';
            $html .= '</div>';
        }
        
        $html .= '<div style="margin-left: auto; cursor: pointer;" id="debug-toolbar-arrow">▲</div>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Рендерить Overview
     */
    private static function renderOverview(): string
    {
        $stats = self::collectStats();
        
        $html = '<div style="padding: 20px;">';
        $html .= '<h3 style="margin-top: 0;">📊 Request Overview</h3>';
        
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
        
        // Performance
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
        $html .= '<h4 style="margin: 0 0 10px 0; color: #1976d2;">⚡ Performance</h4>';
        $html .= '<div><strong>Total Time:</strong> ' . number_format($stats['time'], 2) . 'ms</div>';
        if (isset($stats['query_time'])) {
            $html .= '<div><strong>Query Time:</strong> ' . number_format($stats['query_time'], 2) . 'ms</div>';
        }
        $html .= '</div>';
        
        // Memory
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
        $html .= '<h4 style="margin: 0 0 10px 0; color: #388e3c;">💾 Memory</h4>';
        $html .= '<div><strong>Current:</strong> ' . self::formatBytes($stats['memory']) . '</div>';
        $html .= '<div><strong>Peak:</strong> ' . self::formatBytes($stats['peak_memory']) . '</div>';
        $html .= '</div>';
        
        // Database
        if ($stats['queries'] > 0) {
            $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
            $html .= '<h4 style="margin: 0 0 10px 0; color: #f57c00;">🗄️ Database</h4>';
            $html .= '<div><strong>Queries:</strong> ' . $stats['queries'] . '</div>';
            $html .= '<div><strong>Slow:</strong> ' . $stats['slow_queries'] . '</div>';
            $html .= '</div>';
        }
        
        // Debug
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
        $html .= '<h4 style="margin: 0 0 10px 0; color: #7b1fa2;">🐛 Debug</h4>';
        $html .= '<div><strong>Dumps:</strong> ' . $stats['dumps'] . '</div>';
        $html .= '<div><strong>Contexts:</strong> ' . $stats['contexts'] . '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Рендерить Dumps
     */
    private static function renderDumps(array $dumps): string
    {
        if (empty($dumps)) {
            return '<div style="padding: 20px; text-align: center; color: #757575;">No dumps collected</div>';
        }

        $html = '<div style="padding: 10px; max-height: 400px; overflow-y: auto;">';
        foreach ($dumps as $index => $dump) {
            $html .= '<div style="margin-bottom: 10px;">' . $dump['output'] . '</div>';
        }
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Рендерить Queries
     */
    private static function renderQueries(array $queries): string
    {
        if (empty($queries)) {
            return '<div style="padding: 20px; text-align: center; color: #757575;">No queries executed</div>';
        }

        $html = '<div style="padding: 10px; max-height: 400px; overflow-y: auto;">';
        
        foreach ($queries as $index => $query) {
            $bgColor = $query['is_slow'] ? '#ffebee' : 'white';
            $borderColor = $query['is_slow'] ? '#ef5350' : '#e0e0e0';
            
            $html .= '<div style="background: ' . $bgColor . '; border: 1px solid ' . $borderColor . '; padding: 10px; margin-bottom: 8px; border-radius: 4px; font-size: 12px;">';
            
            $html .= '<div style="display: flex; justify-content: space-between; margin-bottom: 5px;">';
            $html .= '<strong>#' . ($index + 1) . '</strong>';
            $html .= '<span style="color: ' . ($query['is_slow'] ? '#ef5350' : '#66bb6a') . ';">' . number_format($query['time'], 2) . 'ms | ' . $query['rows'] . ' rows</span>';
            $html .= '</div>';
            
            $html .= '<pre style="background: #f5f5f5; padding: 8px; border-radius: 3px; margin: 0; overflow-x: auto; font-size: 11px;">' . htmlspecialchars($query['sql']) . '</pre>';
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Рендерить Timers
     */
    private static function renderTimers(): string
    {
        $html = '<div style="padding: 20px;">';
        $html .= '<div style="text-align: center; color: #757575;">Use timer_dump() to display timers</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Рендерить Memory
     */
    private static function renderMemory(): string
    {
        $html = '<div style="padding: 20px;">';
        $html .= '<div style="text-align: center; color: #757575;">Use memory_dump() to display memory profile</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Рендерить Contexts
     */
    private static function renderContexts(array $contexts): string
    {
        if (empty($contexts)) {
            return '<div style="padding: 20px; text-align: center; color: #757575;">No contexts created</div>';
        }

        $html = '<div style="padding: 10px; max-height: 400px; overflow-y: auto;">';
        
        foreach ($contexts as $name => $context) {
            $config = $context['config'];
            
            $html .= '<div style="background: white; border-left: 4px solid ' . $config['color'] . '; padding: 10px; margin-bottom: 8px; border-radius: 4px;">';
            $html .= '<div style="font-weight: bold; color: ' . $config['color'] . ';">' . $config['icon'] . ' ' . $config['label'] . '</div>';
            $html .= '<div style="font-size: 12px; color: #757575; margin-top: 5px;">Items: ' . count($context['items']) . '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Рендерить JavaScript
     */
    private static function renderJavaScript(): string
    {
        return "
        <script>
        function debugToolbarToggle() {
            const toolbar = document.getElementById('debug-toolbar');
            const arrow = document.getElementById('debug-toolbar-arrow');
            toolbar.classList.toggle('collapsed');
            arrow.textContent = toolbar.classList.contains('collapsed') ? '▲' : '▼';
        }
        
        function debugToolbarSwitchTab(tabName) {
            // Remove active from all
            document.querySelectorAll('.debug-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.debug-panel').forEach(p => p.classList.remove('active'));
            
            // Add active to selected
            document.querySelector('.debug-tab[data-tab=\"' + tabName + '\"]').classList.add('active');
            document.querySelector('.debug-panel[data-panel=\"' + tabName + '\"]').classList.add('active');
        }
        </script>
        <style>
        .debug-tab.active {
            background: #1976d2 !important;
            color: white !important;
        }
        .debug-panel {
            display: none;
        }
        .debug-panel.active {
            display: block;
        }
        #debug-toolbar.collapsed .debug-toolbar-content {
            display: none;
        }
        .debug-tab .badge {
            background: #ef5350;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            margin-left: 5px;
        }
        </style>
        ";
    }

    // Styles
    private static function getBaseStyles(): string
    {
        return 'position: fixed; ' . self::$position . ': 0; left: 0; right: 0; z-index: 999999; background: #263238; color: #eceff1; font-family: monospace; font-size: 13px; box-shadow: 0 -2px 10px rgba(0,0,0,0.3);';
    }

    private static function getHeaderStyles(): string
    {
        return 'padding: 10px 20px; cursor: pointer; user-select: none; border-bottom: 1px solid #37474f;';
    }

    private static function getContentStyles(): string
    {
        return 'background: #eceff1;';
    }

    private static function getTabsStyles(): string
    {
        return 'display: flex; background: #37474f; padding: 0; margin: 0; overflow-x: auto;';
    }

    private static function getTabButtonStyles(): string
    {
        return 'background: transparent; border: none; color: #eceff1; padding: 12px 20px; cursor: pointer; white-space: nowrap; transition: all 0.3s;';
    }

    private static function getPanelsStyles(): string
    {
        return 'background: white; color: #333;';
    }

    private static function getPanelStyles(): string
    {
        return 'min-height: 200px; max-height: 500px; overflow-y: auto;';
    }

    // Helpers
    private static function formatBytes(int $bytes): string
    {
        if (class_exists('\Core\MemoryProfiler')) {
            return \Core\MemoryProfiler::formatBytes($bytes);
        }
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private static function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        return $value;
    }
}
