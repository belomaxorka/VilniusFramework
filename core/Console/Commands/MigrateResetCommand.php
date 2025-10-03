<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\Database\Migrations\Migrator;

/**
 * Migrate Reset Command
 * 
 * Откатить все миграции
 */
class MigrateResetCommand extends Command
{
    protected string $signature = 'migrate:reset';
    protected string $description = 'Rollback all database migrations';

    public function handle(): int
    {
        if (!$this->confirm('Are you sure you want to reset all migrations? This will drop all tables!', false)) {
            $this->info('Reset cancelled.');
            return 0;
        }

        $this->warning('Resetting all migrations...');
        $this->newLine();

        $migrator = new Migrator(ROOT . '/database/migrations');
        
        $migrator->setOutput(function (string $message) {
            $this->line("  {$message}");
        });

        $migrations = $migrator->reset();

        if (empty($migrations)) {
            $this->info('Nothing to reset.');
            return 0;
        }

        $this->newLine();
        $this->success('Reset completed successfully!');
        $this->line("  Rolled back: " . count($migrations) . " migrations");

        return 0;
    }
}

