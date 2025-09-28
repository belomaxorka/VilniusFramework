<?php declare(strict_types=1);

use Core\Database\DatabaseManager;
use Core\Database\QueryBuilder;

it('tests toSql method accessibility', function (): void {
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
    
    // Тестируем, что метод toSql() теперь публичный
    $sql = $queryBuilder->table('users')->toSql();
    expect($sql)->toBe('SELECT * FROM users');
    
    // Тестируем более сложный запрос
    $complexSql = $queryBuilder
        ->table('users')
        ->select(['name', 'email'])
        ->where('age', '>', 25)
        ->orderBy('name', 'asc')
        ->limit(10)
        ->toSql();
    
    expect($complexSql)->toBe('SELECT name, email FROM users WHERE age > ? ORDER BY name asc LIMIT 10');
});
