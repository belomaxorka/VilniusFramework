<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\DumpServer;
use Core\Environment;

/**
 * Dump Server Command
 * 
 * Запустить dump server для приема debug данных
 */
class DumpServerCommand extends Command
{
    protected string $signature = 'dump-server';
    protected string $description = 'Start the dump server to receive debug dumps';

    public function handle(): int
    {
        $host = $this->option('host') ?: '127.0.0.1';
        $port = (int) ($this->option('port') ?: 9912);

        // Установка окружения
        Environment::set(Environment::DEVELOPMENT);

        // Настройка сервера
        DumpServer::configure($host, $port);

        // Вывод заголовка
        $this->newLine();
        $this->line('╔═══════════════════════════════════════════════════════════╗');
        $this->line('║                                                           ║');
        $this->line('║              🐛 DEBUG DUMP SERVER 🐛                     ║');
        $this->line('║                                                           ║');
        $this->line('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->info("Server listening on {$host}:{$port}");
        $this->line('Press Ctrl+C to stop');
        $this->newLine();

        try {
            DumpServer::start();
            return 0;
        } catch (\Throwable $e) {
            $this->error('Failed to start dump server: ' . $e->getMessage());
            return 1;
        }
    }
}

