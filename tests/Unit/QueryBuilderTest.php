<?php declare(strict_types=1);

use Core\Database\DatabaseManager;
use Core\Database\QueryBuilder;

beforeEach(function (): void {
    $this->config = [
        'default' => 'test',
        'connections' => [
            'test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ],
    ];
    
    $this->db = new DatabaseManager($this->config);
    $this->connection = $this->db->connection();
    
    // Создаем тестовые таблицы
    $this->connection->exec('
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE,
            age INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
    $this->connection->exec('
        CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            title TEXT NOT NULL,
            content TEXT,
            FOREIGN KEY (user_id) REFERENCES users (id)
        )
    ');
    
    // Вставляем тестовые данные
    $this->connection->exec("
        INSERT INTO users (name, email, age) VALUES 
        ('John Doe', 'john@example.com', 30),
        ('Jane Smith', 'jane@example.com', 25),
        ('Bob Johnson', 'bob@example.com', 35)
    ");
    
    $this->connection->exec("
        INSERT INTO posts (user_id, title, content) VALUES 
        (1, 'First Post', 'This is the first post'),
        (1, 'Second Post', 'This is the second post'),
        (2, 'Third Post', 'This is the third post')
    ");
    
    $this->queryBuilder = new QueryBuilder($this->db);
});

it('creates query builder instance', function (): void {
    expect($this->queryBuilder)->toBeInstanceOf(QueryBuilder::class);
});

it('sets table name', function (): void {
    $result = $this->queryBuilder->table('users');
    
    expect($result)->toBe($this->queryBuilder);
    
    // Проверяем через рефлексию, что таблица установлена
    $reflection = new ReflectionClass($this->queryBuilder);
    $tableProperty = $reflection->getProperty('table');
    $tableProperty->setAccessible(true);
    $table = $tableProperty->getValue($this->queryBuilder);
    
    expect($table)->toBe('users');
});

it('sets select columns', function (): void {
    $result = $this->queryBuilder->table('users')->select(['name', 'email']);
    
    expect($result)->toBe($this->queryBuilder);
    
    // Проверяем через рефлексию
    $reflection = new ReflectionClass($this->queryBuilder);
    $selectsProperty = $reflection->getProperty('selects');
    $selectsProperty->setAccessible(true);
    $selects = $selectsProperty->getValue($this->queryBuilder);
    
    expect($selects)->toBe(['name', 'email']);
});

it('uses default select all columns', function (): void {
    $this->queryBuilder->table('users');
    
    $reflection = new ReflectionClass($this->queryBuilder);
    $selectsProperty = $reflection->getProperty('selects');
    $selectsProperty->setAccessible(true);
    $selects = $selectsProperty->getValue($this->queryBuilder);
    
    expect($selects)->toBe(['*']);
});

it('adds where conditions', function (): void {
    $result = $this->queryBuilder->table('users')->where('age', '>', 30);
    
    expect($result)->toBe($this->queryBuilder);
    
    // Проверяем через рефлексию
    $reflection = new ReflectionClass($this->queryBuilder);
    $wheresProperty = $reflection->getProperty('wheres');
    $wheresProperty->setAccessible(true);
    $wheres = $wheresProperty->getValue($this->queryBuilder);
    
    expect($wheres)->toHaveCount(1);
    expect($wheres[0])->toBe([
        'type' => 'basic',
        'column' => 'age',
        'operator' => '>',
        'value' => 30,
    ]);
});

it('adds multiple where conditions', function (): void {
    $this->queryBuilder->table('users')
        ->where('age', '>', 25)
        ->where('name', '=', 'John Doe');
    
    $reflection = new ReflectionClass($this->queryBuilder);
    $wheresProperty = $reflection->getProperty('wheres');
    $wheresProperty->setAccessible(true);
    $wheres = $wheresProperty->getValue($this->queryBuilder);
    
    expect($wheres)->toHaveCount(2);
    expect($wheres[0]['column'])->toBe('age');
    expect($wheres[1]['column'])->toBe('name');
});

it('adds join conditions', function (): void {
    $result = $this->queryBuilder->table('users')
        ->join('posts', 'users.id', '=', 'posts.user_id');
    
    expect($result)->toBe($this->queryBuilder);
    
    // Проверяем через рефлексию
    $reflection = new ReflectionClass($this->queryBuilder);
    $joinsProperty = $reflection->getProperty('joins');
    $joinsProperty->setAccessible(true);
    $joins = $joinsProperty->getValue($this->queryBuilder);
    
    expect($joins)->toHaveCount(1);
    expect($joins[0])->toBe([
        'type' => 'inner',
        'table' => 'posts',
        'first' => 'users.id',
        'operator' => '=',
        'second' => 'posts.user_id',
    ]);
});

it('adds order by clauses', function (): void {
    $result = $this->queryBuilder->table('users')->orderBy('name', 'desc');
    
    expect($result)->toBe($this->queryBuilder);
    
    // Проверяем через рефлексию
    $reflection = new ReflectionClass($this->queryBuilder);
    $ordersProperty = $reflection->getProperty('orders');
    $ordersProperty->setAccessible(true);
    $orders = $ordersProperty->getValue($this->queryBuilder);
    
    expect($orders)->toHaveCount(1);
    expect($orders[0])->toBe([
        'column' => 'name',
        'direction' => 'desc',
    ]);
});

it('uses default order direction', function (): void {
    $this->queryBuilder->table('users')->orderBy('name');
    
    $reflection = new ReflectionClass($this->queryBuilder);
    $ordersProperty = $reflection->getProperty('orders');
    $ordersProperty->setAccessible(true);
    $orders = $ordersProperty->getValue($this->queryBuilder);
    
    expect($orders[0]['direction'])->toBe('asc');
});

it('sets limit', function (): void {
    $result = $this->queryBuilder->table('users')->limit(10);
    
    expect($result)->toBe($this->queryBuilder);
    
    // Проверяем через рефлексию
    $reflection = new ReflectionClass($this->queryBuilder);
    $limitProperty = $reflection->getProperty('limit');
    $limitProperty->setAccessible(true);
    $limit = $limitProperty->getValue($this->queryBuilder);
    
    expect($limit)->toBe(10);
});

it('sets offset', function (): void {
    $result = $this->queryBuilder->table('users')->offset(5);
    
    expect($result)->toBe($this->queryBuilder);
    
    // Проверяем через рефлексию
    $reflection = new ReflectionClass($this->queryBuilder);
    $offsetProperty = $reflection->getProperty('offset');
    $offsetProperty->setAccessible(true);
    $offset = $offsetProperty->getValue($this->queryBuilder);
    
    expect($offset)->toBe(5);
});

it('generates correct SQL for simple select', function (): void {
    $sql = $this->queryBuilder->table('users')->toSql();
    
    expect($sql)->toBe('SELECT * FROM users');
});

it('generates correct SQL with select columns', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->select(['name', 'email'])
        ->toSql();
    
    expect($sql)->toBe('SELECT name, email FROM users');
});

it('generates correct SQL with where conditions', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->where('age', '>', 30)
        ->toSql();
    
    expect($sql)->toBe('SELECT * FROM users WHERE age > ?');
});

it('generates correct SQL with multiple where conditions', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->where('age', '>', 25)
        ->where('name', '=', 'John')
        ->toSql();
    
    expect($sql)->toBe('SELECT * FROM users WHERE age > ? AND name = ?');
});

it('generates correct SQL with joins', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->join('posts', 'users.id', '=', 'posts.user_id')
        ->toSql();
    
    expect($sql)->toBe('SELECT * FROM users inner JOIN posts ON users.id = posts.user_id');
});

it('generates correct SQL with order by', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->orderBy('name', 'desc')
        ->toSql();
    
    expect($sql)->toBe('SELECT * FROM users ORDER BY name desc');
});

it('generates correct SQL with multiple order by clauses', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->orderBy('age', 'desc')
        ->orderBy('name', 'asc')
        ->toSql();
    
    expect($sql)->toBe('SELECT * FROM users ORDER BY age desc, name asc');
});

it('generates correct SQL with limit', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->limit(10)
        ->toSql();
    
    expect($sql)->toBe('SELECT * FROM users LIMIT 10');
});

it('generates correct SQL with offset', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->offset(5)
        ->toSql();
    
    expect($sql)->toBe('SELECT * FROM users OFFSET 5');
});

it('generates correct SQL with limit and offset', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->limit(10)
        ->offset(5)
        ->toSql();
    
    expect($sql)->toBe('SELECT * FROM users LIMIT 10 OFFSET 5');
});

it('generates complex SQL query', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->select(['name', 'email'])
        ->join('posts', 'users.id', '=', 'posts.user_id')
        ->where('age', '>', 25)
        ->orderBy('name', 'asc')
        ->limit(5)
        ->toSql();
    
    expect($sql)->toBe('SELECT name, email FROM users inner JOIN posts ON users.id = posts.user_id WHERE age > ? ORDER BY name asc LIMIT 5');
});

it('executes get query and returns results', function (): void {
    $results = $this->queryBuilder->table('users')->get();
    
    expect($results)->toHaveCount(3);
    expect($results[0]['name'])->toBe('John Doe');
    expect($results[1]['name'])->toBe('Jane Smith');
    expect($results[2]['name'])->toBe('Bob Johnson');
});

it('executes get query with where condition', function (): void {
    $results = $this->queryBuilder->table('users')
        ->where('age', '>', 30)
        ->get();
    
    expect($results)->toHaveCount(1);
    expect($results[0]['name'])->toBe('Bob Johnson');
});

it('executes get query with select columns', function (): void {
    $results = $this->queryBuilder->table('users')
        ->select(['name', 'email'])
        ->get();
    
    expect($results)->toHaveCount(3);
    expect($results[0])->toHaveKeys(['name', 'email']);
    expect($results[0])->not->toHaveKey('id');
});

it('executes get query with join', function (): void {
    $results = $this->queryBuilder->table('users')
        ->join('posts', 'users.id', '=', 'posts.user_id')
        ->select(['users.name', 'posts.title'])
        ->get();
    
    expect($results)->toHaveCount(3);
    expect($results[0])->toHaveKeys(['name', 'title']);
});

it('executes get query with order by', function (): void {
    $results = $this->queryBuilder->table('users')
        ->orderBy('age', 'desc')
        ->get();
    
    expect($results)->toHaveCount(3);
    expect($results[0]['name'])->toBe('Bob Johnson'); // age 35
    expect($results[1]['name'])->toBe('John Doe');    // age 30
    expect($results[2]['name'])->toBe('Jane Smith');  // age 25
});

it('executes get query with limit', function (): void {
    $results = $this->queryBuilder->table('users')
        ->orderBy('age', 'desc')
        ->limit(2)
        ->get();
    
    expect($results)->toHaveCount(2);
    expect($results[0]['name'])->toBe('Bob Johnson');
    expect($results[1]['name'])->toBe('John Doe');
});

it('executes first query and returns single result', function (): void {
    $result = $this->queryBuilder->table('users')
        ->where('age', '>', 30)
        ->first();
    
    expect($result)->toBeArray();
    expect($result['name'])->toBe('Bob Johnson');
});

it('returns null for first when no results', function (): void {
    $result = $this->queryBuilder->table('users')
        ->where('age', '>', 100)
        ->first();
    
    expect($result)->toBeNull();
});

it('handles bindings correctly', function (): void {
    $this->queryBuilder->table('users')
        ->where('age', '>', 30)
        ->where('name', '=', 'Bob Johnson');
    
    $reflection = new ReflectionClass($this->queryBuilder);
    $bindingsProperty = $reflection->getProperty('bindings');
    $bindingsProperty->setAccessible(true);
    $bindings = $bindingsProperty->getValue($this->queryBuilder);
    
    expect($bindings)->toBe([30, 'Bob Johnson']);
});

it('executes query with correct bindings', function (): void {
    $results = $this->queryBuilder->table('users')
        ->where('age', '>', 30)
        ->where('name', '=', 'Bob Johnson')
        ->get();
    
    expect($results)->toHaveCount(1);
    expect($results[0]['name'])->toBe('Bob Johnson');
});
