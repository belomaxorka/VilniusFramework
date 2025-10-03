<?php declare(strict_types=1);

namespace Core\Console;

use RuntimeException;

/**
 * Console Application
 * 
 * Главное приложение для CLI (аналог Artisan)
 */
class Application
{
    /**
     * Название приложения
     */
    private string $name = 'Vilnius Framework';

    /**
     * Версия приложения
     */
    private string $version = '1.0.0';

    /**
     * Зарегистрированные команды
     */
    private array $commands = [];

    /**
     * Output handler
     */
    private Output $output;

    public function __construct()
    {
        $this->output = new Output();
    }

    /**
     * Зарегистрировать команду
     */
    public function register(string $commandClass): void
    {
        if (!class_exists($commandClass)) {
            throw new RuntimeException("Command class not found: {$commandClass}");
        }

        if (!is_subclass_of($commandClass, Command::class)) {
            throw new RuntimeException("Command must extend " . Command::class);
        }

        $command = new $commandClass();
        $signature = $command->getSignature();

        if (empty($signature)) {
            throw new RuntimeException("Command signature cannot be empty");
        }

        $this->commands[$signature] = $commandClass;
    }

    /**
     * Зарегистрировать несколько команд
     */
    public function registerCommands(array $commands): void
    {
        foreach ($commands as $command) {
            $this->register($command);
        }
    }

    /**
     * Запустить приложение
     */
    public function run(): int
    {
        global $argv;

        // Если нет аргументов, показываем список команд
        if (!isset($argv[1]) || $argv[1] === 'list') {
            $this->showCommands();
            return 0;
        }

        $commandName = $argv[1];

        // Показать версию
        if ($commandName === '--version' || $commandName === '-V') {
            $this->output->line("{$this->name} {$this->version}");
            return 0;
        }

        // Показать помощь
        if ($commandName === '--help' || $commandName === '-h') {
            $this->showHelp();
            return 0;
        }

        // Выполнить команду
        if (isset($this->commands[$commandName])) {
            $commandClass = $this->commands[$commandName];
            $command = new $commandClass();
            
            try {
                return $command->handle();
            } catch (\Throwable $e) {
                $this->output->error('Command failed: ' . $e->getMessage());
                
                if (getenv('APP_DEBUG') === 'true') {
                    $this->output->line('');
                    $this->output->line($e->getTraceAsString());
                }
                
                return 1;
            }
        }

        $this->output->error("Command '{$commandName}' not found.");
        $this->output->line('');
        $this->output->line('Run "php vilnius list" to see all available commands.');

        return 1;
    }

    /**
     * Показать список команд
     */
    private function showCommands(): void
    {
        $this->output->line('');
        $this->output->line($this->colorize("{$this->name} {$this->version}", 'yellow'));
        $this->output->line('');
        $this->output->line($this->colorize('Usage:', 'yellow'));
        $this->output->line('  command [options] [arguments]');
        $this->output->line('');
        $this->output->line($this->colorize('Available commands:', 'yellow'));

        // Группируем команды по префиксу
        $groupedCommands = [];
        
        foreach ($this->commands as $signature => $commandClass) {
            $command = new $commandClass();
            $parts = explode(':', $signature);
            $group = count($parts) > 1 ? $parts[0] : 'general';
            
            $groupedCommands[$group][] = [
                'signature' => $signature,
                'description' => $command->getDescription(),
            ];
        }

        // Сортируем группы
        ksort($groupedCommands);

        // Сначала выводим команды без группы (general) - отступ в 1 пробел (на уровне заголовков групп)
        if (isset($groupedCommands['general'])) {
            foreach ($groupedCommands['general'] as $commandInfo) {
                $signature = str_pad($commandInfo['signature'], 24);
                $this->output->line(" " . $this->colorize($signature, 'green') . $commandInfo['description']);
            }
        }

        // Затем выводим остальные группы с заголовками
        foreach ($groupedCommands as $group => $commands) {
            if ($group === 'general') {
                continue; // Уже вывели выше
            }

            $this->output->line('');
            $this->output->line($this->colorize(" {$group}", 'green'));

            foreach ($commands as $commandInfo) {
                $signature = str_pad($commandInfo['signature'], 25);
                $this->output->line("  " . $this->colorize($commandInfo['signature'], 'green') . str_repeat(' ', 25 - strlen($commandInfo['signature'])) . $commandInfo['description']);
            }
        }

        $this->output->line('');
    }

    /**
     * Показать справку
     */
    private function showHelp(): void
    {
        $this->output->line('');
        $this->output->line($this->colorize("{$this->name} {$this->version}", 'yellow'));
        $this->output->line('');
        $this->output->line($this->colorize('Usage:', 'yellow'));
        $this->output->line('  php vilnius <command> [options] [arguments]');
        $this->output->line('');
        $this->output->line($this->colorize('Options:', 'yellow'));
        $this->output->line('  -h, --help     Display this help message');
        $this->output->line('  -V, --version  Display application version');
        $this->output->line('');
        $this->output->line($this->colorize('Available commands:', 'yellow'));
        $this->output->line('  list           List all available commands');
        $this->output->line('');
        $this->output->line('Run "php vilnius list" to see all available commands.');
        $this->output->line('');
    }

    /**
     * Применить цвет к тексту
     */
    private function colorize(string $text, string $color): string
    {
        $colors = [
            'yellow' => "\033[1;33m",
            'green' => "\033[0;32m",
            'reset' => "\033[0m",
        ];

        $colorCode = $colors[$color] ?? $colors['reset'];
        return $colorCode . $text . $colors['reset'];
    }

    /**
     * Получить все зарегистрированные команды
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
}

