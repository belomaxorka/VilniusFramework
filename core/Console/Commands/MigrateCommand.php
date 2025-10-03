<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\Database\Migrations\Migrator;

/**
 * Migrate Command
 * 
 * Выполнить миграции базы данных
 */
class MigrateCommand extends Command
{
    protected string $signature = 'migrate';
    protected string $description = 'Run database migrations';

    public function handle(): int
    {
        $this->info('Running migrations...');
        $this->newLine();

        $migrator = new Migrator(ROOT . '/database/migrations');
        
        $migrator->setOutput(function (string $message) {
            $this->line("  {$message}");
        });

        $migrations = $migrator->run();

        if (empty($migrations)) {
            $this->info('Nothing to migrate.');
            return 0;
        }

        $this->newLine();
        $this->success('Migrations completed successfully!');
        $this->line("  Migrated: " . count($migrations) . " migrations");

        return 0;
    }
}

