<?php declare(strict_types=1);

namespace Core;

class DebugTimer
{
    private static array $timers = [];
    private static array $laps = [];

    /**
     * Запустить таймер
     */
    public static function start(string $name = 'default'): void
    {
        if (!Environment::isDebug()) {
            return;
        }

        self::$timers[$name] = [
            'start' => microtime(true),
            'end' => null,
            'laps' => [],
        ];
    }

    /**
     * Остановить таймер
     */
    public static function stop(string $name = 'default'): float
    {
        if (!Environment::isDebug()) {
            return 0.0;
        }

        if (!isset(self::$timers[$name])) {
            return 0.0;
        }

        // Останавливаем только если еще не остановлен
        if (self::$timers[$name]['end'] === null) {
            self::$timers[$name]['end'] = microtime(true);
        }

        return self::getElapsed($name);
    }

    /**
     * Промежуточный замер (lap)
     */
    public static function lap(string $name = 'default', ?string $label = null): float
    {
        if (!Environment::isDebug()) {
            return 0.0;
        }

        if (!isset(self::$timers[$name])) {
            return 0.0;
        }

        $lapTime = microtime(true);
        $start = self::$timers[$name]['start'];
        $elapsed = ($lapTime - $start) * 1000; // в миллисекундах

        self::$timers[$name]['laps'][] = [
            'time' => $lapTime,
            'elapsed' => $elapsed,
            'label' => $label,
        ];

        return $elapsed;
    }

    /**
     * Получить прошедшее время
     */
    public static function getElapsed(string $name = 'default'): float
    {
        if (!isset(self::$timers[$name])) {
            return 0.0;
        }

        $start = self::$timers[$name]['start'];
        $end = self::$timers[$name]['end'] ?? microtime(true);

        return ($end - $start) * 1000; // в миллисекундах
    }

    /**
     * Проверить, запущен ли таймер
     */
    public static function isRunning(string $name = 'default'): bool
    {
        return isset(self::$timers[$name]) && self::$timers[$name]['end'] === null;
    }

    /**
     * Получить все таймеры
     */
    public static function getAll(): array
    {
        $result = [];

        foreach (self::$timers as $name => $timer) {
            $result[$name] = [
                'elapsed' => self::getElapsed($name),
                'laps' => $timer['laps'],
                'running' => self::isRunning($name),
            ];
        }

        return $result;
    }

    /**
     * Вывести все таймеры
     */
    public static function dump(?string $name = null): void
    {
        if (!Environment::isDebug()) {
            return;
        }

        if ($name !== null) {
            self::dumpTimer($name);
            return;
        }

        // Выводим все таймеры
        foreach (self::$timers as $timerName => $timer) {
            self::dumpTimer($timerName);
        }
    }

    /**
     * Вывести конкретный таймер
     */
    private static function dumpTimer(string $name): void
    {
        if (!isset(self::$timers[$name])) {
            return;
        }

        $timer = self::$timers[$name];
        $elapsed = self::getElapsed($name);
        $running = self::isRunning($name);
        $status = $running ? 'Running' : 'Stopped';

        $output = '<div style="background: #e8f5e9; border: 1px solid #4caf50; margin: 10px; padding: 15px; border-radius: 5px; font-family: monospace;">';
        $output .= '<h4 style="color: #2e7d32; margin-top: 0;">⏱️ Timer: ' . htmlspecialchars($name) . ' <small style="color: #66bb6a;">(' . $status . ')</small></h4>';
        $output .= '<div style="background: white; padding: 10px; border-radius: 3px; margin-bottom: 10px;">';
        $output .= '<strong>Total Time:</strong> <span style="color: #2e7d32; font-size: 16px;">' . number_format($elapsed, 2) . 'ms</span>';
        $output .= '</div>';

        // Выводим laps если есть
        if (!empty($timer['laps'])) {
            $output .= '<div style="background: white; padding: 10px; border-radius: 3px;">';
            $output .= '<strong>Lap Times:</strong><br>';
            $output .= '<table style="width: 100%; border-collapse: collapse; margin-top: 5px;">';
            $output .= '<tr style="border-bottom: 1px solid #e0e0e0;"><th style="text-align: left; padding: 5px;">Lap</th><th style="text-align: left; padding: 5px;">Label</th><th style="text-align: right; padding: 5px;">Time</th><th style="text-align: right; padding: 5px;">Interval</th></tr>';

            $prevTime = $timer['start'];
            foreach ($timer['laps'] as $index => $lap) {
                $interval = ($lap['time'] - $prevTime) * 1000;
                $label = $lap['label'] ?? '#' . ($index + 1);

                $output .= '<tr>';
                $output .= '<td style="padding: 5px;">#' . ($index + 1) . '</td>';
                $output .= '<td style="padding: 5px;">' . htmlspecialchars($label) . '</td>';
                $output .= '<td style="padding: 5px; text-align: right; color: #2e7d32;">' . number_format($lap['elapsed'], 2) . 'ms</td>';
                $output .= '<td style="padding: 5px; text-align: right; color: #757575;">+' . number_format($interval, 2) . 'ms</td>';
                $output .= '</tr>';

                $prevTime = $lap['time'];
            }

            $output .= '</table>';
            $output .= '</div>';
        }

        $output .= '</div>';

        if (Environment::isDevelopment()) {
            Debug::addOutput($output);
        } else {
            Logger::debug("Timer [{$name}]: {$elapsed}ms");
        }
    }

    /**
     * Очистить все таймеры
     */
    public static function clear(?string $name = null): void
    {
        if ($name !== null) {
            unset(self::$timers[$name]);
        } else {
            self::$timers = [];
        }
    }

    /**
     * Получить количество таймеров
     */
    public static function count(): int
    {
        return count(self::$timers);
    }

    /**
     * Measure - удобная обертка для измерения времени выполнения кода
     */
    public static function measure(string $name, callable $callback): mixed
    {
        if (!Environment::isDebug()) {
            return $callback();
        }

        self::start($name);

        try {
            $result = $callback();
        } finally {
            self::stop($name);
            self::dump($name);
        }

        return $result;
    }
}
