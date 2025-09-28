<?php declare(strict_types=1);

use Core\Database\DatabaseManager;
use Core\Database\QueryBuilder;
use Core\Database\Exceptions\ConnectionException;
use Core\Database\Exceptions\QueryException;

it('creates database manager with SQLite', function (): void {
    $config = [
        'default' => 'test',
        'connections' => [
            'test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ],
    ];

    $db = new DatabaseManager($config);
    expect($db)->toBeInstanceOf(DatabaseManager::class);
});

it('connects to in-memory SQLite database', function (): void {
    $config = [
        'default' => 'test',
        'connections' => [
            'test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ],
    ];

    $db = new DatabaseManager($config);
    $connection = $db->connection();

    expect($connection)->toBeInstanceOf(PDO::class);
});

it('executes simple SQL queries', function (): void {
    $config = [
        'default' => 'test',
        'connections' => [
            'test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ],
    ];

    $db = new DatabaseManager($config);
    $connection = $db->connection();

    // Создаем таблицу
    $connection->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');

    // Вставляем данные
    $result = $db->insert('INSERT INTO test_table (name) VALUES (?)', ['test_value']);
    expect($result)->toBeTrue();

    // Получаем данные
    $results = $db->select('SELECT * FROM test_table');
    expect($results)->toHaveCount(1);
    expect($results[0]['name'])->toBe('test_value');
});

it('creates query builder instance', function (): void {
    $config = [
        'default' => 'test',
        'connections' => [
            'test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ],
    ];

    $db = new DatabaseManager($config);
    $queryBuilder = new QueryBuilder($db);

    expect($queryBuilder)->toBeInstanceOf(QueryBuilder::class);
});

it('throws exception for invalid connection', function (): void {
    $config = [
        'default' => 'invalid',
        'connections' => [
            'invalid' => [
                'driver' => 'nonexistent',
                'database' => 'test',
            ],
        ],
    ];

    $db = new DatabaseManager($config);

    expect(fn() => $db->connection())
        ->toThrow(ConnectionException::class);
});
