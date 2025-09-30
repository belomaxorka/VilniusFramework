<?php declare(strict_types=1);

namespace Core;

class QueryDebugger
{
    private static array $queries = [];
    private static bool $enabled = true;
    private static float $slowQueryThreshold = 100.0; // ms
    private static bool $detectDuplicates = true;

    /**
     * Логировать SQL запрос
     */
    public static function log(string $sql, array $bindings = [], float $time = 0.0, int $rows = 0): void
    {
        if (!Environment::isDebug() || !self::$enabled) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = self::findCaller($backtrace);

        $query = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time,
            'rows' => $rows,
            'caller' => $caller,
            'timestamp' => microtime(true),
            'is_slow' => $time > self::$slowQueryThreshold,
        ];

        self::$queries[] = $query;

        // Добавляем в контекст если активен
        if (class_exists('\Core\DebugContext')) {
            \Core\DebugContext::add('query', [
                'sql' => self::formatSql($sql),
                'time' => number_format($time, 2) . 'ms',
                'rows' => $rows
            ], 'database');
        }
    }

    /**
     * Включить/выключить логирование
     */
    public static function enable(bool $enabled = true): void
    {
        self::$enabled = $enabled;
    }

    /**
     * Установить порог медленных запросов (в миллисекундах)
     */
    public static function setSlowQueryThreshold(float $ms): void
    {
        self::$slowQueryThreshold = $ms;
    }

    /**
     * Включить/выключить обнаружение дубликатов
     */
    public static function setDetectDuplicates(bool $detect): void
    {
        self::$detectDuplicates = $detect;
    }

    /**
     * Получить все запросы
     */
    public static function getQueries(): array
    {
        return self::$queries;
    }

    /**
     * Получить медленные запросы
     */
    public static function getSlowQueries(): array
    {
        return array_filter(self::$queries, fn($q) => $q['is_slow']);
    }

    /**
     * Получить дублирующиеся запросы
     */
    public static function getDuplicates(): array
    {
        if (!self::$detectDuplicates) {
            return [];
        }

        $normalized = [];
        $duplicates = [];

        foreach (self::$queries as $index => $query) {
            $key = self::normalizeQuery($query['sql']);

            if (!isset($normalized[$key])) {
                $normalized[$key] = [];
            }

            $normalized[$key][] = $index;
        }

        foreach ($normalized as $key => $indices) {
            if (count($indices) > 1) {
                $duplicates[] = [
                    'query' => self::$queries[$indices[0]]['sql'],
                    'count' => count($indices),
                    'indices' => $indices,
                ];
            }
        }

        return $duplicates;
    }

    /**
     * Получить статистику
     */
    public static function getStats(): array
    {
        if (empty(self::$queries)) {
            return [
                'total' => 0,
                'slow' => 0,
                'duplicates' => 0,
                'total_time' => 0,
                'avg_time' => 0,
                'total_rows' => 0,
            ];
        }

        $totalTime = array_sum(array_column(self::$queries, 'time'));
        $totalRows = array_sum(array_column(self::$queries, 'rows'));
        $slowCount = count(self::getSlowQueries());
        $duplicatesCount = count(self::getDuplicates());

        return [
            'total' => count(self::$queries),
            'slow' => $slowCount,
            'duplicates' => $duplicatesCount,
            'total_time' => $totalTime,
            'avg_time' => $totalTime / count(self::$queries),
            'total_rows' => $totalRows,
        ];
    }

    /**
     * Очистить логи
     */
    public static function clear(): void
    {
        self::$queries = [];
    }

    /**
     * Вывести все запросы
     */
    public static function dump(): void
    {
        if (!Environment::isDebug() || empty(self::$queries)) {
            return;
        }

        $stats = self::getStats();
        $duplicates = self::getDuplicates();

        $output = '<div style="background: #fff3cd; border: 1px solid #ffc107; margin: 10px; padding: 15px; border-radius: 5px; font-family: monospace;">';
        $output .= '<h4 style="color: #856404; margin-top: 0;">📊 SQL Query Debugger</h4>';

        // Статистика
        $output .= '<div style="background: white; padding: 10px; border-radius: 3px; margin-bottom: 10px;">';
        $output .= '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; font-size: 13px;">';

        $output .= '<div><strong>Total Queries:</strong> ' . $stats['total'] . '</div>';
        $output .= '<div><strong>Slow Queries:</strong> <span style="color: ' . ($stats['slow'] > 0 ? '#d32f2f' : '#388e3c') . ';">' . $stats['slow'] . '</span></div>';
        $output .= '<div><strong>Duplicates:</strong> <span style="color: ' . ($stats['duplicates'] > 0 ? '#f57c00' : '#388e3c') . ';">' . $stats['duplicates'] . '</span></div>';

        $output .= '<div><strong>Total Time:</strong> ' . number_format($stats['total_time'], 2) . 'ms</div>';
        $output .= '<div><strong>Avg Time:</strong> ' . number_format($stats['avg_time'], 2) . 'ms</div>';
        $output .= '<div><strong>Total Rows:</strong> ' . $stats['total_rows'] . '</div>';

        $output .= '</div>';
        $output .= '</div>';

        // Предупреждения
        if ($stats['slow'] > 0 || $stats['duplicates'] > 0) {
            $output .= '<div style="background: #ffebee; border-left: 4px solid #d32f2f; padding: 10px; margin-bottom: 10px; border-radius: 3px;">';
            $output .= '<strong style="color: #c62828;">⚠️ Issues Detected:</strong><br>';

            if ($stats['slow'] > 0) {
                $output .= '• ' . $stats['slow'] . ' slow queries (>' . self::$slowQueryThreshold . 'ms)<br>';
            }

            if ($stats['duplicates'] > 0) {
                $output .= '• ' . $stats['duplicates'] . ' duplicate queries (possible N+1 problem)';
            }

            $output .= '</div>';
        }

        // Список запросов
        $output .= '<div style="background: white; padding: 10px; border-radius: 3px;">';
        $output .= '<strong>Query Log:</strong><br>';

        foreach (self::$queries as $index => $query) {
            $bgColor = $query['is_slow'] ? '#ffebee' : 'white';
            $borderColor = $query['is_slow'] ? '#d32f2f' : '#e0e0e0';

            $output .= '<div style="background: ' . $bgColor . '; border: 1px solid ' . $borderColor . '; padding: 8px; margin: 5px 0; border-radius: 3px; font-size: 12px;">';

            // Заголовок запроса
            $output .= '<div style="display: flex; justify-content: space-between; margin-bottom: 5px;">';
            $output .= '<strong>#' . ($index + 1) . '</strong>';
            $output .= '<div>';
            $output .= '<span style="color: ' . ($query['is_slow'] ? '#d32f2f' : '#388e3c') . '; margin-right: 10px;">' . number_format($query['time'], 2) . 'ms</span>';
            $output .= '<span style="color: #757575;">' . $query['rows'] . ' rows</span>';
            $output .= '</div>';
            $output .= '</div>';

            // SQL с подсветкой
            $output .= '<pre style="background: #f5f5f5; padding: 8px; border-radius: 3px; margin: 5px 0; overflow-x: auto; font-size: 11px;">';
            $output .= self::highlightSql($query['sql']);
            $output .= '</pre>';

            // Bindings
            if (!empty($query['bindings'])) {
                $output .= '<div style="font-size: 11px; color: #757575; margin-top: 5px;">';
                $output .= '<strong>Bindings:</strong> ' . htmlspecialchars(json_encode($query['bindings']));
                $output .= '</div>';
            }

            // Caller
            if ($query['caller']) {
                $output .= '<div style="font-size: 10px; color: #9e9e9e; margin-top: 3px;">';
                $output .= '📍 ' . htmlspecialchars($query['caller']['file']) . ':' . $query['caller']['line'];
                $output .= '</div>';
            }

            $output .= '</div>';
        }

        $output .= '</div>';
        $output .= '</div>';

        // Когда debug включен - отправляем в toolbar, иначе ничего не делаем (уже логируется отдельно)
        if (Environment::isDebug()) {
            Debug::addOutput($output);
        }
    }

    /**
     * Форматировать SQL для вывода
     */
    private static function formatSql(string $sql): string
    {
        return trim(preg_replace('/\s+/', ' ', $sql));
    }

    /**
     * Нормализовать запрос для поиска дубликатов
     */
    private static function normalizeQuery(string $sql): string
    {
        // Убираем числа, строки в кавычках и пробелы для сравнения
        $normalized = preg_replace('/\d+/', '?', $sql);
        $normalized = preg_replace("/'[^']*'/", '?', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return strtoupper(trim($normalized));
    }

    /**
     * Подсветка SQL синтаксиса
     */
    private static function highlightSql(string $sql): string
    {
        $keywords = ['SELECT', 'FROM', 'WHERE', 'JOIN', 'LEFT', 'RIGHT', 'INNER', 'OUTER', 'ON', 'AND', 'OR', 'ORDER', 'BY', 'GROUP', 'HAVING', 'LIMIT', 'OFFSET', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'SET', 'DELETE', 'CREATE', 'TABLE', 'ALTER', 'DROP', 'AS', 'DISTINCT', 'COUNT', 'SUM', 'AVG', 'MAX', 'MIN'];

        $highlighted = htmlspecialchars($sql);

        foreach ($keywords as $keyword) {
            $highlighted = preg_replace(
                '/\b(' . $keyword . ')\b/i',
                '<span style="color: #0066cc; font-weight: bold;">$1</span>',
                $highlighted
            );
        }

        // Строки
        $highlighted = preg_replace(
            "/'([^']*)'/",
            '<span style="color: #d32f2f;">\'$1\'</span>',
            $highlighted
        );

        // Числа
        $highlighted = preg_replace(
            '/\b(\d+)\b/',
            '<span style="color: #388e3c;">$1</span>',
            $highlighted
        );

        return $highlighted;
    }

    /**
     * Найти вызывающий код
     */
    private static function findCaller(array $backtrace): ?array
    {
        foreach ($backtrace as $trace) {
            if (isset($trace['file']) && !str_contains($trace['file'], 'QueryDebugger.php')) {
                return [
                    'file' => $trace['file'],
                    'line' => $trace['line'] ?? 0,
                    'function' => $trace['function'] ?? 'unknown',
                ];
            }
        }

        return null;
    }

    /**
     * Measure query - выполнить и залогировать запрос
     */
    public static function measure(callable $callback, ?string $label = null): mixed
    {
        if (!Environment::isDebug() || !self::$enabled) {
            return $callback();
        }

        $start = microtime(true);

        try {
            $result = $callback();
            $time = (microtime(true) - $start) * 1000;

            if ($label) {
                self::log($label, [], $time, 0);
            }

            return $result;
        } catch (\Throwable $e) {
            $time = (microtime(true) - $start) * 1000;

            if ($label) {
                self::log($label . ' [ERROR]', [], $time, 0);
            }

            throw $e;
        }
    }
}
