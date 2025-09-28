<?php declare(strict_types=1);

use Core\Database\DatabaseManager;
use Core\Database\QueryBuilder;
use Core\Database\Exceptions\ConnectionException;
use Core\Database\Exceptions\QueryException;

describe('Database Integration Tests', function (): void {
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
        setupTestTables($this->connection);
        insertTestData($this->connection);
    });

    it('performs complex queries with joins', function (): void {
        $query = "
            SELECT u.name, u.email, p.title, p.status, c.name as category_name
            FROM users u
            JOIN posts p ON u.id = p.user_id
            JOIN post_categories pc ON p.id = pc.post_id
            JOIN categories c ON pc.category_id = c.id
            WHERE u.is_active = 1 AND p.status = 'published'
            ORDER BY u.name, p.title
        ";
        
        $results = $this->db->select($query);
        
        expect($results)->toHaveCount(6); // 6 published posts from active users
        
        // Проверяем структуру результатов
        expect($results[0])->toHaveKeys(['name', 'email', 'title', 'status', 'category_name']);
        
        // Проверяем, что все пользователи активны
        foreach ($results as $result) {
            expect($result['status'])->toBe('published');
        }
    });

    it('performs aggregation queries', function (): void {
        $query = "
            SELECT 
                c.name as category_name,
                COUNT(p.id) as post_count,
                AVG(u.age) as avg_author_age
            FROM categories c
            LEFT JOIN post_categories pc ON c.id = pc.category_id
            LEFT JOIN posts p ON pc.post_id = p.id AND p.status = 'published'
            LEFT JOIN users u ON p.user_id = u.id
            GROUP BY c.id, c.name
            ORDER BY post_count DESC
        ";
        
        $results = $this->db->select($query);
        
        expect($results)->toHaveCount(4); // 4 categories
        
        // Проверяем, что категория Technology имеет больше всего постов
        expect($results[0]['category_name'])->toBe('Technology');
        expect($results[0]['post_count'])->toBe(3);
    });

    it('performs subquery operations', function (): void {
        $query = "
            SELECT u.name, u.email, u.age
            FROM users u
            WHERE u.id IN (
                SELECT DISTINCT p.user_id 
                FROM posts p 
                WHERE p.status = 'published'
            )
            ORDER BY u.age DESC
        ";
        
        $results = $this->db->select($query);
        
        expect($results)->toHaveCount(4); // 4 users with published posts
        
        // Проверяем сортировку по возрасту
        expect($results[0]['age'])->toBe(42); // Charlie Wilson
        expect($results[1]['age'])->toBe(35); // Bob Johnson
    });

    it('handles transactions with rollback', function (): void {
        $initialCount = $this->connection->query('SELECT COUNT(*) FROM users')->fetchColumn();
        
        try {
            $this->db->transaction(function ($db) {
                $db->insert('INSERT INTO users (name, email, age) VALUES (?, ?, ?)', 
                    ['Test User', 'test@example.com', 30]);
                
                // Имитируем ошибку
                throw new Exception('Simulated error');
            });
        } catch (Exception $e) {
            expect($e->getMessage())->toBe('Simulated error');
        }
        
        $finalCount = $this->connection->query('SELECT COUNT(*) FROM users')->fetchColumn();
        expect($finalCount)->toBe($initialCount); // Количество не изменилось
    });

    it('handles transactions with commit', function (): void {
        $initialCount = $this->connection->query('SELECT COUNT(*) FROM users')->fetchColumn();
        
        $result = $this->db->transaction(function ($db) {
            $db->insert('INSERT INTO users (name, email, age) VALUES (?, ?, ?)', 
                ['Test User', 'test@example.com', 30]);
            
            return 'success';
        });
        
        expect($result)->toBe('success');
        
        $finalCount = $this->connection->query('SELECT COUNT(*) FROM users')->fetchColumn();
        expect($finalCount)->toBe($initialCount + 1);
    });

    it('performs batch operations', function (): void {
        $users = [
            ['Batch User 1', 'batch1@example.com', 25],
            ['Batch User 2', 'batch2@example.com', 30],
            ['Batch User 3', 'batch3@example.com', 35],
        ];
        
        $this->db->beginTransaction();
        
        try {
            foreach ($users as $user) {
                $this->db->insert('INSERT INTO users (name, email, age) VALUES (?, ?, ?)', $user);
            }
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
        
        $count = $this->connection->query('SELECT COUNT(*) FROM users WHERE name LIKE "Batch User%"')->fetchColumn();
        expect($count)->toBe(3);
    });

    it('handles concurrent operations', function (): void {
        $initialCount = $this->connection->query('SELECT COUNT(*) FROM posts')->fetchColumn();
        
        // Имитируем несколько одновременных операций
        $operations = [
            function () { return $this->db->insert('INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)', 
                [1, 'Concurrent Post 1', 'Content 1']); },
            function () { return $this->db->insert('INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)', 
                [2, 'Concurrent Post 2', 'Content 2']); },
            function () { return $this->db->insert('INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)', 
                [3, 'Concurrent Post 3', 'Content 3']); },
        ];
        
        foreach ($operations as $operation) {
            $result = $operation();
            expect($result)->toBeTrue();
        }
        
        $finalCount = $this->connection->query('SELECT COUNT(*) FROM posts')->fetchColumn();
        expect($finalCount)->toBe($initialCount + 3);
    });

    it('performs complex updates with joins', function (): void {
        $affectedRows = $this->db->update('
            UPDATE posts 
            SET status = "archived" 
            WHERE id IN (
                SELECT p.id 
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE u.age > 40
            )
        ');
        
        expect($affectedRows)->toBe(1); // Only Charlie Wilson's post
        
        $archivedCount = $this->connection->query('SELECT COUNT(*) FROM posts WHERE status = "archived"')->fetchColumn();
        expect($archivedCount)->toBe(1);
    });

    it('performs complex deletes with joins', function (): void {
        $affectedRows = $this->db->delete('
            DELETE FROM posts 
            WHERE id IN (
                SELECT p.id 
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE u.is_active = 0
            )
        ');
        
        expect($affectedRows)->toBe(1); // Only Bob Johnson's post (he's inactive)
        
        $remainingCount = $this->connection->query('SELECT COUNT(*) FROM posts')->fetchColumn();
        expect($remainingCount)->toBe(6); // 7 - 1 = 6
    });

    it('handles large result sets', function (): void {
        // Создаем много тестовых данных
        $this->db->beginTransaction();
        
        for ($i = 1; $i <= 100; $i++) {
            $this->db->insert('INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)', 
                [1, "Test Post {$i}", "Content for post {$i}"]);
        }
        
        $this->db->commit();
        
        // Получаем все посты
        $results = $this->db->select('SELECT * FROM posts ORDER BY id');
        
        expect($results)->toHaveCount(107); // 7 original + 100 new
        
        // Проверяем пагинацию
        $paginatedResults = $this->db->select('SELECT * FROM posts ORDER BY id LIMIT 10 OFFSET 50');
        expect($paginatedResults)->toHaveCount(10);
    });

    it('handles query builder with complex queries', function (): void {
        $queryBuilder = new QueryBuilder($this->db);
        
        $results = $queryBuilder
            ->table('users')
            ->select(['users.name', 'users.email', 'posts.title', 'categories.name as category_name'])
            ->join('posts', 'users.id', '=', 'posts.user_id')
            ->join('post_categories', 'posts.id', '=', 'post_categories.post_id')
            ->join('categories', 'post_categories.category_id', '=', 'categories.id')
            ->where('users.is_active', '=', 1)
            ->where('posts.status', '=', 'published')
            ->orderBy('users.name', 'asc')
            ->orderBy('posts.title', 'asc')
            ->get();
        
        expect($results)->toHaveCount(6);
        expect($results[0])->toHaveKeys(['name', 'email', 'title', 'category_name']);
    });

    it('handles query builder with limit and offset', function (): void {
        $queryBuilder = new QueryBuilder($this->db);
        
        $results = $queryBuilder
            ->table('posts')
            ->select(['title', 'status'])
            ->orderBy('id', 'asc')
            ->limit(3)
            ->offset(2)
            ->get();
        
        expect($results)->toHaveCount(3);
        
        // Проверяем, что это действительно посты с id 3, 4, 5
        $firstPost = $this->connection->query('SELECT title FROM posts WHERE id = 3')->fetchColumn();
        expect($results[0]['title'])->toBe($firstPost);
    });

    it('handles query builder first method', function (): void {
        $queryBuilder = new QueryBuilder($this->db);
        
        $result = $queryBuilder
            ->table('users')
            ->where('age', '>', 30)
            ->orderBy('age', 'desc')
            ->first();
        
        expect($result)->toBeArray();
        expect($result['name'])->toBe('Charlie Wilson'); // age 42
        expect($result['age'])->toBe(42);
    });

    it('handles query builder with no results', function (): void {
        $queryBuilder = new QueryBuilder($this->db);
        
        $result = $queryBuilder
            ->table('users')
            ->where('age', '>', 100)
            ->first();
        
        expect($result)->toBeNull();
    });

    it('handles database errors gracefully', function (): void {
        // Попытка выполнить неверный SQL
        expect(fn() => $this->db->select('INVALID SQL QUERY'))
            ->toThrow(QueryException::class);
        
        // Попытка вставить дублирующийся email
        expect(fn() => $this->db->insert('INSERT INTO users (name, email, age) VALUES (?, ?, ?)', 
            ['Duplicate', 'john@example.com', 30]))
            ->toThrow(QueryException::class);
    });

    it('handles connection errors gracefully', function (): void {
        $invalidConfig = [
            'default' => 'invalid',
            'connections' => [
                'invalid' => [
                    'driver' => 'nonexistent',
                    'database' => 'test',
                ],
            ],
        ];
        
        $invalidDb = new DatabaseManager($invalidConfig);
        
        expect(fn() => $invalidDb->connection())
            ->toThrow(ConnectionException::class);
    });
});

function setupTestTables(PDO $connection): void {
    $connection->exec('
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            age INTEGER,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
    $connection->exec('
        CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            content TEXT,
            status TEXT DEFAULT "draft",
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        )
    ');
    
    $connection->exec('
        CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
    $connection->exec('
        CREATE TABLE post_categories (
            post_id INTEGER NOT NULL,
            category_id INTEGER NOT NULL,
            PRIMARY KEY (post_id, category_id),
            FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE
        )
    ');
}

function insertTestData(PDO $connection): void {
    // Вставляем пользователей
    $connection->exec("
        INSERT INTO users (name, email, age, is_active) VALUES 
        ('John Doe', 'john@example.com', 30, 1),
        ('Jane Smith', 'jane@example.com', 25, 1),
        ('Bob Johnson', 'bob@example.com', 35, 0),
        ('Alice Brown', 'alice@example.com', 28, 1),
        ('Charlie Wilson', 'charlie@example.com', 42, 1)
    ");
    
    // Вставляем категории
    $connection->exec("
        INSERT INTO categories (name, description) VALUES 
        ('Technology', 'Posts about technology and programming'),
        ('Lifestyle', 'Posts about lifestyle and personal experiences'),
        ('News', 'News and current events'),
        ('Tutorials', 'Educational and tutorial content')
    ");
    
    // Вставляем посты
    $connection->exec("
        INSERT INTO posts (user_id, title, content, status) VALUES 
        (1, 'Introduction to PHP', 'This is a comprehensive guide to PHP programming.', 'published'),
        (1, 'Database Design Best Practices', 'Learn how to design efficient databases.', 'published'),
        (2, 'My Travel Experience', 'Sharing my recent travel experiences.', 'draft'),
        (2, 'Healthy Living Tips', 'Tips for maintaining a healthy lifestyle.', 'published'),
        (3, 'Breaking News Update', 'Latest news update on current events.', 'published'),
        (4, 'JavaScript Tutorial', 'Learn JavaScript from scratch.', 'published'),
        (5, 'Photography Tips', 'Professional photography techniques.', 'draft')
    ");
    
    // Связываем посты с категориями
    $connection->exec("
        INSERT INTO post_categories (post_id, category_id) VALUES 
        (1, 1), (1, 4),  -- PHP post -> Technology, Tutorials
        (2, 1), (2, 4),  -- Database post -> Technology, Tutorials
        (3, 2),          -- Travel post -> Lifestyle
        (4, 2),          -- Health post -> Lifestyle
        (5, 3),          -- News post -> News
        (6, 1), (6, 4),  -- JavaScript post -> Technology, Tutorials
        (7, 2)           -- Photography post -> Lifestyle
    ");
}