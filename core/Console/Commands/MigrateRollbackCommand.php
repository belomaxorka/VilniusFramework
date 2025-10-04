<?php declare(strict_types=1);

namespace Core\Console\Commands;

/**
 * Migrate Rollback Command
 * 
 * Откатить последнюю миграцию
 */
class MigrateRollbackCommand extends BaseMigrationCommand
{
    protected string $signature = 'migrate:rollback';
    protected string $description = 'Rollback the last database migration';

    public function handle(): int
    {
        $steps = (int)($this->option('step') ?? 1);

        $this->warning("Rolling back migrations (steps: {$steps})...");
        $this->newLine();

        $migrator = $this->createMigrator();
        $migrations = $migrator->rollback($steps);

        if (empty($migrations)) {
            $this->showNothing('rollback');
            return 0;
        }

        $this->showResult('Rollback', $migrations);
        return 0;
    }
}

