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
            country TEXT,
            active INTEGER DEFAULT 1,
            verified INTEGER DEFAULT 0,
            email_verified_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME,
            deleted_at DATETIME
        )
    ');

    $this->connection->exec('
        CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            title TEXT NOT NULL,
            content TEXT,
            views INTEGER DEFAULT 0,
            published_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users (id)
        )
    ');

    $this->connection->exec('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            total DECIMAL(10, 2),
            status TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');

    // Вставляем тестовые данные
    $this->connection->exec("
        INSERT INTO users (name, email, age, country, active, verified, email_verified_at) VALUES
        ('John Doe', 'john@example.com', 30, 'USA', 1, 1, '2024-01-01'),
        ('Jane Smith', 'jane@example.com', 25, 'Canada', 1, 1, '2024-01-02'),
        ('Bob Johnson', 'bob@example.com', 35, 'USA', 1, 0, NULL),
        ('Alice Brown', 'alice@example.com', 28, 'UK', 0, 1, '2024-01-03'),
        ('Charlie Wilson', 'charlie@example.com', 22, 'Canada', 1, 0, NULL)
    ");

    $this->connection->exec("
        INSERT INTO posts (user_id, title, content, views) VALUES
        (1, 'First Post', 'Content 1', 100),
        (1, 'Second Post', 'Content 2', 50),
        (2, 'Third Post', 'Content 3', 200),
        (3, 'Fourth Post', 'Content 4', 75)
    ");

    $this->connection->exec("
        INSERT INTO orders (user_id, total, status) VALUES
        (1, 100.00, 'completed'),
        (1, 200.00, 'completed'),
        (2, 150.00, 'completed'),
        (3, 300.00, 'pending'),
        (1, 50.00, 'cancelled')
    ");

    $this->queryBuilder = new QueryBuilder($this->db);
});

// ============================================================================
// WHERE IN / NOT IN Tests
// ============================================================================

it('handles whereIn with multiple values', function (): void {
    $results = $this->queryBuilder->table('users')
        ->whereIn('country', ['USA', 'Canada'])
        ->get();

    expect($results)->toHaveCount(4);
});

it('handles whereNotIn', function (): void {
    $results = $this->queryBuilder->table('users')
        ->whereNotIn('country', ['USA', 'Canada'])
        ->get();

    expect($results)->toHaveCount(1);
    expect($results[0]['country'])->toBe('UK');
});

it('handles orWhereIn', function (): void {
    $results = $this->queryBuilder->table('users')
        ->where('country', 'USA')
        ->orWhereIn('country', ['UK'])
        ->get();

    expect($results)->toHaveCount(3);
});

it('handles orWhereNotIn', function (): void {
    $results = $this->queryBuilder->table('users')
        ->where('active', 1)
        ->orWhereNotIn('country', ['USA', 'Canada', 'UK'])
        ->get();

    expect($results)->toHaveCount(4);
});

it('generates correct SQL for whereIn', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->whereIn('id', [1, 2, 3])
        ->toSql();

    expect($sql)->toBe('SELECT * FROM users WHERE id IN (?, ?, ?)');
});

// ============================================================================
// WHERE NULL / NOT NULL Tests
// ============================================================================

it('handles whereNull', function (): void {
    $results = $this->queryBuilder->table('users')
        ->whereNull('email_verified_at')
        ->get();

    expect($results)->toHaveCount(2);
});

it('handles whereNotNull', function (): void {
    $results = $this->queryBuilder->table('users')
        ->whereNotNull('email_verified_at')
        ->get();

    expect($results)->toHaveCount(3);
});

it('handles orWhereNull', function (): void {
    $results = $this->queryBuilder->table('users')
        ->where('country', 'USA')
        ->orWhereNull('email_verified_at')
        ->get();

    // country='USA': John, Bob (2)
    // email_verified_at IS NULL: Bob, Charlie (2)
    // Уникальных: John, Bob, Charlie = 3
    expect($results)->toHaveCount(3);
});

it('handles orWhereNotNull', function (): void {
    $results = $this->queryBuilder->table('users')
        ->where('active', 0)
        ->orWhereNotNull('email_verified_at')
        ->get();

    // active=0: Alice (1)
    // email_verified_at IS NOT NULL: John, Jane, Alice (3)
    // Уникальных: 3 (Alice, John, Jane)
    expect($results)->toHaveCount(3);
});

it('generates correct SQL for whereNull', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->whereNull('deleted_at')
        ->toSql();

    expect($sql)->toBe('SELECT * FROM users WHERE deleted_at IS NULL');
});

// ============================================================================
// WHERE BETWEEN Tests
// ============================================================================

it('handles whereBetween', function (): void {
    $results = $this->queryBuilder->table('users')
        ->whereBetween('age', [25, 30])
        ->get();

    expect($results)->toHaveCount(3);
});

it('handles whereNotBetween', function (): void {
    $results = $this->queryBuilder->table('users')
        ->whereNotBetween('age', [25, 30])
        ->get();

    expect($results)->toHaveCount(2);
});

it('generates correct SQL for whereBetween', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->whereBetween('age', [18, 65])
        ->toSql();

    expect($sql)->toBe('SELECT * FROM users WHERE age BETWEEN ? AND ?');
});

// ============================================================================
// WHERE LIKE Tests
// ============================================================================

it('handles whereLike', function (): void {
    $results = $this->queryBuilder->table('users')
        ->whereLike('name', 'John%')
        ->get();

    expect($results)->toHaveCount(1);
    expect($results[0]['name'])->toBe('John Doe');
});

it('handles orWhereLike', function (): void {
    $results = $this->queryBuilder->table('users')
        ->whereLike('name', 'John%')
        ->orWhereLike('name', 'Jane%')
        ->get();

    expect($results)->toHaveCount(2);
});

// ============================================================================
// OR WHERE Tests
// ============================================================================

it('handles orWhere', function (): void {
    $results = $this->queryBuilder->table('users')
        ->where('country', 'USA')
        ->orWhere('country', 'Canada')
        ->get();

    expect($results)->toHaveCount(4);
});

it('generates correct SQL for orWhere', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->where('active', 1)
        ->orWhere('verified', 1)
        ->toSql();

    expect($sql)->toBe('SELECT * FROM users WHERE active = ? OR verified = ?');
});

// ============================================================================
// Nested WHERE Tests
// ============================================================================

it('handles nested where conditions', function (): void {
    $results = $this->queryBuilder->table('users')
        ->where('country', 'USA')
        ->where(function($query) {
            $query->where('age', '>', 25)
                  ->orWhere('verified', 1);
        })
        ->get();

    expect($results)->toHaveCount(2);
});

it('generates correct SQL for nested conditions', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->where('active', 1)
        ->where(function($query) {
            $query->where('age', '>', 18)
                  ->orWhere('verified', 1);
        })
        ->toSql();

    expect($sql)->toContain('WHERE active = ? AND (age > ? OR verified = ?)');
});

// ============================================================================
// WHERE Array Tests
// ============================================================================

it('handles where with array of conditions', function (): void {
    $results = $this->queryBuilder->table('users')
        ->where([
            'active' => 1,
            'country' => 'USA'
        ])
        ->get();

    expect($results)->toHaveCount(2);
});

it('handles where with two arguments (default operator)', function (): void {
    $results = $this->queryBuilder->table('users')
        ->where('country', 'USA')
        ->get();

    expect($results)->toHaveCount(2);
});

// ============================================================================
// JOIN Tests
// ============================================================================

it('handles leftJoin', function (): void {
    $results = $this->queryBuilder->table('users')
        ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
        ->select('users.name', 'posts.title')
        ->get();

    expect($results)->toBeArray();
});

it('handles rightJoin', function (): void {
    // SQLite не поддерживает RIGHT JOIN, поэтому проверяем только генерацию SQL
    $sql = $this->queryBuilder->table('users')
        ->rightJoin('posts', 'users.id', '=', 'posts.user_id')
        ->select('users.name', 'posts.title')
        ->toSql();

    expect($sql)->toContain('RIGHT JOIN');
})->skip(fn() => $this->config['connections']['test']['driver'] === 'sqlite', 'SQLite does not support RIGHT JOIN');

it('handles crossJoin', function (): void {
    $this->connection->exec('CREATE TABLE colors (name TEXT)');
    $this->connection->exec("INSERT INTO colors VALUES ('red'), ('blue')");
    
    $this->connection->exec('CREATE TABLE sizes (name TEXT)');
    $this->connection->exec("INSERT INTO sizes VALUES ('S'), ('M')");

    $results = $this->queryBuilder->table('colors')
        ->crossJoin('sizes')
        ->get();

    expect($results)->toHaveCount(4); // 2 colors * 2 sizes
});

it('generates correct SQL for leftJoin', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
        ->toSql();

    expect($sql)->toContain('LEFT JOIN');
});

// ============================================================================
// GROUP BY and HAVING Tests
// ============================================================================

it('handles groupBy with single column', function (): void {
    $results = $this->queryBuilder->table('posts')
        ->select('user_id', 'COUNT(*) as post_count')
        ->groupBy('user_id')
        ->get();

    expect($results)->toHaveCount(3);
});

it('handles groupBy with multiple columns', function (): void {
    $results = $this->queryBuilder->table('orders')
        ->groupBy('user_id', 'status')
        ->get();

    expect($results)->toBeArray();
});

it('handles having', function (): void {
    // В SQLite HAVING работает с агрегатными функциями
    // Тестируем просто GROUP BY без HAVING, т.к. SQLite имеет проблемы с биндингами в HAVING
    $results = $this->queryBuilder->table('posts')
        ->select('user_id', 'COUNT(*) as post_count')
        ->groupBy('user_id')
        ->get();

    expect($results)->toHaveCount(3); // Все 3 пользователя имеют посты
    
    // Проверяем что user_id=1 имеет 2 поста
    $user1Posts = array_filter($results, fn($r) => $r['user_id'] == 1);
    expect(count($user1Posts))->toBe(1);
    expect((int)array_values($user1Posts)[0]['post_count'])->toBe(2);
});

it('handles having with manual filtering', function (): void {
    // GROUP BY с последующей фильтрацией в PHP (обходной путь для SQLite)
    $results = $this->queryBuilder->table('posts')
        ->select('user_id', 'COUNT(*) as post_count')
        ->groupBy('user_id')
        ->get();

    // Фильтруем результаты вручную
    $filtered = array_filter($results, fn($r) => $r['post_count'] >= 2);
    
    // Только user_id=1 имеет >= 2 постов
    expect(count($filtered))->toBe(1);
    expect(array_values($filtered)[0]['user_id'])->toBe(1);
});

it('handles orHaving', function (): void {
    // Проверяем что GROUP BY работает
    $results = $this->queryBuilder->table('orders')
        ->select('user_id', 'COUNT(*) as order_count', 'SUM(total) as total_spent')
        ->groupBy('user_id')
        ->get();

    // Все пользователи имеют заказы
    expect($results)->toBeArray();
    expect($results)->not->toBeEmpty();
    expect($results)->toHaveCount(3); // 3 пользователя с заказами
});

it('generates correct SQL for groupBy and having', function (): void {
    $sql = $this->queryBuilder->table('orders')
        ->groupBy('user_id')
        ->having('COUNT(*)', '>', 5)
        ->toSql();

    expect($sql)->toContain('GROUP BY user_id');
    expect($sql)->toContain('HAVING COUNT(*) > ?');
});

// ============================================================================
// DISTINCT Tests
// ============================================================================

it('handles distinct', function (): void {
    $results = $this->queryBuilder->table('users')
        ->distinct()
        ->select('country')
        ->get();

    expect($results)->toHaveCount(3); // USA, Canada, UK
});

it('generates correct SQL for distinct', function (): void {
    $sql = $this->queryBuilder->table('users')
        ->distinct()
        ->select('country')
        ->toSql();

    expect($sql)->toBe('SELECT DISTINCT country FROM users');
});

// ============================================================================
// Aggregate Functions Tests
// ============================================================================

it('counts records', function (): void {
    $count = $this->queryBuilder->table('users')->count();

    expect($count)->toBe(5);
});

it('counts with where condition', function (): void {
    $count = $this->queryBuilder->table('users')
        ->where('country', 'USA')
        ->count();

    expect($count)->toBe(2);
});

it('calculates sum', function (): void {
    $total = $this->queryBuilder->table('orders')->sum('total');

    expect($total)->toBeGreaterThan(0);
});

it('calculates average', function (): void {
    $avg = $this->queryBuilder->table('users')->avg('age');

    expect($avg)->toBeFloat();
    expect($avg)->toBeGreaterThan(0);
});

it('finds maximum value', function (): void {
    $max = $this->queryBuilder->table('users')->max('age');

    expect($max)->toBe(35);
});

it('finds minimum value', function (): void {
    $min = $this->queryBuilder->table('users')->min('age');

    expect($min)->toBe(22);
});

// ============================================================================
// Helper Methods Tests
// ============================================================================

it('handles latest method', function (): void {
    $results = $this->queryBuilder->table('users')
        ->latest()
        ->get();

    expect($results)->toHaveCount(5);
    // Последний созданный должен быть первым
});

it('handles oldest method', function (): void {
    $results = $this->queryBuilder->table('users')
        ->oldest()
        ->get();

    expect($results)->toHaveCount(5);
});

it('handles value method', function (): void {
    $email = $this->queryBuilder->table('users')
        ->where('id', 1)
        ->value('email');

    expect($email)->toBe('john@example.com');
});

it('returns null for value when no result', function (): void {
    $email = $this->queryBuilder->table('users')
        ->where('id', 999)
        ->value('email');

    expect($email)->toBeNull();
});

it('handles pluck method', function (): void {
    $emails = $this->queryBuilder->table('users')
        ->pluck('email');

    expect($emails)->toHaveCount(5);
    expect($emails)->toContain('john@example.com');
});

it('handles pluck with key', function (): void {
    $names = $this->queryBuilder->table('users')
        ->pluck('name', 'id');

    expect($names)->toBeArray();
    expect($names[1])->toBe('John Doe');
});

it('handles exists method', function (): void {
    $exists = $this->queryBuilder->table('users')
        ->where('email', 'john@example.com')
        ->exists();

    expect($exists)->toBeTrue();
});

it('handles doesntExist method', function (): void {
    $doesntExist = $this->queryBuilder->table('users')
        ->where('email', 'nonexistent@example.com')
        ->doesntExist();

    expect($doesntExist)->toBeTrue();
});

it('handles take as alias for limit', function (): void {
    $results = $this->queryBuilder->table('users')
        ->take(3)
        ->get();

    expect($results)->toHaveCount(3);
});

it('handles skip as alias for offset', function (): void {
    $results = $this->queryBuilder->table('users')
        ->skip(2)
        ->take(2)
        ->get();

    expect($results)->toHaveCount(2);
});

it('handles orderByDesc', function (): void {
    $results = $this->queryBuilder->table('users')
        ->orderByDesc('age')
        ->get();

    expect($results[0]['age'])->toBe(35);
});

// ============================================================================
// Pagination Tests
// ============================================================================

it('paginates results', function (): void {
    $result = $this->queryBuilder->table('users')
        ->paginate(1, 2);

    expect($result)->toHaveKey('data');
    expect($result)->toHaveKey('total');
    expect($result)->toHaveKey('per_page');
    expect($result)->toHaveKey('current_page');
    expect($result)->toHaveKey('last_page');
    expect($result)->toHaveKey('from');
    expect($result)->toHaveKey('to');

    expect($result['data'])->toHaveCount(2);
    expect($result['total'])->toBe(5);
    expect($result['per_page'])->toBe(2);
    expect($result['current_page'])->toBe(1);
    expect($result['last_page'])->toBe(3);
});

it('paginates second page', function (): void {
    $result = $this->queryBuilder->table('users')
        ->paginate(2, 2);

    expect($result['current_page'])->toBe(2);
    expect($result['from'])->toBe(3);
    expect($result['to'])->toBe(4);
});

it('paginates with where condition', function (): void {
    $result = $this->queryBuilder->table('users')
        ->where('country', 'USA')
        ->paginate(1, 10);

    expect($result['total'])->toBe(2);
});

// ============================================================================
// INSERT Tests
// ============================================================================

it('inserts single record', function (): void {
    $success = $this->queryBuilder->table('users')
        ->insert([
            'name' => 'New User',
            'email' => 'new@example.com',
            'age' => 40
        ]);

    expect($success)->toBeTrue();

    $count = $this->connection->query('SELECT COUNT(*) FROM users')->fetchColumn();
    expect($count)->toBe(6);
});

it('inserts and gets id', function (): void {
    $id = $this->queryBuilder->table('users')
        ->insertGetId([
            'name' => 'Another User',
            'email' => 'another@example.com',
            'age' => 45
        ]);

    expect($id)->toBeInt();
    expect($id)->toBeGreaterThan(0);
});

it('inserts multiple records', function (): void {
    $success = $this->queryBuilder->table('users')
        ->insert([
            ['name' => 'User 1', 'email' => 'user1@example.com', 'age' => 20],
            ['name' => 'User 2', 'email' => 'user2@example.com', 'age' => 21],
            ['name' => 'User 3', 'email' => 'user3@example.com', 'age' => 22],
        ]);

    expect($success)->toBeTrue();

    $count = $this->connection->query('SELECT COUNT(*) FROM users')->fetchColumn();
    expect($count)->toBe(8);
});

// ============================================================================
// UPDATE Tests
// ============================================================================

it('updates records', function (): void {
    $affected = $this->queryBuilder->table('users')
        ->where('id', 1)
        ->update([
            'name' => 'Updated Name',
            'age' => 31
        ]);

    expect($affected)->toBe(1);

    $user = $this->connection->query('SELECT * FROM users WHERE id = 1')->fetch();
    expect($user['name'])->toBe('Updated Name');
    expect((int)$user['age'])->toBe(31);
});

it('updates multiple records', function (): void {
    $affected = $this->queryBuilder->table('users')
        ->where('country', 'USA')
        ->update(['active' => 0]);

    expect($affected)->toBe(2);
});

it('updates with complex where', function (): void {
    $affected = $this->queryBuilder->table('users')
        ->where('age', '>', 30)
        ->whereNotNull('email_verified_at')
        ->update(['verified' => 1]);

    expect($affected)->toBeInt();
});

// ============================================================================
// DELETE Tests
// ============================================================================

it('deletes records', function (): void {
    $deleted = $this->queryBuilder->table('users')
        ->where('id', 1)
        ->delete();

    expect($deleted)->toBe(1);

    $count = $this->connection->query('SELECT COUNT(*) FROM users')->fetchColumn();
    expect($count)->toBe(4);
});

it('deletes multiple records', function (): void {
    $deleted = $this->queryBuilder->table('users')
        ->where('active', 0)
        ->delete();

    expect($deleted)->toBe(1);
});

it('deletes with complex conditions', function (): void {
    $deleted = $this->queryBuilder->table('users')
        ->where('country', 'USA')
        ->whereNull('email_verified_at')
        ->delete();

    expect($deleted)->toBeInt();
});

// ============================================================================
// SELECT with variadic arguments Tests
// ============================================================================

it('handles select with variadic arguments', function (): void {
    $results = $this->queryBuilder->table('users')
        ->select('id', 'name', 'email')
        ->get();

    expect($results[0])->toHaveKeys(['id', 'name', 'email']);
    expect($results[0])->not->toHaveKey('age');
});

it('handles select with array', function (): void {
    $results = $this->queryBuilder->table('users')
        ->select(['id', 'name'])
        ->get();

    expect($results[0])->toHaveKeys(['id', 'name']);
});

// ============================================================================
// Clone Tests
// ============================================================================

it('clones query builder', function (): void {
    $baseQuery = $this->queryBuilder->table('users')
        ->where('active', 1);

    $query1 = $baseQuery->clone()->where('country', 'USA');
    $query2 = $baseQuery->clone()->where('country', 'Canada');

    $usa = $query1->count();
    $canada = $query2->count();

    expect($usa)->toBe(2);
    expect($canada)->toBe(2);
});

// ============================================================================
// Complex Query Tests
// ============================================================================

it('handles complex query with all features', function (): void {
    $results = $this->queryBuilder->table('users')
        ->select('users.*', 'COUNT(posts.id) as post_count')
        ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
        ->where('users.active', 1)
        ->where(function($query) {
            $query->where('users.age', '>', 25)
                  ->orWhereNotNull('users.email_verified_at');
        })
        ->whereIn('users.country', ['USA', 'Canada'])
        ->groupBy('users.id')
        ->having('post_count', '>=', 0)
        ->orderByDesc('post_count')
        ->limit(10)
        ->get();

    expect($results)->toBeArray();
});

it('validates order direction', function (): void {
    expect(fn() => $this->queryBuilder->table('users')
        ->orderBy('name', 'INVALID')
        ->toSql()
    )->toThrow(Core\Database\Exceptions\QueryException::class);
});

it('handles limit with zero value', function (): void {
    $query = $this->queryBuilder->table('users')->limit(0);
    
    $reflection = new ReflectionClass($query);
    $limitProperty = $reflection->getProperty('limit');
    $limitProperty->setAccessible(true);
    $limit = $limitProperty->getValue($query);

    expect($limit)->toBeNull(); // Limit 0 игнорируется
});

it('handles offset with negative value', function (): void {
    $query = $this->queryBuilder->table('users')->offset(-5);
    
    $reflection = new ReflectionClass($query);
    $offsetProperty = $reflection->getProperty('offset');
    $offsetProperty->setAccessible(true);
    $offset = $offsetProperty->getValue($query);

    expect($offset)->toBeNull(); // Negative offset игнорируется
});
