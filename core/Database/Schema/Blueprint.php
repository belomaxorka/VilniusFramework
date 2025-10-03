<?php declare(strict_types=1);

namespace Core\Database\Schema;

/**
 * Database Schema Blueprint
 * 
 * Построитель схемы таблицы для миграций
 */
class Blueprint
{
    /**
     * Название таблицы
     */
    private string $table;

    /**
     * Колонки для создания
     */
    private array $columns = [];

    /**
     * Индексы
     */
    private array $indexes = [];

    /**
     * Внешние ключи
     */
    private array $foreignKeys = [];

    /**
     * Команды для выполнения
     */
    private array $commands = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Получить название таблицы
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Auto-increment ID
     */
    public function id(string $name = 'id'): Column
    {
        return $this->bigIncrements($name);
    }

    /**
     * Big integer auto-increment
     */
    public function bigIncrements(string $name): Column
    {
        $column = new Column($name, 'bigint');
        $column->autoIncrement()->unsigned()->primary();
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Integer auto-increment
     */
    public function increments(string $name): Column
    {
        $column = new Column($name, 'integer');
        $column->autoIncrement()->unsigned()->primary();
        $this->columns[] = $column;
        return $column;
    }

    /**
     * String column
     */
    public function string(string $name, int $length = 255): Column
    {
        $column = new Column($name, 'varchar', $length);
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Text column
     */
    public function text(string $name): Column
    {
        $column = new Column($name, 'text');
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Integer column
     */
    public function integer(string $name): Column
    {
        $column = new Column($name, 'integer');
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Big integer column
     */
    public function bigInteger(string $name): Column
    {
        $column = new Column($name, 'bigint');
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Tiny integer column
     */
    public function tinyInteger(string $name): Column
    {
        $column = new Column($name, 'tinyint');
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Small integer column
     */
    public function smallInteger(string $name): Column
    {
        $column = new Column($name, 'smallint');
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Boolean column
     */
    public function boolean(string $name): Column
    {
        $column = new Column($name, 'boolean');
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Decimal column
     */
    public function decimal(string $name, int $precision = 8, int $scale = 2): Column
    {
        $column = new Column($name, 'decimal', $precision, $scale);
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Float column
     */
    public function float(string $name, int $precision = 8, int $scale = 2): Column
    {
        $column = new Column($name, 'float', $precision, $scale);
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Double column
     */
    public function double(string $name, int $precision = 8, int $scale = 2): Column
    {
        $column = new Column($name, 'double', $precision, $scale);
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Date column
     */
    public function date(string $name): Column
    {
        $column = new Column($name, 'date');
        $this->columns[] = $column;
        return $column;
    }

    /**
     * DateTime column
     */
    public function dateTime(string $name): Column
    {
        $column = new Column($name, 'datetime');
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Timestamp column
     */
    public function timestamp(string $name): Column
    {
        $column = new Column($name, 'timestamp');
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Timestamps (created_at, updated_at)
     */
    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    /**
     * Soft deletes (deleted_at)
     */
    public function softDeletes(string $name = 'deleted_at'): Column
    {
        return $this->timestamp($name)->nullable();
    }

    /**
     * JSON column
     */
    public function json(string $name): Column
    {
        $column = new Column($name, 'json');
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Enum column
     */
    public function enum(string $name, array $values): Column
    {
        $column = new Column($name, 'enum');
        $column->setAllowedValues($values);
        $this->columns[] = $column;
        return $column;
    }

    /**
     * UUID column
     */
    public function uuid(string $name): Column
    {
        $column = new Column($name, 'char', 36);
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Foreign ID column
     */
    public function foreignId(string $name): Column
    {
        return $this->bigInteger($name)->unsigned();
    }

    /**
     * Добавить индекс
     */
    public function index(string|array $columns, ?string $name = null): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $name = $name ?? $this->createIndexName('index', $columns);
        
        $this->indexes[] = [
            'type' => 'index',
            'name' => $name,
            'columns' => $columns,
        ];

        return $this;
    }

    /**
     * Добавить уникальный индекс
     */
    public function unique(string|array $columns, ?string $name = null): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $name = $name ?? $this->createIndexName('unique', $columns);
        
        $this->indexes[] = [
            'type' => 'unique',
            'name' => $name,
            'columns' => $columns,
        ];

        return $this;
    }

    /**
     * Добавить внешний ключ
     */
    public function foreign(string|array $columns): ForeignKey
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $foreignKey = new ForeignKey($columns);
        $this->foreignKeys[] = $foreignKey;
        return $foreignKey;
    }

    /**
     * Создать имя индекса
     */
    private function createIndexName(string $type, array $columns): string
    {
        $index = strtolower($this->table . '_' . implode('_', $columns) . '_' . $type);
        return str_replace(['-', '.'], '_', $index);
    }

    /**
     * Получить все колонки
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Получить все индексы
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * Получить все внешние ключи
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /**
     * Добавить команду
     */
    public function addCommand(string $command): void
    {
        $this->commands[] = $command;
    }

    /**
     * Получить команды
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Удалить колонку
     */
    public function dropColumn(string|array $columns): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        
        foreach ($columns as $column) {
            $this->addCommand("ALTER TABLE `{$this->table}` DROP COLUMN `{$column}`");
        }

        return $this;
    }

    /**
     * Переименовать колонку
     */
    public function renameColumn(string $from, string $to): self
    {
        $this->addCommand("ALTER TABLE `{$this->table}` RENAME COLUMN `{$from}` TO `{$to}`");
        return $this;
    }

    /**
     * Удалить индекс
     */
    public function dropIndex(string|array $index): self
    {
        $index = is_array($index) ? $index : [$index];
        
        foreach ($index as $indexName) {
            $this->addCommand("ALTER TABLE `{$this->table}` DROP INDEX `{$indexName}`");
        }

        return $this;
    }

    /**
     * Удалить внешний ключ
     */
    public function dropForeign(string|array $index): self
    {
        $index = is_array($index) ? $index : [$index];
        
        foreach ($index as $foreignKey) {
            $this->addCommand("ALTER TABLE `{$this->table}` DROP FOREIGN KEY `{$foreignKey}`");
        }

        return $this;
    }
}

