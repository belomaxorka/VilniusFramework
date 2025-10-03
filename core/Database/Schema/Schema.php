<?php declare(strict_types=1);

namespace Core\Database\Schema;

use Core\Database;
use Core\Database\DatabaseManager;

/**
 * Database Schema Builder
 * 
 * Построитель схемы базы данных
 */
class Schema
{
    private static ?DatabaseManager $database = null;

    /**
     * Установить database instance
     */
    public static function setDatabase(DatabaseManager $database): void
    {
        self::$database = $database;
    }

    /**
     * Получить database instance
     */
    private static function getDatabase(): DatabaseManager
    {
        if (self::$database === null) {
            self::$database = Database::getInstance();
        }

        return self::$database;
    }

    /**
     * Создать новую таблицу
     */
    public static function create(string $table, \Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $sql = self::compileCreate($blueprint);
        self::getDatabase()->statement($sql);

        // Создаем индексы
        foreach ($blueprint->getIndexes() as $index) {
            self::createIndex($table, $index);
        }

        // Создаем внешние ключи
        foreach ($blueprint->getForeignKeys() as $foreignKey) {
            self::createForeignKey($table, $foreignKey);
        }
    }

    /**
     * Изменить существующую таблицу
     */
    public static function table(string $table, \Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        // Выполняем команды
        foreach ($blueprint->getCommands() as $command) {
            self::getDatabase()->statement($command);
        }

        // Добавляем новые колонки
        foreach ($blueprint->getColumns() as $column) {
            $sql = self::compileAddColumn($table, $column);
            self::getDatabase()->statement($sql);
        }

        // Создаем индексы
        foreach ($blueprint->getIndexes() as $index) {
            self::createIndex($table, $index);
        }

        // Создаем внешние ключи
        foreach ($blueprint->getForeignKeys() as $foreignKey) {
            self::createForeignKey($table, $foreignKey);
        }
    }

    /**
     * Удалить таблицу
     */
    public static function drop(string $table): void
    {
        $sql = "DROP TABLE IF EXISTS `{$table}`";
        self::getDatabase()->statement($sql);
    }

    /**
     * Удалить таблицу, если она существует
     */
    public static function dropIfExists(string $table): void
    {
        self::drop($table);
    }

    /**
     * Переименовать таблицу
     */
    public static function rename(string $from, string $to): void
    {
        $sql = "RENAME TABLE `{$from}` TO `{$to}`";
        self::getDatabase()->statement($sql);
    }

    /**
     * Проверить существование таблицы
     */
    public static function hasTable(string $table): bool
    {
        $sql = "SHOW TABLES LIKE '{$table}'";
        $result = self::getDatabase()->select($sql);
        return !empty($result);
    }

    /**
     * Проверить существование колонки
     */
    public static function hasColumn(string $table, string $column): bool
    {
        $sql = "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'";
        $result = self::getDatabase()->select($sql);
        return !empty($result);
    }

    /**
     * Получить список колонок таблицы
     */
    public static function getColumns(string $table): array
    {
        $sql = "SHOW COLUMNS FROM `{$table}`";
        return self::getDatabase()->select($sql);
    }

    /**
     * Скомпилировать CREATE TABLE
     */
    private static function compileCreate(Blueprint $blueprint): string
    {
        $table = $blueprint->getTable();
        $columns = [];
        $primaryKeys = [];

        foreach ($blueprint->getColumns() as $column) {
            $columns[] = self::compileColumn($column);
            
            if ($column->isPrimary()) {
                $primaryKeys[] = $column->getName();
            }
        }

        $sql = "CREATE TABLE `{$table}` (\n";
        $sql .= "  " . implode(",\n  ", $columns);

        if (!empty($primaryKeys)) {
            $sql .= ",\n  PRIMARY KEY (`" . implode('`, `', $primaryKeys) . "`)";
        }

        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        return $sql;
    }

    /**
     * Скомпилировать колонку
     */
    private static function compileColumn(Column $column): string
    {
        $sql = "`{$column->getName()}` ";

        // Тип
        $type = strtoupper($column->getType());
        
        switch ($type) {
            case 'VARCHAR':
            case 'CHAR':
                $sql .= "{$type}({$column->getLength()})";
                break;
            
            case 'DECIMAL':
            case 'FLOAT':
            case 'DOUBLE':
                $precision = $column->getPrecision() ?? 8;
                $scale = $column->getScale() ?? 2;
                $sql .= "{$type}({$precision}, {$scale})";
                break;
            
            case 'ENUM':
                $values = array_map(fn($v) => "'{$v}'", $column->getAllowedValues());
                $sql .= "ENUM(" . implode(', ', $values) . ")";
                break;
            
            case 'INTEGER':
                $sql .= 'INT';
                break;
            
            case 'BOOLEAN':
                $sql .= 'TINYINT(1)';
                break;
            
            default:
                $sql .= $type;
        }

        // Unsigned
        if ($column->isUnsigned() && in_array($type, ['BIGINT', 'INT', 'TINYINT', 'SMALLINT', 'MEDIUMINT'])) {
            $sql .= ' UNSIGNED';
        }

        // Nullable
        if ($column->isNullable()) {
            $sql .= ' NULL';
        } else {
            $sql .= ' NOT NULL';
        }

        // Auto increment
        if ($column->isAutoIncrement()) {
            $sql .= ' AUTO_INCREMENT';
        }

        // Default
        if ($column->hasDefault()) {
            $default = $column->getDefault();
            
            if ($default === null) {
                $sql .= ' DEFAULT NULL';
            } elseif (is_bool($default)) {
                $sql .= ' DEFAULT ' . ($default ? '1' : '0');
            } elseif (is_numeric($default)) {
                $sql .= ' DEFAULT ' . $default;
            } else {
                $sql .= " DEFAULT '" . addslashes($default) . "'";
            }
        }

        // Comment
        if ($column->getComment()) {
            $sql .= " COMMENT '" . addslashes($column->getComment()) . "'";
        }

        return $sql;
    }

    /**
     * Скомпилировать добавление колонки
     */
    private static function compileAddColumn(string $table, Column $column): string
    {
        $sql = "ALTER TABLE `{$table}` ADD COLUMN " . self::compileColumn($column);

        if ($column->getAfter()) {
            $sql .= " AFTER `{$column->getAfter()}`";
        } elseif ($column->isFirst()) {
            $sql .= " FIRST";
        }

        return $sql;
    }

    /**
     * Создать индекс
     */
    private static function createIndex(string $table, array $index): void
    {
        $type = $index['type'] === 'unique' ? 'UNIQUE' : 'INDEX';
        $name = $index['name'];
        $columns = implode('`, `', $index['columns']);
        
        $sql = "ALTER TABLE `{$table}` ADD {$type} `{$name}` (`{$columns}`)";
        self::getDatabase()->statement($sql);
    }

    /**
     * Создать внешний ключ
     */
    private static function createForeignKey(string $table, ForeignKey $foreignKey): void
    {
        $columns = implode('`, `', $foreignKey->getColumns());
        $referencedTable = $foreignKey->getReferencedTable();
        $referencedColumns = implode('`, `', $foreignKey->getReferencedColumns());
        
        $name = $foreignKey->getName() ?? 'fk_' . $table . '_' . implode('_', $foreignKey->getColumns());
        
        $sql = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` ";
        $sql .= "FOREIGN KEY (`{$columns}`) REFERENCES `{$referencedTable}` (`{$referencedColumns}`)";
        
        if ($foreignKey->getOnDelete()) {
            $sql .= " ON DELETE {$foreignKey->getOnDelete()}";
        }
        
        if ($foreignKey->getOnUpdate()) {
            $sql .= " ON UPDATE {$foreignKey->getOnUpdate()}";
        }
        
        self::getDatabase()->statement($sql);
    }

    /**
     * Очистить таблицу
     */
    public static function truncate(string $table): void
    {
        $sql = "TRUNCATE TABLE `{$table}`";
        self::getDatabase()->statement($sql);
    }
}

