<?php declare(strict_types=1);

namespace Core\Console;

/**
 * Console Input
 * 
 * Обработчик ввода из консоли
 */
class Input
{
    /**
     * Аргументы командной строки
     */
    private array $arguments = [];

    /**
     * Опции командной строки
     */
    private array $options = [];

    public function __construct(?array $argv = null)
    {
        $this->parseArguments($argv);
    }

    /**
     * Парсинг аргументов командной строки
     */
    private function parseArguments(?array $argv = null): void
    {
        if ($argv === null) {
            global $argv;
        }
        
        if (!isset($argv) || empty($argv)) {
            return;
        }

        // Пропускаем первый элемент (имя скрипта) и второй (имя команды)
        $args = array_slice($argv, 2);

        $stopParsing = false;

        foreach ($args as $arg) {
            // После -- все аргументы трактуются как обычные аргументы
            if ($arg === '--') {
                $stopParsing = true;
                continue;
            }

            if (!$stopParsing && str_starts_with($arg, '--')) {
                // Длинная опция: --option=value или --option
                $option = substr($arg, 2);
                if (str_contains($option, '=')) {
                    [$key, $value] = explode('=', $option, 2);
                    $this->options[$key] = $value;
                } else {
                    $this->options[$option] = true;
                }
            } elseif (!$stopParsing && str_starts_with($arg, '-')) {
                // Короткая опция: -o=value или -o
                $option = substr($arg, 1);
                if (str_contains($option, '=')) {
                    [$key, $value] = explode('=', $option, 2);
                    $this->options[$key] = $value;
                } else {
                    $this->options[$option] = true;
                }
            } else {
                // Аргумент
                $this->arguments[] = $arg;
            }
        }
    }

    /**
     * Получить аргумент по индексу
     */
    public function getArgument(string|int $name, mixed $default = null): mixed
    {
        if (is_int($name)) {
            return $this->arguments[$name] ?? $default;
        }

        // Если имя строковое, ищем по индексу 0
        return $this->arguments[0] ?? $default;
    }

    /**
     * Получить все аргументы
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Проверить наличие аргумента
     */
    public function hasArgument(int $index): bool
    {
        return isset($this->arguments[$index]);
    }

    /**
     * Получить опцию
     */
    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    /**
     * Получить все опции
     */
    public function getAllOptions(): array
    {
        return $this->options;
    }

    /**
     * Получить все опции (алиас для getAllOptions)
     */
    public function getOptions(): array
    {
        return $this->getAllOptions();
    }

    /**
     * Проверить наличие опции
     */
    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * Заменить аргументы
     */
    public function replace(array $arguments): void
    {
        foreach ($arguments as $index => $value) {
            $this->arguments[$index] = $value;
        }
    }

    /**
     * Запросить ввод пользователя
     */
    public function ask(string $question, ?string $default = null): string
    {
        if ($default !== null) {
            echo "{$question} [{$default}]: ";
        } else {
            echo "{$question}: ";
        }

        $handle = fopen('php://stdin', 'r');
        $line = fgets($handle);
        fclose($handle);

        $line = trim($line);

        return $line !== '' ? $line : ($default ?? '');
    }

    /**
     * Запросить подтверждение (yes/no)
     */
    public function confirm(string $question, bool $default = false): bool
    {
        $defaultText = $default ? 'Y/n' : 'y/N';
        echo "{$question} [{$defaultText}]: ";

        $handle = fopen('php://stdin', 'r');
        $line = fgets($handle);
        fclose($handle);

        $line = trim(strtolower($line));

        if ($line === '') {
            return $default;
        }

        return in_array($line, ['y', 'yes', '1', 'true']);
    }

    /**
     * Запросить выбор из вариантов
     */
    public function choice(string $question, array $choices, mixed $default = null): string
    {
        echo $question . PHP_EOL;

        foreach ($choices as $i => $choice) {
            echo "  [{$i}] {$choice}" . PHP_EOL;
        }

        $defaultText = $default !== null ? " [{$default}]" : '';
        echo "Choose{$defaultText}: ";

        $handle = fopen('php://stdin', 'r');
        $line = fgets($handle);
        fclose($handle);

        $line = trim($line);

        if ($line === '' && $default !== null) {
            $line = $default;
        }

        $index = is_numeric($line) ? (int)$line : array_search($line, $choices);

        if ($index === false || !isset($choices[$index])) {
            echo "Invalid choice. Please try again." . PHP_EOL;
            return $this->choice($question, $choices, $default);
        }

        return $choices[$index];
    }

    /**
     * Запросить секретный ввод (например, пароль)
     */
    public function secret(string $question): string
    {
        echo "{$question}: ";

        if (DIRECTORY_SEPARATOR === '\\') {
            // Windows
            $command = 'powershell -Command "$password = Read-Host -AsSecureString; [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($password))"';
            $password = rtrim(shell_exec($command));
        } else {
            // Unix/Linux/Mac
            $command = "/bin/bash -c 'read -s password; echo \$password'";
            $password = rtrim(shell_exec($command));
        }

        echo PHP_EOL;

        return $password;
    }
}

