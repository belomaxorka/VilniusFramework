<?php declare(strict_types=1);

namespace Core\Database\Migrations;

use Core\Database;
use Core\Database\DatabaseManager;
use Core\Database\Schema\Schema;

/**
 * Migration Repository
 * 
 * Управление таблицей миграций
 */
class MigrationRepository
{
    /**
     * Название таблицы миграций
     */
    private string $table = 'migrations';

    /**
     * Database instance
     */
    private DatabaseManager $database;

    public function __construct()
    {
        // Получаем через DI контейнер
        $this->database = \Core\Container::getInstance()->make(\Core\Contracts\DatabaseInterface::class);
    }

    /**
     * Создать таблицу миграций
     */
    public function createRepository(): void
    {
        if (Schema::hasTable($this->table)) {
            return;
        }

        Schema::create($this->table, function ($table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Удалить таблицу миграций
     */
    public function deleteRepository(): void
    {
        Schema::dropIfExists($this->table);
    }

    /**
     * Получить выполненные миграции
     */
    public function getRan(): array
    {
        if (!Schema::hasTable($this->table)) {
            return [];
        }

        $results = $this->database->select(
            "SELECT migration FROM {$this->table} ORDER BY batch ASC, migration ASC"
        );

        return array_column($results, 'migration');
    }

    /**
     * Получить последний номер batch
     */
    public function getLastBatchNumber(): int
    {
        if (!Schema::hasTable($this->table)) {
            return 0;
        }

        $result = $this->database->selectOne(
            "SELECT MAX(batch) as batch FROM {$this->table}"
        );

        return (int)($result['batch'] ?? 0);
    }

    /**
     * Получить миграции последнего batch
     */
    public function getLast(): array
    {
        if (!Schema::hasTable($this->table)) {
            return [];
        }

        $batch = $this->getLastBatchNumber();

        $results = $this->database->select(
            "SELECT migration FROM {$this->table} WHERE batch = ? ORDER BY migration DESC",
            [$batch]
        );

        return array_column($results, 'migration');
    }

    /**
     * Получить все миграции с информацией о batch
     */
    public function getMigrations(): array
    {
        if (!Schema::hasTable($this->table)) {
            return [];
        }

        return $this->database->select(
            "SELECT * FROM {$this->table} ORDER BY batch ASC, migration ASC"
        );
    }

    /**
     * Записать миграцию как выполненную
     */
    public function log(string $migration, int $batch): void
    {
        $this->database->insert(
            "INSERT INTO {$this->table} (migration, batch, created_at) VALUES (?, ?, ?)",
            [$migration, $batch, date('Y-m-d H:i:s')]
        );
    }

    /**
     * Удалить запись о миграции
     */
    public function delete(string $migration): void
    {
        $this->database->delete(
            "DELETE FROM {$this->table} WHERE migration = ?",
            [$migration]
        );
    }

    /**
     * Получить следующий номер batch
     */
    public function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Проверить существование таблицы миграций
     */
    public function repositoryExists(): bool
    {
        return Schema::hasTable($this->table);
    }
}

