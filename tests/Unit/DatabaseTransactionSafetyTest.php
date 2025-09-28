<?php declare(strict_types=1);

use Core\Database\DatabaseManager;

it('tests transaction safety methods', function (): void {
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
    
    // Создаем тестовую таблицу
    $connection->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
    
    // Тест 1: commit без активной транзакции должен вернуть false
    $result = $db->commit();
    expect($result)->toBeFalse();
    
    // Тест 2: rollback без активной транзакции должен вернуть false
    $result = $db->rollback();
    expect($result)->toBeFalse();
    
    // Тест 3: двойной commit
    $db->beginTransaction();
    $db->insert('INSERT INTO test_table (name) VALUES (?)', ['test']);
    $firstCommit = $db->commit();
    expect($firstCommit)->toBeTrue();
    
    $secondCommit = $db->commit();
    expect($secondCommit)->toBeFalse();
    
    // Тест 4: двойной rollback
    $db->beginTransaction();
    $db->insert('INSERT INTO test_table (name) VALUES (?)', ['test2']);
    $firstRollback = $db->rollback();
    expect($firstRollback)->toBeTrue();
    
    $secondRollback = $db->rollback();
    expect($secondRollback)->toBeFalse();
    
    // Проверяем, что данные корректно обработаны
    $count = $connection->query('SELECT COUNT(*) FROM test_table')->fetchColumn();
    expect($count)->toBe(1); // Только первая запись должна быть сохранена
});
