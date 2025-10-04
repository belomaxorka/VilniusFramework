<?php declare(strict_types=1);

namespace Core\Console\Commands;

/**
 * Migrate Reset Command
 * 
 * Откатить все миграции
 */
class MigrateResetCommand extends BaseMigrationCommand
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

        $migrator = $this->createMigrator();
        $migrations = $migrator->reset();

        if (empty($migrations)) {
            $this->showNothing('reset');
            return 0;
        }

        $this->showResult('Reset', $migrations);
        return 0;
    }
}

