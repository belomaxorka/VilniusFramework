<?php declare(strict_types=1);

namespace Core;

use Core\DebugToolbar\CollectorInterface;
use Core\DebugToolbar\Collectors\CacheCollector;
use Core\DebugToolbar\Collectors\OverviewCollector;
use Core\DebugToolbar\Collectors\RequestCollector;
use Core\DebugToolbar\Collectors\ResponseCollector;
use Core\DebugToolbar\Collectors\RoutesCollector;
use Core\DebugToolbar\Collectors\DumpsCollector;
use Core\DebugToolbar\Collectors\QueriesCollector;
use Core\DebugToolbar\Collectors\TimersCollector;
use Core\DebugToolbar\Collectors\MemoryCollector;
use Core\DebugToolbar\Collectors\ContextsCollector;
use Core\DebugToolbar\Collectors\SessionCollector;
use Core\DebugToolbar\Collectors\EnvironmentCollector;
use Core\DebugToolbar\Collectors\FilesCollector;
use Core\DebugToolbar\Collectors\LogsCollector;

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
        self::addCollector(new ResponseCollector());
        self::addCollector(new RoutesCollector());
        self::addCollector(new DumpsCollector());
        self::addCollector(new QueriesCollector());
        self::addCollector(new TimersCollector());
        self::addCollector(new MemoryCollector());
        self::addCollector(new ContextsCollector());
        self::addCollector(new SessionCollector());
        self::addCollector(new EnvironmentCollector());
        self::addCollector(new FilesCollector());
        self::addCollector(new LogsCollector());

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
        $positionClass = self::$position === 'top' ? 'top' : '';
        $collapsedClass = self::$collapsed ? 'collapsed' : '';
        $classes = array_filter(['debug-toolbar', $positionClass, $collapsedClass]);

        // Include CSS and JS
        $html = self::renderAssets();

        $html .= '<div id="debug-toolbar" class="' . implode(' ', $classes) . '">';

        // Header with stats
        $html .= '<div class="debug-toolbar-header" onclick="debugToolbarToggle()">';
        $html .= self::renderHeader();
        $html .= '</div>';

        // Content with tabs
        $html .= '<div class="debug-toolbar-content">';

        // Tab navigation
        $html .= '<div class="debug-toolbar-tabs">';
        $isFirst = true;
        foreach ($tabs as $key => $tab) {
            $activeClass = $isFirst ? 'active' : '';
            $badge = $tab['badge'] ? '<span class="badge">' . $tab['badge'] . '</span>' : '';
            $html .= '<button class="debug-tab ' . $activeClass . '" data-tab="' . $key . '" onclick="debugToolbarSwitchTab(\'' . $key . '\')">';
            $html .= $tab['icon'] . ' ' . $tab['title'] . $badge;
            $html .= '</button>';
            $isFirst = false;
        }
        $html .= '</div>';

        // Tab panels
        $html .= '<div class="debug-toolbar-panels">';
        $isFirst = true;
        foreach ($tabs as $key => $tab) {
            $activeClass = $isFirst ? 'active' : '';
            $html .= '<div class="debug-panel ' . $activeClass . '" data-panel="' . $key . '">';
            $html .= $tab['content'];
            $html .= '</div>';
            $isFirst = false;
        }
        $html .= '</div>';

        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Рендерить заголовок
     */
    private static function renderHeader(): string
    {
        $html = '<div class="debug-toolbar-header-content">';

        $html .= '<div class="debug-toolbar-title">🐛 Debug Toolbar</div>';

        // Собираем статистику из коллекторов
        $stats = self::collectHeaderStats();

        foreach ($stats as $stat) {
            $html .= '<div class="debug-toolbar-stat">';
            $html .= '<span class="debug-toolbar-stat-icon">' . $stat['icon'] . '</span>';
            $html .= '<span style="color: ' . $stat['color'] . ';">' . $stat['value'] . '</span>';
            $html .= '</div>';
        }

        $html .= '<div class="debug-toolbar-arrow" id="debug-toolbar-arrow">▲</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Рендерить CSS и JS assets
     */
    private static function renderAssets(): string
    {
        $basePath = self::getAssetBasePath();
        
        $cssPath = $basePath . '/css/debug-toolbar.css';
        $jsPath = $basePath . '/js/debug-toolbar.js';
        
        $html = '';
        
        // CSS
        if (file_exists(dirname(__DIR__) . '/resources/css/debug-toolbar.css')) {
            $html .= '<link rel="stylesheet" href="' . htmlspecialchars($cssPath) . '?v=' . self::getAssetVersion() . '">';
        } else {
            // Fallback to inline styles if file not found
            $html .= '<style>' . self::getInlineStyles() . '</style>';
        }
        
        // JavaScript
        if (file_exists(dirname(__DIR__) . '/resources/js/debug-toolbar.js')) {
            $html .= '<script src="' . htmlspecialchars($jsPath) . '?v=' . self::getAssetVersion() . '" defer></script>';
        } else {
            // Fallback to inline script if file not found
            $html .= '<script>' . self::getInlineScript() . '</script>';
        }
        
        return $html;
    }

    /**
     * Получить базовый путь для assets
     */
    private static function getAssetBasePath(): string
    {
        // Определяем базовый URL
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        
        $basePath = $scheme . '://' . $host . $scriptName;
        $basePath = rtrim($basePath, '/');
        
        return $basePath . '/resources';
    }

    /**
     * Получить версию assets для cache busting
     */
    private static function getAssetVersion(): string
    {
        // Используем modification time файла или константу версии
        static $version = null;
        
        if ($version === null) {
            $cssFile = dirname(__DIR__) . '/resources/css/debug-toolbar.css';
            if (file_exists($cssFile)) {
                $version = filemtime($cssFile);
            } else {
                $version = '1.0.0';
            }
        }
        
        return (string)$version;
    }

    /**
     * Получить inline стили (fallback)
     */
    private static function getInlineStyles(): string
    {
        return '
        #debug-toolbar{position:fixed;bottom:0;left:0;right:0;z-index:999999;background:#263238;color:#eceff1;font-family:monospace;font-size:13px;box-shadow:0 -2px 10px rgba(0,0,0,0.3)}
        #debug-toolbar.top{bottom:auto;top:0}
        .debug-toolbar-header{padding:10px 20px;cursor:pointer;user-select:none;border-bottom:1px solid #37474f}
        .debug-toolbar-header-content{display:flex;align-items:center;gap:20px;flex-wrap:wrap}
        .debug-toolbar-title{font-weight:bold;color:#fff}
        .debug-toolbar-stat{display:flex;align-items:center;gap:5px}
        .debug-toolbar-arrow{margin-left:auto;cursor:pointer}
        .debug-toolbar-content{background:#eceff1}
        #debug-toolbar.collapsed .debug-toolbar-content{display:none}
        .debug-toolbar-tabs{display:flex;background:#37474f;padding:0;margin:0;overflow-x:auto}
        .debug-tab{background:transparent;border:none;color:#eceff1;padding:12px 20px;cursor:pointer;white-space:nowrap;transition:all 0.3s}
        .debug-tab.active{background:#1976d2;color:white}
        .debug-tab .badge{background:#ef5350;color:white;border-radius:10px;padding:2px 6px;font-size:10px;margin-left:5px}
        .debug-toolbar-panels{background:white;color:#333}
        .debug-panel{display:none;min-height:200px;max-height:500px;overflow-y:auto}
        .debug-panel.active{display:block}
        ';
    }

    /**
     * Получить inline script (fallback)
     */
    private static function getInlineScript(): string
    {
        return '
        function debugToolbarToggle(){const t=document.getElementById("debug-toolbar"),e=document.getElementById("debug-toolbar-arrow");t.classList.toggle("collapsed"),e.textContent=t.classList.contains("collapsed")?"▲":"▼"}
        function debugToolbarSwitchTab(t){document.querySelectorAll(".debug-tab").forEach(t=>t.classList.remove("active")),document.querySelectorAll(".debug-panel").forEach(t=>t.classList.remove("active")),document.querySelector(\'.debug-tab[data-tab="\'+t+\'"]\').classList.add("active"),document.querySelector(\'.debug-panel[data-panel="\'+t+\'"]\').classList.add("active")}
        ';
    }
}
