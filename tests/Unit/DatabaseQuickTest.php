<?php declare(strict_types=1);

use Core\Database\DatabaseManager;
use Core\Database\QueryBuilder;

it('quick test for database functionality', function (): void {
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

    // Создаем простую таблицу
    $connection->exec('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT, age INTEGER)');

    // Тестируем вставку
    $result = $db->insert('INSERT INTO test_users (name, age) VALUES (?, ?)', ['John', 30]);
    expect($result)->toBeTrue();

    // Тестируем выборку
    $results = $db->select('SELECT * FROM test_users');
    expect($results)->toHaveCount(1);
    expect($results[0]['name'])->toBe('John');
    expect($results[0]['age'])->toBe(30);

    // Тестируем QueryBuilder
    $queryBuilder = new QueryBuilder($db);
    $qbResults = $queryBuilder
        ->table('test_users')
        ->where('age', '>', 25)
        ->get();

    expect($qbResults)->toHaveCount(1);
    expect($qbResults[0]['name'])->toBe('John');

    // Тестируем транзакцию
    $transactionResult = $db->transaction(function ($db) {
        $db->insert('INSERT INTO test_users (name, age) VALUES (?, ?)', ['Jane', 25]);
        return 'success';
    });

    expect($transactionResult)->toBe('success');

    // Проверяем, что данные добавились
    $finalCount = $connection->query('SELECT COUNT(*) FROM test_users')->fetchColumn();
    expect($finalCount)->toBe(2);
});
