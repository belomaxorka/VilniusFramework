<?php declare(strict_types=1);

namespace Core\Console;

/**
 * Console Output
 * 
 * Обработчик вывода в консоль
 */
class Output
{
    /**
     * Цвета для вывода
     */
    private const COLORS = [
        'black' => '0;30',
        'red' => '0;31',
        'green' => '0;32',
        'yellow' => '0;33',
        'blue' => '0;34',
        'magenta' => '0;35',
        'cyan' => '0;36',
        'white' => '0;37',
        'bright_red' => '1;31',
        'bright_green' => '1;32',
        'bright_yellow' => '1;33',
    ];

    /**
     * Прогресс-бар
     */
    private int $progressMax = 0;
    private int $progressCurrent = 0;

    /**
     * Кэш проверки поддержки цветов
     */
    private static ?bool $colorsSupported = null;

    /**
     * Конструктор
     */
    public function __construct()
    {
        // Включаем поддержку ANSI на Windows
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->enableWindowsAnsiSupport();
        }
    }

    /**
     * Включить поддержку ANSI на Windows
     */
    private function enableWindowsAnsiSupport(): void
    {
        // Начиная с Windows 10, можно включить режим виртуального терминала
        // через escape-последовательность
        if (PHP_VERSION_ID >= 70200) {
            // Попытка включить ANSI через stream
            @stream_set_blocking(STDOUT, false);
            @stream_set_blocking(STDOUT, true);
        }
        
        // Отправляем специальную последовательность для активации ANSI
        // Это работает в Windows 10+
        @fwrite(STDOUT, "\x1b[0m");
    }

    /**
     * Проверить, поддерживаются ли цвета
     */
    private function supportsColors(): bool
    {
        // Используем кэш, чтобы не проверять каждый раз
        if (self::$colorsSupported !== null) {
            return self::$colorsSupported;
        }

        // Форсированное отключение через переменную окружения
        if (getenv('NO_COLOR') !== false) {
            return self::$colorsSupported = false;
        }

        // Форсированное включение через переменную окружения
        if (getenv('FORCE_COLOR') !== false) {
            return self::$colorsSupported = true;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            // Windows
            // Проверяем старые эмуляторы терминала
            if (getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON') {
                return self::$colorsSupported = true;
            }

            // Windows 10+ с поддержкой ANSI (начиная с версии 1511)
            // Проверяем версию Windows
            $version = php_uname('v');
            if (preg_match('/build (\d+)/', $version, $matches)) {
                $build = (int)$matches[1];
                // Build 10586 = Windows 10 версия 1511 (TH2) с поддержкой ANSI
                if ($build >= 10586) {
                    return self::$colorsSupported = true;
                }
            }

            // Проверяем современные терминалы
            $term = getenv('TERM');
            if ($term && strpos($term, 'xterm') !== false) {
                return self::$colorsSupported = true;
            }

            // Windows Terminal, VS Code Terminal
            if (getenv('WT_SESSION') !== false || getenv('TERM_PROGRAM') === 'vscode') {
                return self::$colorsSupported = true;
            }

            return self::$colorsSupported = false;
        }

        // Unix-подобные системы
        return self::$colorsSupported = (function_exists('posix_isatty') && @posix_isatty(STDOUT));
    }

    /**
     * Применить цвет к тексту
     */
    public function color(string $text, string $color): string
    {
        if (!$this->supportsColors()) {
            return $text;
        }

        $colorCode = self::COLORS[$color] ?? '0;37';
        return "\033[{$colorCode}m{$text}\033[0m";
    }

    /**
     * Вывести текст
     */
    public function write(string $message): void
    {
        echo $message;
    }

    /**
     * Вывести строку
     */
    public function line(string $message = ''): void
    {
        echo $message . PHP_EOL;
    }

    /**
     * Вывести сообщение с цветом и иконкой
     */
    private function message(string $text, string $color, string $icon): void
    {
        $this->line($this->color("{$icon} {$text}", $color));
    }

    /**
     * Вывести информацию (синий цвет)
     */
    public function info(string $message): void
    {
        $this->message($message, 'cyan', 'ℹ');
    }

    /**
     * Вывести успех (зеленый цвет)
     */
    public function success(string $message): void
    {
        $this->message($message, 'bright_green', '✓');
    }

    /**
     * Вывести ошибку (красный цвет)
     */
    public function error(string $message): void
    {
        $this->message($message, 'bright_red', '✗');
    }

    /**
     * Вывести предупреждение (желтый цвет)
     */
    public function warning(string $message): void
    {
        $this->message($message, 'bright_yellow', '⚠');
    }

    /**
     * Вывести новую строку
     */
    public function newLine(int $count = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            echo PHP_EOL;
        }
    }

    /**
     * Вывести таблицу
     */
    public function table(array $headers, array $rows): void
    {
        if (empty($headers) && empty($rows)) {
            return;
        }

        // Вычисляем максимальную ширину для каждой колонки
        $columnWidths = [];
        
        // Учитываем заголовки
        foreach ($headers as $i => $header) {
            $columnWidths[$i] = strlen($header);
        }

        // Учитываем данные
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $length = strlen((string)$cell);
                if (!isset($columnWidths[$i]) || $length > $columnWidths[$i]) {
                    $columnWidths[$i] = $length;
                }
            }
        }

        // Выводим заголовки
        $this->line();
        $headerLine = '| ';
        foreach ($headers as $i => $header) {
            $headerLine .= str_pad($header, $columnWidths[$i]) . ' | ';
        }
        $this->line($this->color($headerLine, 'cyan'));

        // Выводим разделитель
        $separator = '+-';
        foreach ($columnWidths as $width) {
            $separator .= str_repeat('-', $width) . '-+-';
        }
        $this->line($separator);

        // Выводим данные
        foreach ($rows as $row) {
            $rowLine = '| ';
            foreach ($row as $i => $cell) {
                $width = $columnWidths[$i] ?? 0;
                $rowLine .= str_pad((string)$cell, $width) . ' | ';
            }
            $this->line($rowLine);
        }

        $this->line($separator);
        $this->line();
    }

    /**
     * Начать прогресс-бар
     */
    public function progressStart(int $max): void
    {
        $this->progressMax = $max;
        $this->progressCurrent = 0;
        $this->renderProgress();
    }

    /**
     * Продвинуть прогресс-бар
     */
    public function progressAdvance(int $step = 1): void
    {
        $this->progressCurrent += $step;
        if ($this->progressCurrent > $this->progressMax) {
            $this->progressCurrent = $this->progressMax;
        }
        $this->renderProgress();
    }

    /**
     * Завершить прогресс-бар
     */
    public function progressFinish(): void
    {
        $this->progressCurrent = $this->progressMax;
        $this->renderProgress();
        echo PHP_EOL;
    }

    /**
     * Отрендерить прогресс-бар
     */
    private function renderProgress(): void
    {
        $percent = $this->progressMax > 0 ? ($this->progressCurrent / $this->progressMax) * 100 : 0;
        $barWidth = 50;
        $filled = (int)($barWidth * $percent / 100);
        $bar = str_repeat('=', $filled) . str_repeat(' ', $barWidth - $filled);
        
        echo "\r[" . $bar . '] ' . number_format($percent, 1) . '%';
    }
}

