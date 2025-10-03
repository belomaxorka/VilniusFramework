<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\Database\Migrations\Migrator;

/**
 * Migrate Refresh Command
 * 
 * Откатить все миграции и выполнить их заново
 */
class MigrateRefreshCommand extends Command
{
    protected string $signature = 'migrate:refresh';
    protected string $description = 'Reset and re-run all migrations';

    public function handle(): int
    {
        if (!$this->confirm('Are you sure you want to refresh all migrations? This will drop and recreate all tables!', false)) {
            $this->info('Refresh cancelled.');
            return 0;
        }

        $this->warning('Refreshing migrations...');
        $this->newLine();

        $migrator = new Migrator(ROOT . '/database/migrations');
        
        $migrator->setOutput(function (string $message) {
            $this->line("  {$message}");
        });

        $migrations = $migrator->refresh();

        $this->newLine();
        $this->success('Refresh completed successfully!');
        $this->line("  Migrated: " . count($migrations) . " migrations");

        return 0;
    }
}

