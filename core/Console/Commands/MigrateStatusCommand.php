<?php declare(strict_types=1);

namespace Core\Console\Commands;

/**
 * Migrate Status Command
 * 
 * Показать статус миграций
 */
class MigrateStatusCommand extends BaseMigrationCommand
{
    protected string $signature = 'migrate:status';
    protected string $description = 'Show the status of each migration';

    public function handle(): int
    {
        $migrator = $this->createMigrator();
        $status = $migrator->status();

        if (empty($status)) {
            $this->info('No migrations found.');
            return 0;
        }

        $this->info('Migration Status:');
        $this->newLine();

        $rows = [];
        foreach ($status as $migration) {
            $rows[] = [
                $migration['ran'] ? '✓' : '✗',
                $migration['migration'],
                $migration['batch'] ?? 'Pending',
            ];
        }

        $this->table(
            ['Ran?', 'Migration', 'Batch'],
            $rows
        );

        return 0;
    }
}

