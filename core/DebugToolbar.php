<?php declare(strict_types=1);

namespace Core;

use Core\DebugToolbar\CollectorInterface;
use Core\DebugToolbar\Collectors\CacheCollector;
use Core\DebugToolbar\Collectors\OverviewCollector;
use Core\DebugToolbar\Collectors\RequestCollector;
use Core\DebugToolbar\Collectors\RoutesCollector;
use Core\DebugToolbar\Collectors\DumpsCollector;
use Core\DebugToolbar\Collectors\QueriesCollector;
use Core\DebugToolbar\Collectors\TimersCollector;
use Core\DebugToolbar\Collectors\MemoryCollector;
use Core\DebugToolbar\Collectors\ContextsCollector;

class DebugToolbar
{
    private static bool $enabled = true;
    private static string $position = 'bottom'; // bottom | top
    private static bool $collapsed = true;
    private static array $collectors = [];
    private static bool $initialized = false;

    /**
     * Инициализация стандартных коллекторов
     */
    private static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // Регистрируем стандартные коллекторы
        self::addCollector(new OverviewCollector());
        self::addCollector(new RequestCollector());
        self::addCollector(new RoutesCollector());
        self::addCollector(new DumpsCollector());
        self::addCollector(new QueriesCollector());
        self::addCollector(new TimersCollector());
        self::addCollector(new MemoryCollector());
        self::addCollector(new ContextsCollector());

        self::$initialized = true;
    }

    /**
     * Добавить коллектор
     */
    public static function addCollector(CollectorInterface $collector): void
    {
        self::$collectors[$collector->getName()] = $collector;
    }

    /**
     * Получить коллектор по имени
     */
    public static function getCollector(string $name): ?CollectorInterface
    {
        return self::$collectors[$name] ?? null;
    }

    /**
     * Получить все коллекторы
     */
    public static function getCollectors(): array
    {
        return self::$collectors;
    }

    /**
     * Удалить коллектор
     */
    public static function removeCollector(string $name): void
    {
        unset(self::$collectors[$name]);
    }

    /**
     * Установить Router для Routes Collector
     */
    public static function setRouter(Router $router): void
    {
        self::initialize();
        
        $routesCollector = self::getCollector('routes');
        if ($routesCollector instanceof RoutesCollector) {
            $routesCollector->setRouter($router);
            $routesCollector->setCurrentRequest(
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $_SERVER['REQUEST_URI'] ?? '/'
            );
        }
    }

    /**
     * Рендерить toolbar
     */
    public static function render(): string
    {
        if (!Environment::isDebug() || !self::$enabled) {
            return '';
        }

        self::initialize();

        // Собираем данные со всех коллекторов
        foreach (self::$collectors as $collector) {
            if ($collector->isEnabled()) {
                $collector->collect();
            }
        }

        $tabs = self::collectTabs();

        return self::renderHtml($tabs);
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
     * Собрать вкладки из коллекторов
     */
    private static function collectTabs(): array
    {
        $tabs = [];

        // Сортируем коллекторы по приоритету (больше = важнее, отображается первым)
        $collectors = self::$collectors;
        uasort($collectors, fn($a, $b) => $b->getPriority() <=> $a->getPriority());

        foreach ($collectors as $collector) {
            if (!$collector->isEnabled()) {
                continue;
            }

            $tabs[$collector->getName()] = [
                'title' => $collector->getTitle(),
                'icon' => $collector->getIcon(),
                'content' => $collector->render(),
                'badge' => $collector->getBadge(),
            ];
        }

        return $tabs;
    }

    /**
     * Собрать статистику для header из коллекторов
     */
    private static function collectHeaderStats(): array
    {
        $stats = [];

        // Сортируем коллекторы по приоритету (больше = важнее, отображается первым)
        $collectors = self::$collectors;
        uasort($collectors, fn($a, $b) => $b->getPriority() <=> $a->getPriority());

        foreach ($collectors as $collector) {
            if (!$collector->isEnabled()) {
                continue;
            }

            $collectorStats = $collector->getHeaderStats();
            if (!empty($collectorStats)) {
                $stats = array_merge($stats, $collectorStats);
            }
        }

        return $stats;
    }

    /**
     * Рендерить HTML
     */
    private static function renderHtml(array $tabs): string
    {
        $positionClass = self::$position === 'top' ? 'top-0' : 'bottom-0';
        $collapsedClass = self::$collapsed ? 'collapsed' : '';

        $html = '<div id="debug-toolbar" class="' . $collapsedClass . '" style="' . self::getBaseStyles() . '">';

        // Header with stats
        $html .= '<div class="debug-toolbar-header" style="' . self::getHeaderStyles() . '" onclick="debugToolbarToggle()">';
        $html .= self::renderHeader();
        $html .= '</div>';

        // Content with tabs
        $html .= '<div class="debug-toolbar-content" style="' . self::getContentStyles() . '">';

        // Tab navigation
        $html .= '<div class="debug-toolbar-tabs" style="' . self::getTabsStyles() . '">';
        $isFirst = true;
        foreach ($tabs as $key => $tab) {
            $activeClass = $isFirst ? 'active' : '';
            $badge = $tab['badge'] ? '<span class="badge">' . $tab['badge'] . '</span>' : '';
            $html .= '<button class="debug-tab ' . $activeClass . '" data-tab="' . $key . '" onclick="debugToolbarSwitchTab(\'' . $key . '\')" style="' . self::getTabButtonStyles() . '">';
            $html .= $tab['icon'] . ' ' . $tab['title'] . $badge;
            $html .= '</button>';
            $isFirst = false;
        }
        $html .= '</div>';

        // Tab panels
        $html .= '<div class="debug-toolbar-panels" style="' . self::getPanelsStyles() . '">';
        $isFirst = true;
        foreach ($tabs as $key => $tab) {
            $activeClass = $isFirst ? 'active' : '';
            $html .= '<div class="debug-panel ' . $activeClass . '" data-panel="' . $key . '" style="' . self::getPanelStyles() . '">';
            $html .= $tab['content'];
            $html .= '</div>';
            $isFirst = false;
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
    private static function renderHeader(): string
    {
        $html = '<div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">';

        $html .= '<div style="font-weight: bold; color: #fff;">🐛 Debug Toolbar</div>';

        // Собираем статистику из коллекторов
        $stats = self::collectHeaderStats();

        foreach ($stats as $stat) {
            $html .= '<div style="display: flex; align-items: center; gap: 5px;">';
            $html .= '<span>' . $stat['icon'] . '</span>';
            $html .= '<span style="color: ' . $stat['color'] . ';">' . $stat['value'] . '</span>';
            $html .= '</div>';
        }

        $html .= '<div style="margin-left: auto; cursor: pointer;" id="debug-toolbar-arrow">▲</div>';

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
}
