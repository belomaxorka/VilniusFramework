<?php declare(strict_types=1);

use Core\Database\DatabaseManager;

it('tests the fixes for data types and sorting', function (): void {
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
    
    // Создаем таблицу с DECIMAL полем
    $connection->exec('CREATE TABLE test_accounts (id INTEGER PRIMARY KEY, balance DECIMAL(10,2))');
    
    // Вставляем данные
    $db->insert('INSERT INTO test_accounts (balance) VALUES (?)', [100.50]);
    
    // Проверяем, что можем получить данные и преобразовать в float
    $result = $connection->query('SELECT balance FROM test_accounts WHERE id = 1')->fetchColumn();
    expect((float)$result)->toBe(100.50);
    
    // Создаем таблицу для тестирования сортировки
    $connection->exec('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT, age INTEGER)');
    $connection->exec("
        INSERT INTO test_users (name, age) VALUES 
        ('Alice', 25),
        ('Bob', 35),
        ('Charlie', 30)
    ");
    
    // Тестируем сортировку по возрасту DESC
    $results = $db->select('SELECT * FROM test_users ORDER BY age DESC');
    
    expect($results)->toHaveCount(3);
    expect($results[0]['age'])->toBe(35); // Bob
    expect($results[1]['age'])->toBe(30); // Charlie
    expect($results[2]['age'])->toBe(25); // Alice
});
