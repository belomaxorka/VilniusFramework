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
            // Получаем через DI контейнер
            self::$database = \Core\Container::getInstance()->make(\Core\Contracts\DatabaseInterface::class);
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
        $driver = self::getDatabase()->getDriverName();

        $sql = match ($driver) {
            'mysql' => "SHOW TABLES LIKE '{$table}'",
            'pgsql' => "SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public' AND tablename = '{$table}'",
            'sqlite' => "SELECT name FROM sqlite_master WHERE type='table' AND name = '{$table}'",
            default => throw new \RuntimeException("Driver '{$driver}' is not supported")
        };

        $result = self::getDatabase()->select($sql);
        return !empty($result);
    }

    /**
     * Проверить существование колонки
     */
    public static function hasColumn(string $table, string $column): bool
    {
        $driver = self::getDatabase()->getDriverName();

        $sql = match ($driver) {
            'mysql' => "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'",
            'pgsql' => "SELECT column_name FROM information_schema.columns WHERE table_name = '{$table}' AND column_name = '{$column}'",
            'sqlite' => "PRAGMA table_info({$table})",
            default => throw new \RuntimeException("Driver '{$driver}' is not supported")
        };

        $result = self::getDatabase()->select($sql);
        
        if ($driver === 'sqlite') {
            // Для SQLite нужно проверить имя колонки в результате
            foreach ($result as $col) {
                if ($col['name'] === $column) {
                    return true;
                }
            }
            return false;
        }

        return !empty($result);
    }

    /**
     * Получить список колонок таблицы
     */
    public static function getColumns(string $table): array
    {
        $driver = self::getDatabase()->getDriverName();

        $sql = match ($driver) {
            'mysql' => "SHOW COLUMNS FROM `{$table}`",
            'pgsql' => "SELECT column_name FROM information_schema.columns WHERE table_name = '{$table}'",
            'sqlite' => "PRAGMA table_info({$table})",
            default => throw new \RuntimeException("Driver '{$driver}' is not supported")
        };

        return self::getDatabase()->select($sql);
    }

    /**
     * Скомпилировать CREATE TABLE
     */
    private static function compileCreate(Blueprint $blueprint): string
    {
        $driver = self::getDatabase()->getDriverName();
        $table = $blueprint->getTable();
        $columns = [];
        $primaryKeys = [];

        foreach ($blueprint->getColumns() as $column) {
            $columns[] = self::compileColumn($column);
            
            // В SQLite PRIMARY KEY с AUTOINCREMENT добавляется в определение колонки
            if ($column->isPrimary() && !($driver === 'sqlite' && $column->isAutoIncrement())) {
                $primaryKeys[] = $column->getName();
            }
        }

        $sql = "CREATE TABLE `{$table}` (\n";
        $sql .= "  " . implode(",\n  ", $columns);

        // Добавляем PRIMARY KEY constraint только если есть ключи
        // и это не SQLite с auto-increment (там PRIMARY KEY уже в колонке)
        if (!empty($primaryKeys)) {
            $sql .= ",\n  PRIMARY KEY (`" . implode('`, `', $primaryKeys) . "`)";
        }

        $sql .= "\n)";

        // Добавляем параметры в зависимости от драйвера
        if ($driver === 'mysql') {
            $sql .= " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }

        return $sql;
    }

    /**
     * Скомпилировать колонку
     */
    private static function compileColumn(Column $column): string
    {
        $driver = self::getDatabase()->getDriverName();
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
                // SQLite не поддерживает ENUM, используем TEXT с CHECK constraint
                if ($driver === 'sqlite') {
                    $sql .= 'TEXT';
                } else {
                    $values = array_map(fn($v) => "'{$v}'", $column->getAllowedValues());
                    $sql .= "ENUM(" . implode(', ', $values) . ")";
                }
                break;
            
            case 'INTEGER':
                $sql .= $driver === 'sqlite' ? 'INTEGER' : 'INT';
                break;
            
            case 'BIGINT':
                $sql .= $driver === 'sqlite' ? 'INTEGER' : 'BIGINT';
                break;
            
            case 'BOOLEAN':
                $sql .= $driver === 'sqlite' ? 'INTEGER' : 'TINYINT(1)';
                break;
            
            default:
                $sql .= $type;
        }

        // Unsigned (не поддерживается в SQLite)
        if ($driver !== 'sqlite' && $column->isUnsigned() && in_array($type, ['BIGINT', 'INT', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INTEGER'])) {
            $sql .= ' UNSIGNED';
        }

        // Primary Key (для SQLite с AUTOINCREMENT должен быть ДО AUTOINCREMENT)
        if ($driver === 'sqlite' && $column->isPrimary() && $column->isAutoIncrement()) {
            $sql .= ' PRIMARY KEY AUTOINCREMENT';
        } else {
            // Nullable
            if ($column->isNullable()) {
                $sql .= ' NULL';
            } else {
                $sql .= ' NOT NULL';
            }

            // Auto increment (для других драйверов)
            if ($column->isAutoIncrement() && $driver !== 'sqlite') {
                $sql .= ' AUTO_INCREMENT';
            }
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

        // Comment (не поддерживается в SQLite)
        if ($driver !== 'sqlite' && $column->getComment()) {
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

