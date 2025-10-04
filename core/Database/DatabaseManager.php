<?php declare(strict_types=1);

namespace Core\Database;

use Core\Database\Drivers\MySqlDriver;
use Core\Database\Drivers\PostgreSqlDriver;
use Core\Database\Drivers\SqliteDriver;
use Core\Database\Exceptions\ConnectionException;
use Core\Database\Exceptions\DatabaseException;
use Core\Database\Exceptions\QueryException;

use Exception;
use PDO;
use PDOException;

class DatabaseManager implements DatabaseInterface
{
    protected array $config;
    protected array $connections = [];
    protected array $drivers = [
        'mysql' => MySqlDriver::class,
        'pgsql' => PostgreSqlDriver::class,
        'sqlite' => SqliteDriver::class,
    ];
    protected ?string $defaultConnection = null;
    protected array $queryLog = [];
    protected bool $loggingQueries = false;
    protected int $reconnectAttempts = 3;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->defaultConnection = $config['default'] ?? null;
        $this->loggingQueries = $config['log_queries'] ?? false;

        // Настраиваем QueryDebugger для Debug Toolbar
        if (class_exists('\Core\QueryDebugger')) {
            if (isset($config['slow_query_threshold'])) {
                \Core\QueryDebugger::setSlowQueryThreshold((float)$config['slow_query_threshold']);
            }
        }
    }

    /**
     * Получить соединение с БД
     */
    public function connection(?string $name = null): PDO
    {
        $name = $name ?: $this->defaultConnection;

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * Создать новое соединение
     */
    protected function createConnection(string $name): PDO
    {
        if (!isset($this->config['connections'][$name])) {
            throw new ConnectionException("Database connection [{$name}] not configured.");
        }

        $config = $this->config['connections'][$name];
        $driverName = $config['driver'];

        if (!isset($this->drivers[$driverName])) {
            throw new ConnectionException("Database driver [{$driverName}] not supported.");
        }

        $driverClass = $this->drivers[$driverName];
        $driver = new $driverClass();

        try {
            return $driver->connect($config);
        } catch (PDOException $e) {
            throw new ConnectionException("Could not connect to database: " . $e->getMessage());
        }
    }

    /**
     * Переподключиться к базе данных
     */
    public function reconnect(?string $name = null): PDO
    {
        $name = $name ?: $this->defaultConnection;

        // Удаляем существующее соединение
        unset($this->connections[$name]);

        // Создаем новое
        return $this->connection($name);
    }

    /**
     * Выполнить SELECT запрос
     */
    public function select(string $query, array $bindings = []): array
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->connection()->prepare($query);
            $statement->execute($bindings);
            return $statement->fetchAll();
        });
    }

    /**
     * Выполнить SELECT запрос и получить одну запись
     */
    public function selectOne(string $query, array $bindings = []): ?array
    {
        $results = $this->select($query, $bindings);
        return $results[0] ?? null;
    }

    /**
     * Выполнить INSERT запрос
     */
    public function insert(string $query, array $bindings = []): bool
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->connection()->prepare($query);
            return $statement->execute($bindings);
        });
    }

    /**
     * Выполнить UPDATE запрос
     */
    public function update(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->connection()->prepare($query);
            $statement->execute($bindings);
            return $statement->rowCount();
        });
    }

    /**
     * Выполнить DELETE запрос
     */
    public function delete(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->connection()->prepare($query);
            $statement->execute($bindings);
            return $statement->rowCount();
        });
    }

    /**
     * Выполнить произвольный запрос
     */
    public function statement(string $query, array $bindings = []): bool
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            $statement = $this->connection()->prepare($query);
            return $statement->execute($bindings);
        });
    }

    /**
     * Выполнить запрос с логированием и обработкой ошибок
     */
    protected function run(string $query, array $bindings, callable $callback)
    {
        $start = microtime(true);

        try {
            $result = $callback($query, $bindings);
            $time = microtime(true) - $start;

            // Определяем количество затронутых строк
            $rows = 0;
            if (is_array($result)) {
                $rows = count($result);
            } elseif (is_int($result)) {
                $rows = $result;
            }

            // Логируем успешный запрос
            $this->logQuery($query, $bindings, $time, null, $rows);

            return $result;
        } catch (PDOException $e) {
            // Логируем неудачный запрос
            $this->logQuery($query, $bindings, microtime(true) - $start, $e->getMessage(), 0);

            // Пробуем переподключиться при потере соединения
            if ($this->causedByLostConnection($e)) {
                return $this->tryAgainIfCausedByLostConnection($e, $query, $bindings, $callback);
            }

            throw new QueryException("Query failed: " . $e->getMessage() . " | SQL: " . $query);
        }
    }

    /**
     * Попробовать выполнить запрос снова при потере соединения
     */
    protected function tryAgainIfCausedByLostConnection(PDOException $e, string $query, array $bindings, callable $callback)
    {
        for ($attempt = 1; $attempt <= $this->reconnectAttempts; $attempt++) {
            try {
                $this->reconnect();
                return $callback($query, $bindings);
            } catch (PDOException $e) {
                if ($attempt >= $this->reconnectAttempts) {
                    throw new QueryException("Query failed after {$this->reconnectAttempts} reconnection attempts: " . $e->getMessage());
                }

                // Небольшая задержка перед следующей попыткой
                usleep(100000 * $attempt); // 100ms, 200ms, 300ms...
            }
        }
    }

    /**
     * Проверить, вызвана ли ошибка потерей соединения
     */
    protected function causedByLostConnection(PDOException $e): bool
    {
        $message = $e->getMessage();

        $lostConnectionMessages = [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
        ];

        foreach ($lostConnectionMessages as $lostMessage) {
            if (stripos($message, $lostMessage) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Логировать запрос
     */
    protected function logQuery(string $query, array $bindings, float $time, ?string $error = null, int $rows = 0): void
    {
        $timeMs = round($time * 1000, 2); // в миллисекундах

        if ($this->loggingQueries) {
            $this->queryLog[] = [
                'query' => $query,
                'bindings' => $bindings,
                'time' => $timeMs,
                'error' => $error,
                'rows' => $rows,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }

        // Интеграция с QueryDebugger для Debug Toolbar
        if (class_exists('\Core\QueryDebugger')) {
            // Логируем в QueryDebugger (только если не в production)
            \Core\QueryDebugger::log(
                $query,
                $bindings,
                $timeMs,
                $rows
            );
        }
    }

    /**
     * Включить логирование запросов
     */
    public function enableQueryLog(): void
    {
        $this->loggingQueries = true;
    }

    /**
     * Выключить логирование запросов
     */
    public function disableQueryLog(): void
    {
        $this->loggingQueries = false;
    }

    /**
     * Получить лог запросов
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Очистить лог запросов
     */
    public function flushQueryLog(): void
    {
        $this->queryLog = [];
    }

    /**
     * Получить последний выполненный запрос
     */
    public function getLastQuery(): ?array
    {
        return end($this->queryLog) ?: null;
    }

    /**
     * Выполнить код в транзакции
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Начать транзакцию
     */
    public function beginTransaction(): bool
    {
        try {
            $pdo = $this->connection();

            // Проверяем, нет ли уже активной транзакции
            if ($pdo->inTransaction()) {
                return false;
            }

            return $pdo->beginTransaction();
        } catch (PDOException $e) {
            throw new DatabaseException("Could not begin transaction: " . $e->getMessage());
        }
    }

    /**
     * Подтвердить транзакцию
     */
    public function commit(): bool
    {
        try {
            $pdo = $this->connection();

            if (!$pdo->inTransaction()) {
                return false;
            }

            return $pdo->commit();
        } catch (PDOException $e) {
            throw new DatabaseException("Could not commit transaction: " . $e->getMessage());
        }
    }

    /**
     * Отменить транзакцию
     */
    public function rollback(): bool
    {
        try {
            $pdo = $this->connection();

            if (!$pdo->inTransaction()) {
                return false;
            }

            return $pdo->rollBack();
        } catch (PDOException $e) {
            throw new DatabaseException("Could not rollback transaction: " . $e->getMessage());
        }
    }

    /**
     * Проверить, активна ли транзакция
     */
    public function inTransaction(): bool
    {
        return $this->connection()->inTransaction();
    }

    /**
     * Получить ID последней вставленной записи
     */
    public function lastInsertId(): string
    {
        return $this->connection()->lastInsertId();
    }

    /**
     * Добавить кастомный драйвер
     */
    public function addDriver(string $name, string $driverClass): void
    {
        if (!class_exists($driverClass)) {
            throw new DatabaseException("Driver class [{$driverClass}] does not exist.");
        }

        if (!in_array(DatabaseDriverInterface::class, class_implements($driverClass))) {
            throw new DatabaseException("Driver class must implement DatabaseDriverInterface.");
        }

        $this->drivers[$name] = $driverClass;
    }

    /**
     * Закрыть все соединения
     */
    public function disconnect(): void
    {
        $this->connections = [];
    }

    /**
     * Закрыть конкретное соединение
     */
    public function disconnectFrom(string $name): void
    {
        unset($this->connections[$name]);
    }

    /**
     * Получить информацию о соединении
     */
    public function getConnectionInfo(?string $name = null): array
    {
        $name = $name ?: $this->defaultConnection;
        $info = $this->config['connections'][$name] ?? [];

        // Скрываем пароль в информации о соединении
        if (isset($info['password'])) {
            $info['password'] = '******';
        }

        return $info;
    }

    /**
     * Получить имя драйвера для соединения
     */
    public function getDriverName(?string $name = null): string
    {
        $info = $this->getConnectionInfo($name);
        return $info['driver'] ?? '';
    }

    /**
     * Получить имя базы данных
     */
    public function getDatabaseName(?string $name = null): string
    {
        $info = $this->getConnectionInfo($name);
        return $info['database'] ?? '';
    }

    /**
     * Установить количество попыток переподключения
     */
    public function setReconnectAttempts(int $attempts): void
    {
        $this->reconnectAttempts = max(1, $attempts);
    }

    /**
     * Получить таблицу через QueryBuilder
     */
    public function table(string $table): QueryBuilder
    {
        return (new QueryBuilder($this))->table($table);
    }

    /**
     * Выполнить raw запрос (alias для statement)
     */
    public function raw(string $query, array $bindings = []): bool
    {
        return $this->statement($query, $bindings);
    }

    /**
     * Получить список всех таблиц
     */
    public function getTables(): array
    {
        $driver = $this->getDriverName();

        $query = match ($driver) {
            'mysql' => "SHOW TABLES",
            'pgsql' => "SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'",
            'sqlite' => "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'",
            default => throw new DatabaseException("Getting tables is not supported for driver: {$driver}")
        };

        $results = $this->select($query);

        // Извлекаем имена таблиц из результата
        return array_map(fn($row) => reset($row), $results);
    }

    /**
     * Проверить существование таблицы
     */
    public function hasTable(string $table): bool
    {
        $tables = $this->getTables();
        return in_array($table, $tables);
    }

    /**
     * Получить колонки таблицы
     */
    public function getColumns(string $table): array
    {
        $driver = $this->getDriverName();

        $query = match ($driver) {
            'mysql' => "SHOW COLUMNS FROM {$table}",
            'pgsql' => "SELECT column_name FROM information_schema.columns WHERE table_name = '{$table}'",
            'sqlite' => "PRAGMA table_info({$table})",
            default => throw new DatabaseException("Getting columns is not supported for driver: {$driver}")
        };

        return $this->select($query);
    }

    /**
     * Получить статистику производительности
     */
    public function getQueryStats(): array
    {
        if (empty($this->queryLog)) {
            return [
                'total_queries' => 0,
                'total_time' => 0,
                'avg_time' => 0,
                'max_time' => 0,
                'min_time' => 0,
                'failed_queries' => 0
            ];
        }

        $times = array_column($this->queryLog, 'time');
        $failedCount = count(array_filter($this->queryLog, fn($log) => $log['error'] !== null));

        return [
            'total_queries' => count($this->queryLog),
            'total_time' => round(array_sum($times), 2),
            'avg_time' => round(array_sum($times) / count($times), 2),
            'max_time' => max($times),
            'min_time' => min($times),
            'failed_queries' => $failedCount
        ];
    }

    /**
     * Получить медленные запросы (больше указанного времени в мс)
     */
    public function getSlowQueries(float $threshold = 100): array
    {
        return array_filter($this->queryLog, fn($log) => $log['time'] > $threshold);
    }
}
