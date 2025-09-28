<?php declare(strict_types=1);

use Core\Database\DatabaseManager;
use Core\Database\Exceptions\ConnectionException;
use Core\Database\Exceptions\QueryException;
use Core\Database\Drivers\MySqlDriver;
use Core\Database\Drivers\SqliteDriver;
use Core\Database\Drivers\PostgreSqlDriver;
use PDO;
use PDOStatement;

beforeEach(function (): void {
    $this->config = [
        'default' => 'test',
        'connections' => [
            'test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
            'mysql' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'port' => 3306,
                'database' => 'test_db',
                'username' => 'user',
                'password' => 'password',
                'charset' => 'utf8mb4',
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => 'localhost',
                'port' => 5432,
                'database' => 'test_db',
                'username' => 'user',
                'password' => 'password',
                'sslmode' => 'prefer',
            ],
        ],
    ];
});

it('creates database manager with config', function (): void {
    $db = new DatabaseManager($this->config);
    expect($db)->toBeInstanceOf(DatabaseManager::class);
});

it('gets default connection', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection();
    
    expect($connection)->toBeInstanceOf(PDO::class);
});

it('gets named connection', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection('test');
    
    expect($connection)->toBeInstanceOf(PDO::class);
});

it('throws exception for non-existent connection', function (): void {
    $db = new DatabaseManager($this->config);
    
    expect(fn() => $db->connection('nonexistent'))
        ->toThrow(ConnectionException::class, 'Database connection [nonexistent] not configured.');
});

it('throws exception for unsupported driver', function (): void {
    $config = [
        'default' => 'test',
        'connections' => [
            'test' => [
                'driver' => 'unsupported',
                'database' => 'test',
            ],
        ],
    ];
    
    $db = new DatabaseManager($config);
    
    expect(fn() => $db->connection())
        ->toThrow(ConnectionException::class, 'Database driver [unsupported] not supported.');
});

it('reuses existing connections', function (): void {
    $db = new DatabaseManager($this->config);
    $connection1 = $db->connection();
    $connection2 = $db->connection();
    
    expect($connection1)->toBe($connection2);
});

it('executes select queries', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection();
    
    // Создаем тестовую таблицу
    $connection->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
    $connection->exec("INSERT INTO test_table (name) VALUES ('test1'), ('test2')");
    
    $results = $db->select('SELECT * FROM test_table ORDER BY id');
    
    expect($results)->toHaveCount(2);
    expect($results[0]['name'])->toBe('test1');
    expect($results[1]['name'])->toBe('test2');
});

it('executes select queries with bindings', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection();
    
    $connection->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
    $connection->exec("INSERT INTO test_table (name) VALUES ('test1'), ('test2')");
    
    $results = $db->select('SELECT * FROM test_table WHERE name = ?', ['test1']);
    
    expect($results)->toHaveCount(1);
    expect($results[0]['name'])->toBe('test1');
});

it('executes selectOne queries', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection();
    
    $connection->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
    $connection->exec("INSERT INTO test_table (name) VALUES ('test1')");
    
    $result = $db->selectOne('SELECT * FROM test_table WHERE name = ?', ['test1']);
    
    expect($result)->toBeArray();
    expect($result['name'])->toBe('test1');
});

it('returns null for selectOne when no results', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection();
    
    $connection->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
    
    $result = $db->selectOne('SELECT * FROM test_table WHERE name = ?', ['nonexistent']);
    
    expect($result)->toBeNull();
});

it('executes insert queries', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection();
    
    $connection->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
    
    $result = $db->insert('INSERT INTO test_table (name) VALUES (?)', ['test_insert']);
    
    expect($result)->toBeTrue();
    
    $count = $connection->query('SELECT COUNT(*) FROM test_table')->fetchColumn();
    expect($count)->toBe(1);
});

it('executes update queries', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection();
    
    $connection->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
    $connection->exec("INSERT INTO test_table (name) VALUES ('old_name')");
    
    $affectedRows = $db->update('UPDATE test_table SET name = ? WHERE name = ?', ['new_name', 'old_name']);
    
    expect($affectedRows)->toBe(1);
    
    $result = $connection->query('SELECT name FROM test_table')->fetchColumn();
    expect($result)->toBe('new_name');
});

it('executes delete queries', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection();
    
    $connection->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
    $connection->exec("INSERT INTO test_table (name) VALUES ('test1'), ('test2')");
    
    $affectedRows = $db->delete('DELETE FROM test_table WHERE name = ?', ['test1']);
    
    expect($affectedRows)->toBe(1);
    
    $count = $connection->query('SELECT COUNT(*) FROM test_table')->fetchColumn();
    expect($count)->toBe(1);
});

it('executes statement queries', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection();
    
    $result = $db->statement('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
    
    expect($result)->toBeTrue();
    
    // Проверяем, что таблица создана
    $tables = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='test_table'")->fetchAll();
    expect($tables)->toHaveCount(1);
});

it('handles transactions', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection();
    
    $connection->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
    
    $result = $db->transaction(function ($db) {
        $db->insert('INSERT INTO test_table (name) VALUES (?)', ['test1']);
        $db->insert('INSERT INTO test_table (name) VALUES (?)', ['test2']);
        return 'success';
    });
    
    expect($result)->toBe('success');
    
    $count = $connection->query('SELECT COUNT(*) FROM test_table')->fetchColumn();
    expect($count)->toBe(2);
});

it('rolls back transactions on exception', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection();
    
    $connection->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
    
    expect(fn() => $db->transaction(function ($db) {
        $db->insert('INSERT INTO test_table (name) VALUES (?)', ['test1']);
        throw new Exception('Test exception');
    }))->toThrow(Exception::class, 'Test exception');
    
    $count = $connection->query('SELECT COUNT(*) FROM test_table')->fetchColumn();
    expect($count)->toBe(0);
});

it('manages transaction methods', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection();
    
    $connection->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
    
    $beginResult = $db->beginTransaction();
    expect($beginResult)->toBeTrue();
    
    $db->insert('INSERT INTO test_table (name) VALUES (?)', ['test1']);
    
    $commitResult = $db->commit();
    expect($commitResult)->toBeTrue();
    
    $count = $connection->query('SELECT COUNT(*) FROM test_table')->fetchColumn();
    expect($count)->toBe(1);
});

it('rolls back transactions manually', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection();
    
    $connection->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
    
    $db->beginTransaction();
    $db->insert('INSERT INTO test_table (name) VALUES (?)', ['test1']);
    $rollbackResult = $db->rollback();
    
    expect($rollbackResult)->toBeTrue();
    
    $count = $connection->query('SELECT COUNT(*) FROM test_table')->fetchColumn();
    expect($count)->toBe(0);
});

it('gets last insert id', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection();
    
    $connection->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
    
    $db->insert('INSERT INTO test_table (name) VALUES (?)', ['test1']);
    $lastId = $db->lastInsertId();
    
    expect($lastId)->toBe('1');
});

it('adds custom drivers', function (): void {
    $db = new DatabaseManager($this->config);
    
    $db->addDriver('custom', MySqlDriver::class);
    
    // Проверяем, что драйвер добавлен (через рефлексию)
    $reflection = new ReflectionClass($db);
    $driversProperty = $reflection->getProperty('drivers');
    $driversProperty->setAccessible(true);
    $drivers = $driversProperty->getValue($db);
    
    expect($drivers)->toHaveKey('custom');
    expect($drivers['custom'])->toBe(MySqlDriver::class);
});

it('throws exception when adding non-existent driver class', function (): void {
    $db = new DatabaseManager($this->config);
    
    expect(fn() => $db->addDriver('custom', 'NonExistentClass'))
        ->toThrow(Core\Database\Exceptions\DatabaseException::class, 'Driver class [NonExistentClass] does not exist.');
});

it('throws exception when adding driver that does not implement interface', function (): void {
    $db = new DatabaseManager($this->config);
    
    expect(fn() => $db->addDriver('custom', stdClass::class))
        ->toThrow(Core\Database\Exceptions\DatabaseException::class, 'Driver class must implement DatabaseDriverInterface.');
});

it('disconnects all connections', function (): void {
    $db = new DatabaseManager($this->config);
    
    // Создаем соединение
    $db->connection();
    
    // Отключаем все соединения
    $db->disconnect();
    
    // Проверяем, что соединения очищены (через рефлексию)
    $reflection = new ReflectionClass($db);
    $connectionsProperty = $reflection->getProperty('connections');
    $connectionsProperty->setAccessible(true);
    $connections = $connectionsProperty->getValue($db);
    
    expect($connections)->toBeEmpty();
});

it('gets connection info', function (): void {
    $db = new DatabaseManager($this->config);
    
    $info = $db->getConnectionInfo();
    expect($info)->toBe($this->config['connections']['test']);
    
    $info = $db->getConnectionInfo('mysql');
    expect($info)->toBe($this->config['connections']['mysql']);
});

it('throws query exception on invalid sql', function (): void {
    $db = new DatabaseManager($this->config);
    
    expect(fn() => $db->select('INVALID SQL QUERY'))
        ->toThrow(QueryException::class, 'Query failed:');
});

it('throws query exception on insert with invalid data', function (): void {
    $db = new DatabaseManager($this->config);
    $connection = $db->connection();
    
    $connection->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
    
    expect(fn() => $db->insert('INSERT INTO test_table (name) VALUES (?)', [null]))
        ->toThrow(QueryException::class, 'Insert failed:');
});
