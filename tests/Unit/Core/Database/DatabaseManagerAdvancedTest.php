<?php declare(strict_types=1);

use Core\Database\DatabaseManager;
use Core\Database\Exceptions\DatabaseException;
use Core\Database\Exceptions\QueryException;

beforeEach(function (): void {
    $this->config = [
        'default' => 'test',
        'log_queries' => false,
        'connections' => [
            'test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ],
    ];

    $this->db = new DatabaseManager($this->config);
    $this->connection = $this->db->connection();

    // Создаем тестовую таблицу
    $this->connection->exec('
        CREATE TABLE test_table (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            value INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');

    // Вставляем тестовые данные
    $this->connection->exec("
        INSERT INTO test_table (name, value) VALUES
        ('item1', 10),
        ('item2', 20),
        ('item3', 30)
    ");
});

// ============================================================================
// Query Logging Tests
// ============================================================================

it('enables query logging', function (): void {
    $this->db->enableQueryLog();
    
    $this->db->select('SELECT * FROM test_table');
    
    $log = $this->db->getQueryLog();
    expect($log)->toHaveCount(1);
});

it('disables query logging', function (): void {
    $this->db->enableQueryLog();
    $this->db->select('SELECT * FROM test_table');
    
    $this->db->disableQueryLog();
    $this->db->select('SELECT * FROM test_table');
    
    $log = $this->db->getQueryLog();
    expect($log)->toHaveCount(1); // Только первый запрос
});

it('logs query with bindings', function (): void {
    $this->db->enableQueryLog();
    
    $this->db->select('SELECT * FROM test_table WHERE id = ?', [1]);
    
    $log = $this->db->getQueryLog();
    expect($log[0])->toHaveKey('query');
    expect($log[0])->toHaveKey('bindings');
    expect($log[0])->toHaveKey('time');
    expect($log[0])->toHaveKey('timestamp');
    
    expect($log[0]['query'])->toBe('SELECT * FROM test_table WHERE id = ?');
    expect($log[0]['bindings'])->toBe([1]);
});

it('logs query execution time', function (): void {
    $this->db->enableQueryLog();
    
    $this->db->select('SELECT * FROM test_table');
    
    $log = $this->db->getQueryLog();
    expect($log[0]['time'])->toBeFloat();
    expect($log[0]['time'])->toBeGreaterThanOrEqual(0);
});

it('logs failed queries', function (): void {
    $this->db->enableQueryLog();
    
    try {
        $this->db->select('INVALID SQL QUERY');
    } catch (QueryException $e) {
        // Ожидаем исключение
    }
    
    $log = $this->db->getQueryLog();
    expect($log[0])->toHaveKey('error');
    expect($log[0]['error'])->not->toBeNull();
});

it('gets last query', function (): void {
    $this->db->enableQueryLog();
    
    $this->db->select('SELECT * FROM test_table WHERE id = ?', [1]);
    $this->db->select('SELECT * FROM test_table WHERE id = ?', [2]);
    
    $lastQuery = $this->db->getLastQuery();
    expect($lastQuery['query'])->toBe('SELECT * FROM test_table WHERE id = ?');
    expect($lastQuery['bindings'])->toBe([2]);
});

it('returns null for last query when no queries', function (): void {
    $lastQuery = $this->db->getLastQuery();
    expect($lastQuery)->toBeNull();
});

it('flushes query log', function (): void {
    $this->db->enableQueryLog();
    
    $this->db->select('SELECT * FROM test_table');
    expect($this->db->getQueryLog())->toHaveCount(1);
    
    $this->db->flushQueryLog();
    expect($this->db->getQueryLog())->toHaveCount(0);
});

it('logs multiple queries', function (): void {
    $this->db->enableQueryLog();
    
    $this->db->select('SELECT * FROM test_table');
    $this->db->insert('INSERT INTO test_table (name, value) VALUES (?, ?)', ['item4', 40]);
    $this->db->update('UPDATE test_table SET value = ? WHERE id = ?', [50, 1]);
    $this->db->delete('DELETE FROM test_table WHERE id = ?', [3]);
    
    $log = $this->db->getQueryLog();
    expect($log)->toHaveCount(4);
});

// ============================================================================
// Query Stats Tests
// ============================================================================

it('calculates query statistics', function (): void {
    $this->db->enableQueryLog();
    
    $this->db->select('SELECT * FROM test_table');
    $this->db->select('SELECT * FROM test_table WHERE id = ?', [1]);
    $this->db->select('SELECT * FROM test_table WHERE id = ?', [2]);
    
    $stats = $this->db->getQueryStats();
    
    expect($stats)->toHaveKey('total_queries');
    expect($stats)->toHaveKey('total_time');
    expect($stats)->toHaveKey('avg_time');
    expect($stats)->toHaveKey('max_time');
    expect($stats)->toHaveKey('min_time');
    expect($stats)->toHaveKey('failed_queries');
    
    expect($stats['total_queries'])->toBe(3);
    expect($stats['avg_time'])->toBeFloat();
    expect($stats['failed_queries'])->toBe(0);
});

it('returns empty stats when no queries', function (): void {
    $stats = $this->db->getQueryStats();
    
    expect($stats['total_queries'])->toBe(0);
    expect($stats['total_time'])->toBe(0);
    expect($stats['avg_time'])->toBe(0);
});

it('counts failed queries in stats', function (): void {
    $this->db->enableQueryLog();
    
    $this->db->select('SELECT * FROM test_table');
    
    try {
        $this->db->select('INVALID SQL');
    } catch (QueryException $e) {}
    
    $stats = $this->db->getQueryStats();
    expect($stats['total_queries'])->toBe(2);
    expect($stats['failed_queries'])->toBe(1);
});

// ============================================================================
// Slow Queries Tests
// ============================================================================

it('identifies slow queries', function (): void {
    $this->db->enableQueryLog();
    
    // Эти запросы должны быть быстрыми
    $this->db->select('SELECT * FROM test_table');
    
    $slowQueries = $this->db->getSlowQueries(1000); // > 1 секунда
    expect($slowQueries)->toHaveCount(0);
});

it('filters slow queries by threshold', function (): void {
    $this->db->enableQueryLog();
    
    $this->db->select('SELECT * FROM test_table');
    
    // Получаем все запросы медленнее 0ms (все запросы)
    $allQueries = $this->db->getSlowQueries(0);
    expect($allQueries)->toHaveCount(1);
});

// ============================================================================
// Reconnection Tests
// ============================================================================

it('reconnects to database', function (): void {
    $connection1 = $this->db->connection();
    
    $this->db->reconnect();
    
    $connection2 = $this->db->connection();
    
    // После переподключения должен быть новый объект PDO
    expect($connection1)->not->toBe($connection2);
});

it('sets reconnect attempts', function (): void {
    $this->db->setReconnectAttempts(5);
    
    // Проверяем через рефлексию
    $reflection = new ReflectionClass($this->db);
    $attemptsProperty = $reflection->getProperty('reconnectAttempts');
    $attemptsProperty->setAccessible(true);
    $attempts = $attemptsProperty->getValue($this->db);
    
    expect($attempts)->toBe(5);
});

it('limits minimum reconnect attempts to 1', function (): void {
    $this->db->setReconnectAttempts(0);
    
    $reflection = new ReflectionClass($this->db);
    $attemptsProperty = $reflection->getProperty('reconnectAttempts');
    $attemptsProperty->setAccessible(true);
    $attempts = $attemptsProperty->getValue($this->db);
    
    expect($attempts)->toBe(1);
});

// ============================================================================
// Database Info Tests
// ============================================================================

it('gets driver name', function (): void {
    $driver = $this->db->getDriverName();
    expect($driver)->toBe('sqlite');
});

it('gets database name', function (): void {
    $dbName = $this->db->getDatabaseName();
    expect($dbName)->toBe(':memory:');
});

it('hides password in connection info', function (): void {
    $config = [
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'test',
                'username' => 'root',
                'password' => 'secret123',
            ],
        ],
    ];
    
    $db = new DatabaseManager($config);
    $info = $db->getConnectionInfo();
    
    expect($info['password'])->toBe('******');
});

// ============================================================================
// Table Management Tests
// ============================================================================

it('gets list of tables', function (): void {
    $tables = $this->db->getTables();
    
    expect($tables)->toBeArray();
    expect($tables)->toContain('test_table');
});

it('checks if table exists', function (): void {
    expect($this->db->hasTable('test_table'))->toBeTrue();
    expect($this->db->hasTable('nonexistent_table'))->toBeFalse();
});

it('gets table columns', function (): void {
    $columns = $this->db->getColumns('test_table');
    
    expect($columns)->toBeArray();
    expect(count($columns))->toBeGreaterThan(0);
});

// ============================================================================
// Transaction Tests
// ============================================================================

it('checks if in transaction', function (): void {
    expect($this->db->inTransaction())->toBeFalse();
    
    $this->db->beginTransaction();
    expect($this->db->inTransaction())->toBeTrue();
    
    $this->db->commit();
    expect($this->db->inTransaction())->toBeFalse();
});

it('does not begin transaction when already in transaction', function (): void {
    $this->db->beginTransaction();
    $result = $this->db->beginTransaction();
    
    expect($result)->toBeFalse();
    
    $this->db->rollback();
});

it('does not commit when not in transaction', function (): void {
    $result = $this->db->commit();
    expect($result)->toBeFalse();
});

it('does not rollback when not in transaction', function (): void {
    $result = $this->db->rollback();
    expect($result)->toBeFalse();
});

it('handles nested transaction attempt', function (): void {
    $this->db->beginTransaction();
    
    // Попытка начать вложенную транзакцию
    $result = $this->db->beginTransaction();
    expect($result)->toBeFalse();
    
    $this->db->commit();
});

// ============================================================================
// Connection Management Tests
// ============================================================================

it('disconnects from database', function (): void {
    $this->db->connection(); // Создаем соединение
    
    $this->db->disconnect();
    
    $reflection = new ReflectionClass($this->db);
    $connectionsProperty = $reflection->getProperty('connections');
    $connectionsProperty->setAccessible(true);
    $connections = $connectionsProperty->getValue($this->db);
    
    expect($connections)->toBeEmpty();
});

it('disconnects from specific connection', function (): void {
    $this->db->connection('test');
    
    $this->db->disconnectFrom('test');
    
    $reflection = new ReflectionClass($this->db);
    $connectionsProperty = $reflection->getProperty('connections');
    $connectionsProperty->setAccessible(true);
    $connections = $connectionsProperty->getValue($this->db);
    
    expect($connections)->not->toHaveKey('test');
});

// ============================================================================
// Raw Query Tests
// ============================================================================

it('executes raw query', function (): void {
    $result = $this->db->raw('INSERT INTO test_table (name, value) VALUES (?, ?)', ['raw', 100]);
    expect($result)->toBeTrue();
    
    $count = $this->connection->query('SELECT COUNT(*) FROM test_table WHERE name = "raw"')->fetchColumn();
    expect($count)->toBe(1);
});

// ============================================================================
// Table Helper Tests
// ============================================================================

it('creates query builder from table method', function (): void {
    $query = $this->db->table('test_table');
    
    expect($query)->toBeInstanceOf(Core\Database\QueryBuilder::class);
});

it('executes query from table method', function (): void {
    $results = $this->db->table('test_table')
        ->where('value', '>', 15)
        ->get();
    
    expect($results)->toHaveCount(2);
});

// ============================================================================
// Query Logging Config Tests
// ============================================================================

it('enables logging from config', function (): void {
    $config = [
        'default' => 'test',
        'log_queries' => true,
        'connections' => [
            'test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ],
    ];
    
    $db = new DatabaseManager($config);
    $connection = $db->connection();
    $connection->exec('CREATE TABLE temp (id INTEGER)');
    
    $db->select('SELECT * FROM temp');
    
    $log = $db->getQueryLog();
    expect($log)->not->toBeEmpty();
});

// ============================================================================
// Error Handling Tests
// ============================================================================

it('throws database exception on commit error', function (): void {
    // Пытаемся сделать commit без активной транзакции
    // Это не должно выбросить исключение, а вернуть false
    $result = $this->db->commit();
    expect($result)->toBeFalse();
});

it('throws database exception on rollback error', function (): void {
    // Пытаемся сделать rollback без активной транзакции
    // Это не должно выбросить исключение, а вернуть false
    $result = $this->db->rollback();
    expect($result)->toBeFalse();
});

// ============================================================================
// Multiple Connection Tests
// ============================================================================

it('handles multiple connection configurations', function (): void {
    $config = [
        'default' => 'test1',
        'connections' => [
            'test1' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
            'test2' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ],
    ];
    
    $db = new DatabaseManager($config);
    
    $conn1 = $db->connection('test1');
    $conn2 = $db->connection('test2');
    
    expect($conn1)->not->toBe($conn2);
});

// ============================================================================
// Performance Tests
// ============================================================================

it('handles large number of queries efficiently', function (): void {
    $this->db->enableQueryLog();
    
    for ($i = 0; $i < 100; $i++) {
        $this->db->select('SELECT * FROM test_table LIMIT 1');
    }
    
    $stats = $this->db->getQueryStats();
    expect($stats['total_queries'])->toBe(100);
    expect($stats['avg_time'])->toBeFloat();
});

it('logs timestamps for queries', function (): void {
    $this->db->enableQueryLog();
    
    $this->db->select('SELECT * FROM test_table');
    
    $log = $this->db->getQueryLog();
    expect($log[0]['timestamp'])->toBeString();
    expect(strtotime($log[0]['timestamp']))->toBeInt();
});

// ============================================================================
// Edge Cases Tests
// ============================================================================

it('handles empty query log gracefully', function (): void {
    $log = $this->db->getQueryLog();
    expect($log)->toBeArray();
    expect($log)->toBeEmpty();
});

it('handles getLastQuery when no queries executed', function (): void {
    $lastQuery = $this->db->getLastQuery();
    expect($lastQuery)->toBeNull();
});

it('handles getSlowQueries when no queries logged', function (): void {
    $slowQueries = $this->db->getSlowQueries(100);
    expect($slowQueries)->toBeArray();
    expect($slowQueries)->toBeEmpty();
});

it('preserves query log after disabling', function (): void {
    $this->db->enableQueryLog();
    $this->db->select('SELECT * FROM test_table');
    
    $logBefore = $this->db->getQueryLog();
    
    $this->db->disableQueryLog();
    
    $logAfter = $this->db->getQueryLog();
    
    expect($logAfter)->toBe($logBefore);
});
