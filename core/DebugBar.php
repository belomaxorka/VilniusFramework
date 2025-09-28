<?php declare(strict_types=1);

namespace Core;

class DebugBar
{
    private static array $sections = [];
    private static array $queries = [];
    private static array $logs = [];
    private static array $performance = [];
    private static float $startTime;
    private static int $memoryStart;

    public static function init(): void
    {
        self::$startTime = microtime(true);
        self::$memoryStart = memory_get_usage();
        
        // Регистрируем shutdown функцию для отображения дебаг-бара
        register_shutdown_function([self::class, 'render']);
    }

    /**
     * Добавить секцию в дебаг-бар
     */
    public static function addSection(string $name, mixed $data, ?string $icon = null): void
    {
        if (!Environment::isDebug()) {
            return;
        }

        self::$sections[] = [
            'name' => $name,
            'data' => $data,
            'icon' => $icon,
            'time' => microtime(true)
        ];
    }

    /**
     * Добавить SQL запрос
     */
    public static function addQuery(string $sql, float $time, array $bindings = []): void
    {
        if (!Environment::isDebug()) {
            return;
        }

        self::$queries[] = [
            'sql' => $sql,
            'time' => $time,
            'bindings' => $bindings,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ];
    }

    /**
     * Добавить лог
     */
    public static function addLog(string $level, string $message, array $context = []): void
    {
        if (!Environment::isDebug()) {
            return;
        }

        self::$logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'time' => microtime(true)
        ];
    }

    /**
     * Добавить метрику производительности
     */
    public static function addPerformance(string $name, float $time, ?string $description = null): void
    {
        if (!Environment::isDebug()) {
            return;
        }

        self::$performance[] = [
            'name' => $name,
            'time' => $time,
            'description' => $description,
            'memory' => memory_get_usage()
        ];
    }

    /**
     * Отрендерить дебаг-бар
     */
    public static function render(): void
    {
        if (!Environment::isDebug() || !Environment::isDevelopment()) {
            return;
        }

        // Проверяем, что это HTML ответ
        $contentType = headers_list();
        $isHtml = false;
        foreach ($contentType as $header) {
            if (stripos($header, 'content-type: text/html') !== false) {
                $isHtml = true;
                break;
            }
        }

        if (!$isHtml) {
            return;
        }

        $totalTime = microtime(true) - self::$startTime;
        $totalMemory = memory_get_usage() - self::$memoryStart;
        $peakMemory = memory_get_peak_usage();

        echo self::generateHtml($totalTime, $totalMemory, $peakMemory);
    }

    /**
     * Генерировать HTML дебаг-бара
     */
    private static function generateHtml(float $totalTime, int $totalMemory, int $peakMemory): string
    {
        $queryCount = count(self::$queries);
        $logCount = count(self::$logs);
        $sectionCount = count(self::$sections);

        $html = '<div id="debug-bar" style="' . self::getBarStyles() . '">';
        
        // Основная панель
        $html .= '<div class="debug-bar-main" style="' . self::getMainPanelStyles() . '">';
        
        // Кнопка сворачивания
        $html .= '<div class="debug-bar-toggle" onclick="toggleDebugBar()" style="' . self::getToggleStyles() . '">';
        $html .= '<span id="debug-bar-icon">▼</span>';
        $html .= '</div>';

        // Информация о времени и памяти
        $html .= '<div class="debug-bar-info" style="' . self::getInfoStyles() . '">';
        $html .= '<span class="debug-time">' . number_format($totalTime * 1000, 2) . 'ms</span>';
        $html .= '<span class="debug-memory">' . self::formatBytes($totalMemory) . '</span>';
        $html .= '<span class="debug-peak-memory">Peak: ' . self::formatBytes($peakMemory) . '</span>';
        $html .= '</div>';

        // Счетчики
        $html .= '<div class="debug-bar-counters" style="' . self::getCountersStyles() . '">';
        if ($queryCount > 0) {
            $html .= '<span class="debug-counter" title="SQL Queries">' . $queryCount . ' SQL</span>';
        }
        if ($logCount > 0) {
            $html .= '<span class="debug-counter" title="Log Messages">' . $logCount . ' Logs</span>';
        }
        if ($sectionCount > 0) {
            $html .= '<span class="debug-counter" title="Debug Sections">' . $sectionCount . ' Sections</span>';
        }
        $html .= '</div>';

        $html .= '</div>'; // debug-bar-main

        // Детальная панель
        $html .= '<div id="debug-bar-details" class="debug-bar-details" style="' . self::getDetailsStyles() . '">';
        
        // Секции
        if (!empty(self::$sections)) {
            $html .= '<div class="debug-section">';
            $html .= '<h3>Debug Sections</h3>';
            foreach (self::$sections as $section) {
                $html .= '<div class="debug-section-item">';
                $html .= '<h4>' . ($section['icon'] ? $section['icon'] . ' ' : '') . htmlspecialchars($section['name']) . '</h4>';
                $html .= '<pre>' . htmlspecialchars(self::varToString($section['data'])) . '</pre>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        // SQL запросы
        if (!empty(self::$queries)) {
            $html .= '<div class="debug-section">';
            $html .= '<h3>SQL Queries (' . $queryCount . ')</h3>';
            foreach (self::$queries as $index => $query) {
                $html .= '<div class="debug-query">';
                $html .= '<div class="debug-query-header">';
                $html .= '<span class="debug-query-number">#' . ($index + 1) . '</span>';
                $html .= '<span class="debug-query-time">' . number_format($query['time'] * 1000, 2) . 'ms</span>';
                $html .= '</div>';
                $html .= '<pre class="debug-query-sql">' . htmlspecialchars($query['sql']) . '</pre>';
                if (!empty($query['bindings'])) {
                    $html .= '<div class="debug-query-bindings">';
                    $html .= '<strong>Bindings:</strong> ' . htmlspecialchars(json_encode($query['bindings']));
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        // Логи
        if (!empty(self::$logs)) {
            $html .= '<div class="debug-section">';
            $html .= '<h3>Logs (' . $logCount . ')</h3>';
            foreach (self::$logs as $log) {
                $levelClass = 'debug-log-' . strtolower($log['level']);
                $html .= '<div class="debug-log ' . $levelClass . '">';
                $html .= '<span class="debug-log-level">' . strtoupper($log['level']) . '</span>';
                $html .= '<span class="debug-log-message">' . htmlspecialchars($log['message']) . '</span>';
                if (!empty($log['context'])) {
                    $html .= '<pre class="debug-log-context">' . htmlspecialchars(json_encode($log['context'], JSON_PRETTY_PRINT)) . '</pre>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        // Производительность
        if (!empty(self::$performance)) {
            $html .= '<div class="debug-section">';
            $html .= '<h3>Performance</h3>';
            foreach (self::$performance as $perf) {
                $html .= '<div class="debug-performance">';
                $html .= '<span class="debug-performance-name">' . htmlspecialchars($perf['name']) . '</span>';
                $html .= '<span class="debug-performance-time">' . number_format($perf['time'] * 1000, 2) . 'ms</span>';
                if ($perf['description']) {
                    $html .= '<span class="debug-performance-desc">' . htmlspecialchars($perf['description']) . '</span>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</div>'; // debug-bar-details
        $html .= '</div>'; // debug-bar

        // JavaScript
        $html .= self::getJavaScript();

        return $html;
    }

    /**
     * Получить стили для основного бара
     */
    private static function getBarStyles(): string
    {
        return '
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        ';
    }

    /**
     * Получить стили для основной панели
     */
    private static function getMainPanelStyles(): string
    {
        return '
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
            cursor: pointer;
        ';
    }

    /**
     * Получить стили для кнопки сворачивания
     */
    private static function getToggleStyles(): string
    {
        return '
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 4px 8px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 10px;
            transition: background 0.2s;
        ';
    }

    /**
     * Получить стили для информации
     */
    private static function getInfoStyles(): string
    {
        return '
            display: flex;
            gap: 15px;
            align-items: center;
        ';
    }

    /**
     * Получить стили для счетчиков
     */
    private static function getCountersStyles(): string
    {
        return '
            display: flex;
            gap: 10px;
            margin-left: auto;
        ';
    }

    /**
     * Получить стили для детальной панели
     */
    private static function getDetailsStyles(): string
    {
        return '
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            max-height: 400px;
            overflow-y: auto;
            display: none;
            padding: 15px;
        ';
    }

    /**
     * Получить JavaScript для дебаг-бара
     */
    private static function getJavaScript(): string
    {
        return '
        <script>
        function toggleDebugBar() {
            const details = document.getElementById("debug-bar-details");
            const icon = document.getElementById("debug-bar-icon");
            
            if (details.style.display === "none" || details.style.display === "") {
                details.style.display = "block";
                icon.textContent = "▲";
            } else {
                details.style.display = "none";
                icon.textContent = "▼";
            }
        }
        
        // Закрытие по клику вне бара
        document.addEventListener("click", function(e) {
            const debugBar = document.getElementById("debug-bar");
            const details = document.getElementById("debug-bar-details");
            
            if (!debugBar.contains(e.target) && details.style.display === "block") {
                details.style.display = "none";
                document.getElementById("debug-bar-icon").textContent = "▼";
            }
        });
        </script>
        ';
    }

    /**
     * Форматировать байты в читаемый вид
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Преобразовать переменную в строку
     */
    private static function varToString(mixed $var): string
    {
        if (is_null($var)) {
            return 'NULL';
        }
        if (is_bool($var)) {
            return $var ? 'true' : 'false';
        }
        if (is_string($var)) {
            return '"' . addslashes($var) . '"';
        }
        if (is_numeric($var)) {
            return (string)$var;
        }
        if (is_array($var)) {
            return json_encode($var, JSON_PRETTY_PRINT);
        }
        if (is_object($var)) {
            return get_class($var) . ' object';
        }
        return gettype($var);
    }
}
