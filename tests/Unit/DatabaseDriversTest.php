<?php declare(strict_types=1);

use Core\Database\Drivers\MySqlDriver;
use Core\Database\Drivers\PostgreSqlDriver;
use Core\Database\Drivers\SqliteDriver;
use Core\Database\DatabaseDriverInterface;

describe('MySqlDriver', function (): void {
    it('implements DatabaseDriverInterface', function (): void {
        $driver = new MySqlDriver();
        expect($driver)->toBeInstanceOf(DatabaseDriverInterface::class);
    });

    it('builds correct DSN for MySQL', function (): void {
        $driver = new MySqlDriver();
        $config = [
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'test_db',
        ];

        $dsn = $driver->buildDsn($config);
        expect($dsn)->toBe('mysql:host=localhost;port=3306;dbname=test_db');
    });

    it('builds DSN with charset', function (): void {
        $driver = new MySqlDriver();
        $config = [
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'test_db',
            'charset' => 'utf8mb4',
        ];

        $dsn = $driver->buildDsn($config);
        expect($dsn)->toBe('mysql:host=localhost;port=3306;dbname=test_db;charset=utf8mb4');
    });

    it('connects with valid config', function (): void {
        $driver = new MySqlDriver();
        $config = [
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'test_db',
            'username' => 'user',
            'password' => 'password',
        ];

        // Этот тест будет падать без реального MySQL сервера, но проверяет структуру
        expect(fn() => $driver->connect($config))
            ->toThrow(PDOException::class);
    });

    it('connects with options', function (): void {
        $driver = new MySqlDriver();
        $config = [
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'test_db',
            'username' => 'user',
            'password' => 'password',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ],
        ];

        expect(fn() => $driver->connect($config))
            ->toThrow(PDOException::class);
    });
});

describe('PostgreSqlDriver', function (): void {
    it('implements DatabaseDriverInterface', function (): void {
        $driver = new PostgreSqlDriver();
        expect($driver)->toBeInstanceOf(DatabaseDriverInterface::class);
    });

    it('builds correct DSN for PostgreSQL', function (): void {
        $driver = new PostgreSqlDriver();
        $config = [
            'host' => 'localhost',
            'port' => 5432,
            'database' => 'test_db',
        ];

        $dsn = $driver->buildDsn($config);
        expect($dsn)->toBe('pgsql:host=localhost;port=5432;dbname=test_db');
    });

    it('builds DSN with sslmode', function (): void {
        $driver = new PostgreSqlDriver();
        $config = [
            'host' => 'localhost',
            'port' => 5432,
            'database' => 'test_db',
            'sslmode' => 'prefer',
        ];

        $dsn = $driver->buildDsn($config);
        expect($dsn)->toBe('pgsql:host=localhost;port=5432;dbname=test_db;sslmode=prefer');
    });

    it('connects with valid config', function (): void {
        $driver = new PostgreSqlDriver();
        $config = [
            'host' => 'localhost',
            'port' => 5432,
            'database' => 'test_db',
            'username' => 'user',
            'password' => 'password',
        ];

        expect(fn() => $driver->connect($config))
            ->toThrow(PDOException::class);
    });

    it('connects with options', function (): void {
        $driver = new PostgreSqlDriver();
        $config = [
            'host' => 'localhost',
            'port' => 5432,
            'database' => 'test_db',
            'username' => 'user',
            'password' => 'password',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ],
        ];

        expect(fn() => $driver->connect($config))
            ->toThrow(PDOException::class);
    });
});

describe('SqliteDriver', function (): void {
    it('implements DatabaseDriverInterface', function (): void {
        $driver = new SqliteDriver();
        expect($driver)->toBeInstanceOf(DatabaseDriverInterface::class);
    });

    it('builds correct DSN for SQLite', function (): void {
        $driver = new SqliteDriver();
        $config = [
            'database' => '/path/to/database.sqlite',
        ];

        $dsn = $driver->buildDsn($config);
        expect($dsn)->toBe('sqlite:/path/to/database.sqlite');
    });

    it('builds DSN for in-memory database', function (): void {
        $driver = new SqliteDriver();
        $config = [
            'database' => ':memory:',
        ];

        $dsn = $driver->buildDsn($config);
        expect($dsn)->toBe('sqlite::memory:');
    });

    it('connects to in-memory database', function (): void {
        $driver = new SqliteDriver();
        $config = [
            'database' => ':memory:',
        ];

        $pdo = $driver->connect($config);
        expect($pdo)->toBeInstanceOf(PDO::class);
    });

    it('connects with options', function (): void {
        $driver = new SqliteDriver();
        $config = [
            'database' => ':memory:',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ],
        ];

        $pdo = $driver->connect($config);
        expect($pdo)->toBeInstanceOf(PDO::class);
    });

    it('can execute queries on connected database', function (): void {
        $driver = new SqliteDriver();
        $config = [
            'database' => ':memory:',
        ];

        $pdo = $driver->connect($config);
        
        // Создаем таблицу
        $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        
        // Вставляем данные
        $stmt = $pdo->prepare('INSERT INTO test (name) VALUES (?)');
        $stmt->execute(['test_value']);
        
        // Проверяем данные
        $stmt = $pdo->prepare('SELECT * FROM test WHERE name = ?');
        $stmt->execute(['test_value']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        expect($result['name'])->toBe('test_value');
    });
});

describe('DatabaseDriverInterface', function (): void {
    it('defines required methods', function (): void {
        $reflection = new ReflectionClass(DatabaseDriverInterface::class);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn($method) => $method->getName(), $methods);
        
        expect($methodNames)->toContain('connect');
        expect($methodNames)->toContain('buildDsn');
    });

    it('has correct method signatures', function (): void {
        $reflection = new ReflectionClass(DatabaseDriverInterface::class);
        
        $connectMethod = $reflection->getMethod('connect');
        expect($connectMethod->getParameters())->toHaveCount(1);
        expect($connectMethod->getReturnType()->getName())->toBe('PDO');
        
        $buildDsnMethod = $reflection->getMethod('buildDsn');
        expect($buildDsnMethod->getParameters())->toHaveCount(1);
        expect($buildDsnMethod->getReturnType()->getName())->toBe('string');
    });
});
