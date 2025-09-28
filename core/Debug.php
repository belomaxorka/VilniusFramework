<?php declare(strict_types=1);

namespace Core;

class Debug
{
    private static array $debugData = [];
    private static int $maxDepth = 10;
    private static bool $showBacktrace = true;

    /**
     * Дебаг переменной (аналог var_dump)
     */
    public static function dump(mixed $var, ?string $label = null, bool $die = false): void
    {
        if (!Environment::isDebug()) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $caller = $backtrace[0] ?? [];
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 0;

        $output = self::formatVariable($var, $label, $file, $line);
        
        if (Environment::isDevelopment()) {
            echo $output;
        } else {
            Logger::debug($output);
        }

        if ($die) {
            exit;
        }
    }

    /**
     * Дебаг с красивым выводом (аналог Symfony dump)
     */
    public static function dumpPretty(mixed $var, ?string $label = null, bool $die = false): void
    {
        if (!Environment::isDebug()) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $caller = $backtrace[0] ?? [];
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 0;

        $output = self::formatVariablePretty($var, $label, $file, $line);
        
        if (Environment::isDevelopment()) {
            echo $output;
        } else {
            Logger::debug($output);
        }

        if ($die) {
            exit;
        }
    }

    /**
     * Собрать данные для дебага без вывода
     */
    public static function collect(mixed $var, ?string $label = null): void
    {
        if (!Environment::isDebug()) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $caller = $backtrace[0] ?? [];
        
        self::$debugData[] = [
            'data' => $var,
            'label' => $label,
            'file' => $caller['file'] ?? 'unknown',
            'line' => $caller['line'] ?? 0,
            'time' => microtime(true),
        ];
    }

    /**
     * Вывести все собранные данные
     */
    public static function dumpAll(bool $die = false): void
    {
        if (!Environment::isDebug() || empty(self::$debugData)) {
            return;
        }

        echo '<div style="background: #f8f9fa; border: 1px solid #dee2e6; margin: 10px; padding: 15px; border-radius: 5px; font-family: monospace;">';
        echo '<h3 style="color: #495057; margin-top: 0;">Debug Collection</h3>';
        
        foreach (self::$debugData as $index => $item) {
            echo '<div style="margin-bottom: 20px; border-bottom: 1px solid #dee2e6; padding-bottom: 10px;">';
            echo '<strong>#' . ($index + 1) . '</strong> ';
            if ($item['label']) {
                echo '<span style="color: #007bff;">' . htmlspecialchars($item['label']) . '</span> ';
            }
            echo '<small style="color: #6c757d;">(' . basename($item['file']) . ':' . $item['line'] . ')</small><br>';
            echo '<pre style="background: white; padding: 10px; border-radius: 3px; overflow-x: auto;">';
            echo htmlspecialchars(self::varToString($item['data']));
            echo '</pre>';
            echo '</div>';
        }
        
        echo '</div>';

        if ($die) {
            exit;
        }
    }

    /**
     * Очистить собранные данные
     */
    public static function clear(): void
    {
        self::$debugData = [];
    }

    /**
     * Установить максимальную глубину рекурсии
     */
    public static function setMaxDepth(int $depth): void
    {
        self::$maxDepth = $depth;
    }

    /**
     * Включить/выключить показ backtrace
     */
    public static function setShowBacktrace(bool $show): void
    {
        self::$showBacktrace = $show;
    }

    /**
     * Форматировать переменную для вывода
     */
    private static function formatVariable(mixed $var, ?string $label, string $file, int $line): string
    {
        $output = '';
        
        if (Environment::isDevelopment()) {
            $output .= '<div style="background: #f8f9fa; border: 1px solid #dee2e6; margin: 10px; padding: 15px; border-radius: 5px; font-family: monospace;">';
            
            if ($label) {
                $output .= '<h4 style="color: #007bff; margin-top: 0;">' . htmlspecialchars($label) . '</h4>';
            }
            
            if (self::$showBacktrace) {
                $output .= '<small style="color: #6c757d;">' . basename($file) . ':' . $line . '</small><br>';
            }
            
            $output .= '<pre style="background: white; padding: 10px; border-radius: 3px; overflow-x: auto;">';
            $output .= htmlspecialchars(self::varToString($var));
            $output .= '</pre></div>';
        } else {
            $output = ($label ? "[{$label}] " : '') . basename($file) . ':' . $line . "\n" . self::varToString($var);
        }

        return $output;
    }

    /**
     * Форматировать переменную с красивым выводом
     */
    private static function formatVariablePretty(mixed $var, ?string $label, string $file, int $line): string
    {
        $output = '';
        
        if (Environment::isDevelopment()) {
            $output .= '<div style="background: #1e1e1e; color: #d4d4d4; margin: 10px; padding: 15px; border-radius: 5px; font-family: \'Consolas\', \'Monaco\', monospace; font-size: 13px;">';
            
            if ($label) {
                $output .= '<div style="color: #569cd6; font-weight: bold; margin-bottom: 10px;">' . htmlspecialchars($label) . '</div>';
            }
            
            if (self::$showBacktrace) {
                $output .= '<div style="color: #808080; font-size: 11px; margin-bottom: 10px;">' . basename($file) . ':' . $line . '</div>';
            }
            
            $output .= '<pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;">';
            $output .= self::varToStringPretty($var);
            $output .= '</pre></div>';
        } else {
            $output = ($label ? "[{$label}] " : '') . basename($file) . ':' . $line . "\n" . self::varToString($var);
        }

        return $output;
    }

    /**
     * Преобразовать переменную в строку
     */
    private static function varToString(mixed $var, int $depth = 0): string
    {
        if ($depth > self::$maxDepth) {
            return '... (max depth reached)';
        }

        $indent = str_repeat('  ', $depth);

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
            if (empty($var)) {
                return 'array()';
            }

            $result = "array(\n";
            foreach ($var as $key => $value) {
                $result .= $indent . '  ' . (is_string($key) ? '"' . addslashes($key) . '"' : $key) . ' => ' . self::varToString($value, $depth + 1) . ",\n";
            }
            $result .= $indent . ')';
            return $result;
        }

        if (is_object($var)) {
            $class = get_class($var);
            $result = "object({$class}) {\n";
            
            $reflection = new \ReflectionObject($var);
            $properties = $reflection->getProperties();
            
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($var);
                $result .= $indent . '  ' . $property->getName() . ' => ' . self::varToString($value, $depth + 1) . ",\n";
            }
            
            $result .= $indent . '}';
            return $result;
        }

        if (is_resource($var)) {
            return 'resource(' . get_resource_type($var) . ')';
        }

        return gettype($var);
    }

    /**
     * Преобразовать переменную в строку с красивым форматированием
     */
    private static function varToStringPretty(mixed $var, int $depth = 0): string
    {
        if ($depth > self::$maxDepth) {
            return '<span style="color: #808080;">... (max depth reached)</span>';
        }

        $indent = str_repeat('  ', $depth);

        if (is_null($var)) {
            return '<span style="color: #569cd6;">null</span>';
        }

        if (is_bool($var)) {
            return '<span style="color: #569cd6;">' . ($var ? 'true' : 'false') . '</span>';
        }

        if (is_string($var)) {
            return '<span style="color: #ce9178;">"' . htmlspecialchars($var) . '"</span>';
        }

        if (is_numeric($var)) {
            return '<span style="color: #b5cea8;">' . $var . '</span>';
        }

        if (is_array($var)) {
            if (empty($var)) {
                return '<span style="color: #4ec9b0;">array()</span>';
            }

            $result = '<span style="color: #4ec9b0;">array</span> <span style="color: #808080;">(</span><br>';
            foreach ($var as $key => $value) {
                $keyStr = is_string($key) ? '<span style="color: #ce9178;">"' . htmlspecialchars($key) . '"</span>' : '<span style="color: #b5cea8;">' . $key . '</span>';
                $result .= $indent . '  ' . $keyStr . ' <span style="color: #808080;">=></span> ' . self::varToStringPretty($value, $depth + 1) . '<span style="color: #808080;">,</span><br>';
            }
            $result .= $indent . '<span style="color: #808080;">)</span>';
            return $result;
        }

        if (is_object($var)) {
            $class = get_class($var);
            $result = '<span style="color: #4ec9b0;">object</span> <span style="color: #4ec9b0;">(' . $class . ')</span> <span style="color: #808080;">{</span><br>';
            
            $reflection = new \ReflectionObject($var);
            $properties = $reflection->getProperties();
            
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($var);
                $result .= $indent . '  <span style="color: #9cdcfe;">' . $property->getName() . '</span> <span style="color: #808080;">=></span> ' . self::varToStringPretty($value, $depth + 1) . '<span style="color: #808080;">,</span><br>';
            }
            
            $result .= $indent . '<span style="color: #808080;">}</span>';
            return $result;
        }

        if (is_resource($var)) {
            return '<span style="color: #4ec9b0;">resource</span> <span style="color: #808080;">(' . get_resource_type($var) . ')</span>';
        }

        return '<span style="color: #4ec9b0;">' . gettype($var) . '</span>';
    }
}
