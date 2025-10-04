<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\Database\Migrations\Migrator;

/**
 * Base Migration Command
 * 
 * Базовый класс для команд миграций, убирает дублирование кода
 */
abstract class BaseMigrationCommand extends Command
{
    /**
     * Создать Migrator с output callback
     */
    protected function createMigrator(): Migrator
    {
        $migrator = new Migrator(ROOT . '/database/migrations');
        
        $migrator->setOutput(function (string $message) {
            $this->line("  {$message}");
        });

        return $migrator;
    }

    /**
     * Вывести результат миграций
     */
    protected function showResult(string $action, array $migrations): void
    {
        $this->newLine();
        $this->success("{$action} completed successfully!");
        $this->line("  " . ucfirst($action) . ": " . count($migrations) . " migrations");
    }

    /**
     * Вывести сообщение, что нечего делать
     */
    protected function showNothing(string $action): void
    {
        $this->info("Nothing to {$action}.");
    }
}

