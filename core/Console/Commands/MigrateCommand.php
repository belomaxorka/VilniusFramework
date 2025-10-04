<?php declare(strict_types=1);

namespace Core\Console\Commands;

/**
 * Migrate Command
 * 
 * Выполнить миграции базы данных
 */
class MigrateCommand extends BaseMigrationCommand
{
    protected string $signature = 'migrate';
    protected string $description = 'Run database migrations';

    public function handle(): int
    {
        $this->info('Running migrations...');
        $this->newLine();

        $migrator = $this->createMigrator();
        $migrations = $migrator->run();

        if (empty($migrations)) {
            $this->showNothing('migrate');
            return 0;
        }

        $this->showResult('Migration', $migrations);
        return 0;
    }
}

