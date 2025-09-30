<?php declare(strict_types=1);

namespace Core;

class DebugContext
{
    private static array $contexts = [];
    private static array $contextStack = [];
    private static ?string $currentContext = null;
    private static array $enabledContexts = [];
    private static bool $filterEnabled = false;

    /**
     * Предустановленные контексты с цветами
     */
    private static array $presetContexts = [
        'database' => ['color' => '#2196f3', 'icon' => '🗄️', 'label' => 'Database'],
        'cache' => ['color' => '#ff9800', 'icon' => '💾', 'label' => 'Cache'],
        'api' => ['color' => '#4caf50', 'icon' => '🌐', 'label' => 'API'],
        'queue' => ['color' => '#9c27b0', 'icon' => '📬', 'label' => 'Queue'],
        'email' => ['color' => '#f44336', 'icon' => '📧', 'label' => 'Email'],
        'security' => ['color' => '#d32f2f', 'icon' => '🔒', 'label' => 'Security'],
        'performance' => ['color' => '#00bcd4', 'icon' => '⚡', 'label' => 'Performance'],
        'validation' => ['color' => '#e91e63', 'icon' => '✓', 'label' => 'Validation'],
        'business' => ['color' => '#3f51b5', 'icon' => '💼', 'label' => 'Business Logic'],
        'general' => ['color' => '#607d8b', 'icon' => '📝', 'label' => 'General'],
    ];

    /**
     * Начать контекст
     */
    public static function start(string $name, ?array $config = null): void
    {
        if (!Environment::isDebug()) {
            return;
        }

        // Если контекст уже существует, делаем его текущим
        if (!isset(self::$contexts[$name])) {
            self::$contexts[$name] = [
                'name' => $name,
                'config' => $config ?? self::$presetContexts[$name] ?? self::getDefaultConfig($name),
                'items' => [],
                'started_at' => microtime(true),
                'ended_at' => null,
                'parent' => self::$currentContext,
            ];
        }

        // Добавляем в стек
        if (self::$currentContext !== null) {
            self::$contextStack[] = self::$currentContext;
        }

        self::$currentContext = $name;
    }

    /**
     * Закончить контекст
     */
    public static function end(?string $name = null): void
    {
        if (!Environment::isDebug()) {
            return;
        }

        $contextToEnd = $name ?? self::$currentContext;

        if ($contextToEnd && isset(self::$contexts[$contextToEnd])) {
            self::$contexts[$contextToEnd]['ended_at'] = microtime(true);
        }

        // Восстанавливаем предыдущий контекст из стека
        if (!empty(self::$contextStack)) {
            self::$currentContext = array_pop(self::$contextStack);
        } else {
            self::$currentContext = null;
        }
    }

    /**
     * Выполнить код в контексте
     */
    public static function run(string $name, callable $callback, ?array $config = null): mixed
    {
        if (!Environment::isDebug()) {
            return $callback();
        }

        self::start($name, $config);

        try {
            $result = $callback();
        } finally {
            self::end($name);
        }

        return $result;
    }

    /**
     * Добавить элемент в текущий контекст
     */
    public static function add(string $type, mixed $data, ?string $context = null): void
    {
        if (!Environment::isDebug()) {
            return;
        }

        $targetContext = $context ?? self::$currentContext ?? 'general';

        if (!isset(self::$contexts[$targetContext])) {
            self::start($targetContext);
        }

        self::$contexts[$targetContext]['items'][] = [
            'type' => $type,
            'data' => $data,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Получить текущий контекст
     */
    public static function current(): ?string
    {
        return self::$currentContext;
    }

    /**
     * Получить все контексты
     */
    public static function getAll(): array
    {
        return self::$contexts;
    }

    /**
     * Получить конкретный контекст
     */
    public static function get(string $name): ?array
    {
        return self::$contexts[$name] ?? null;
    }

    /**
     * Проверить существование контекста
     */
    public static function exists(string $name): bool
    {
        return isset(self::$contexts[$name]);
    }

    /**
     * Очистить все контексты
     */
    public static function clear(?string $name = null): void
    {
        if ($name !== null) {
            unset(self::$contexts[$name]);
        } else {
            self::$contexts = [];
            self::$contextStack = [];
            self::$currentContext = null;
        }
    }

    /**
     * Включить фильтрацию по контекстам
     */
    public static function enableFilter(array $contexts): void
    {
        self::$enabledContexts = $contexts;
        self::$filterEnabled = true;
    }

    /**
     * Отключить фильтрацию
     */
    public static function disableFilter(): void
    {
        self::$filterEnabled = false;
        self::$enabledContexts = [];
    }

    /**
     * Проверить, включен ли контекст
     */
    public static function isEnabled(string $context): bool
    {
        if (!self::$filterEnabled) {
            return true;
        }

        return in_array($context, self::$enabledContexts);
    }

    /**
     * Вывести все контексты
     */
    public static function dump(?array $contexts = null): void
    {
        if (!Environment::isDebug() || empty(self::$contexts)) {
            return;
        }

        $contextsToDump = $contexts ?? array_keys(self::$contexts);

        $output = '<div style="margin: 10px;">';
        $output .= '<h3 style="color: #333; margin: 10px 0;">📁 Debug Contexts</h3>';

        foreach ($contextsToDump as $contextName) {
            if (!isset(self::$contexts[$contextName])) {
                continue;
            }

            if (!self::isEnabled($contextName)) {
                continue;
            }

            $context = self::$contexts[$contextName];
            $config = $context['config'];
            $duration = $context['ended_at']
                ? ($context['ended_at'] - $context['started_at']) * 1000
                : null;

            $output .= '<div style="background: white; border-left: 4px solid ' . $config['color'] . '; margin: 10px 0; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
            $output .= '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
            $output .= '<span style="font-size: 24px; margin-right: 10px;">' . $config['icon'] . '</span>';
            $output .= '<strong style="color: ' . $config['color'] . '; font-size: 16px;">' . htmlspecialchars($config['label']) . '</strong>';

            if ($duration !== null) {
                $output .= '<span style="margin-left: auto; color: #757575; font-size: 12px;">' . number_format($duration, 2) . 'ms</span>';
            }

            $output .= '</div>';

            if (!empty($context['items'])) {
                $output .= '<div style="background: #f5f5f5; padding: 10px; border-radius: 3px;">';
                $output .= '<div style="font-size: 12px; color: #757575; margin-bottom: 5px;">Items: ' . count($context['items']) . '</div>';

                foreach ($context['items'] as $index => $item) {
                    $output .= '<div style="background: white; padding: 8px; margin: 5px 0; border-radius: 3px; font-size: 13px;">';
                    $output .= '<strong style="color: ' . $config['color'] . ';">' . htmlspecialchars($item['type']) . '</strong>: ';

                    if (is_string($item['data'])) {
                        $output .= htmlspecialchars($item['data']);
                    } else {
                        $output .= '<pre style="margin: 5px 0; padding: 5px; background: #f9f9f9; border-radius: 3px; font-size: 11px; overflow-x: auto;">' . htmlspecialchars(print_r($item['data'], true)) . '</pre>';
                    }

                    $output .= '</div>';
                }

                $output .= '</div>';
            }

            $output .= '</div>';
        }

        $output .= '</div>';

        // Когда debug включен - отправляем в toolbar
        if (Environment::isDebug()) {
            Debug::addOutput($output);
        }
    }

    /**
     * Получить статистику по контекстам
     */
    public static function getStats(): array
    {
        $stats = [];

        foreach (self::$contexts as $name => $context) {
            $stats[$name] = [
                'items' => count($context['items']),
                'duration' => $context['ended_at']
                    ? ($context['ended_at'] - $context['started_at']) * 1000
                    : null,
                'label' => $context['config']['label'],
            ];
        }

        return $stats;
    }

    /**
     * Получить количество контекстов
     */
    public static function count(): int
    {
        return count(self::$contexts);
    }

    /**
     * Получить конфигурацию по умолчанию
     */
    private static function getDefaultConfig(string $name): array
    {
        return [
            'color' => '#607d8b',
            'icon' => '📝',
            'label' => ucfirst($name),
        ];
    }

    /**
     * Получить предустановленные контексты
     */
    public static function getPresets(): array
    {
        return self::$presetContexts;
    }

    /**
     * Зарегистрировать кастомный контекст
     */
    public static function register(string $name, array $config): void
    {
        self::$presetContexts[$name] = $config;
    }
}
