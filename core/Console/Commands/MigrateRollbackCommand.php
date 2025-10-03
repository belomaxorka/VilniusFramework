<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\Database\Migrations\Migrator;

/**
 * Migrate Rollback Command
 * 
 * Откатить последнюю миграцию
 */
class MigrateRollbackCommand extends Command
{
    protected string $signature = 'migrate:rollback';
    protected string $description = 'Rollback the last database migration';

    public function handle(): int
    {
        $steps = (int)($this->option('step') ?? 1);

        $this->warning("Rolling back migrations (steps: {$steps})...");
        $this->newLine();

        $migrator = new Migrator(ROOT . '/database/migrations');
        
        $migrator->setOutput(function (string $message) {
            $this->line("  {$message}");
        });

        $migrations = $migrator->rollback($steps);

        if (empty($migrations)) {
            $this->info('Nothing to rollback.');
            return 0;
        }

        $this->newLine();
        $this->success('Rollback completed successfully!');
        $this->line("  Rolled back: " . count($migrations) . " migrations");

        return 0;
    }
}

