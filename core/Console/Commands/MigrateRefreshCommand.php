<?php declare(strict_types=1);

namespace Core\Console\Commands;

/**
 * Migrate Refresh Command
 * 
 * Откатить все миграции и выполнить их заново
 */
class MigrateRefreshCommand extends BaseMigrationCommand
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

        $migrator = $this->createMigrator();
        $migrations = $migrator->refresh();

        $this->showResult('Refresh', $migrations);
        return 0;
    }
}

