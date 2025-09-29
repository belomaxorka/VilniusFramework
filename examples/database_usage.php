<?php declare(strict_types=1);

/**
 * Примеры использования улучшенного QueryBuilder и Database классов
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Database;

// Инициализация базы данных
Database::init();

// Включаем логирование запросов для демонстрации
$db = Database::getInstance();
$db->enableQueryLog();

echo "=== Примеры использования улучшенного QueryBuilder ===\n\n";

// ============================================================================
// 1. Базовые запросы с новыми возможностями
// ============================================================================

echo "1. WHERE IN и WHERE NULL:\n";
$users = Database::table('users')
    ->whereIn('status', ['active', 'pending'])
    ->whereNotNull('email_verified_at')
    ->get();
echo "SQL: " . Database::table('users')->whereIn('status', ['active', 'pending'])->toSql() . "\n\n";

// ============================================================================
// 2. Вложенные условия (Nested WHERE)
// ============================================================================

echo "2. Вложенные условия:\n";
$users = Database::table('users')
    ->where('country', 'USA')
    ->where(function($query) {
        $query->where('age', '>=', 18)
              ->orWhere('verified', 1);
    })
    ->get();
echo "SQL: Выбирает пользователей из USA, которые либо старше 18, либо верифицированы\n\n";

// ============================================================================
// 3. OR WHERE условия
// ============================================================================

echo "3. OR WHERE условия:\n";
$users = Database::table('users')
    ->where('country', 'USA')
    ->orWhere('country', 'Canada')
    ->orWhereIn('country', ['UK', 'Australia'])
    ->get();
echo "SQL: " . Database::table('users')->where('country', 'USA')->orWhere('country', 'Canada')->toSql() . "\n\n";

// ============================================================================
// 4. WHERE BETWEEN
// ============================================================================

echo "4. WHERE BETWEEN:\n";
$users = Database::table('users')
    ->whereBetween('age', [18, 65])
    ->whereBetween('created_at', ['2024-01-01', '2024-12-31'])
    ->get();
echo "SQL: " . Database::table('users')->whereBetween('age', [18, 65])->toSql() . "\n\n";

// ============================================================================
// 5. WHERE LIKE
// ============================================================================

echo "5. WHERE LIKE:\n";
$users = Database::table('users')
    ->whereLike('name', 'John%')
    ->orWhereLike('email', '%@gmail.com')
    ->get();
echo "SQL: " . Database::table('users')->whereLike('name', 'John%')->toSql() . "\n\n";

// ============================================================================
// 6. Различные типы JOIN
// ============================================================================

echo "6. LEFT JOIN и CROSS JOIN:\n";
$results = Database::table('users')
    ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
    ->select('users.*', 'profiles.bio')
    ->get();

$combinations = Database::table('colors')
    ->crossJoin('sizes')
    ->get();
echo "SQL LEFT JOIN: " . Database::table('users')->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')->toSql() . "\n\n";

// ============================================================================
// 7. GROUP BY и HAVING
// ============================================================================

echo "7. GROUP BY и HAVING:\n";
$stats = Database::table('orders')
    ->select('user_id', 'COUNT(*) as order_count', 'SUM(total) as total_spent')
    ->groupBy('user_id')
    ->having('order_count', '>', 5)
    ->orHaving('total_spent', '>', 1000)
    ->get();
echo "SQL: " . Database::table('orders')
    ->groupBy('user_id')
    ->having('order_count', '>', 5)
    ->toSql() . "\n\n";

// ============================================================================
// 8. DISTINCT
// ============================================================================

echo "8. DISTINCT:\n";
$countries = Database::table('users')
    ->distinct()
    ->select('country')
    ->get();
echo "SQL: " . Database::table('users')->distinct()->select('country')->toSql() . "\n\n";

// ============================================================================
// 9. Агрегатные функции
// ============================================================================

echo "9. Агрегатные функции:\n";
$count = Database::table('users')->count();
echo "Количество пользователей: {$count}\n";

$avgAge = Database::table('users')->avg('age');
echo "Средний возраст: {$avgAge}\n";

$maxPrice = Database::table('products')->max('price');
echo "Максимальная цена: {$maxPrice}\n";

$minPrice = Database::table('products')->min('price');
echo "Минимальная цена: {$minPrice}\n";

$totalRevenue = Database::table('orders')->sum('total');
echo "Общая выручка: {$totalRevenue}\n\n";

// ============================================================================
// 10. Пагинация
// ============================================================================

echo "10. Пагинация:\n";
$result = Database::table('users')
    ->where('active', 1)
    ->orderBy('created_at', 'DESC')
    ->paginate($page = 1, $perPage = 10);

echo "Страница {$result['current_page']} из {$result['last_page']}\n";
echo "Показано записей: {$result['from']}-{$result['to']} из {$result['total']}\n\n";

// ============================================================================
// 11. Helper методы
// ============================================================================

echo "11. Helper методы:\n";

// latest() - сортировка по created_at DESC
$recentPosts = Database::table('posts')->latest()->limit(5)->get();

// oldest() - сортировка по created_at ASC
$oldPosts = Database::table('posts')->oldest()->limit(5)->get();

// value() - получить значение одной колонки
$email = Database::table('users')->where('id', 1)->value('email');
echo "Email пользователя #1: {$email}\n";

// pluck() - получить массив значений
$emails = Database::table('users')->pluck('email');
echo "Все email: " . implode(', ', $emails) . "\n";

// pluck() с ключами
$userNames = Database::table('users')->pluck('name', 'id');
// Результат: [1 => 'John', 2 => 'Jane', ...]

// exists() / doesntExist()
if (Database::table('users')->where('email', 'test@example.com')->exists()) {
    echo "Пользователь с таким email существует\n";
}

// ============================================================================
// 12. INSERT операции
// ============================================================================

echo "\n12. INSERT операции:\n";

// Одиночная вставка
$success = Database::table('users')->insert([
    'name' => 'New User',
    'email' => 'new@example.com',
    'age' => 25
]);

// Вставка с получением ID
$userId = Database::table('users')->insertGetId([
    'name' => 'Another User',
    'email' => 'another@example.com'
]);
echo "Вставлен пользователь с ID: {$userId}\n";

// Batch insert
Database::table('users')->insert([
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com'],
    ['name' => 'User 3', 'email' => 'user3@example.com'],
]);
echo "Вставлено несколько пользователей\n";

// ============================================================================
// 13. UPDATE операции
// ============================================================================

echo "\n13. UPDATE операции:\n";

// Обычное обновление
$affected = Database::table('users')
    ->where('id', 1)
    ->update([
        'name' => 'Updated Name',
        'updated_at' => date('Y-m-d H:i:s')
    ]);
echo "Обновлено записей: {$affected}\n";

// Increment / Decrement
Database::table('posts')->where('id', 1)->increment('views');
Database::table('posts')->where('id', 1)->increment('views', 5);
Database::table('users')->where('id', 1)->decrement('credits', 10);

// С дополнительными полями
Database::table('posts')
    ->where('id', 1)
    ->increment('views', 1, ['last_viewed_at' => date('Y-m-d H:i:s')]);

// ============================================================================
// 14. DELETE операции
// ============================================================================

echo "\n14. DELETE операции:\n";

// Удаление с условием
$deleted = Database::table('users')
    ->where('active', 0)
    ->where('created_at', '<', date('Y-m-d', strtotime('-1 year')))
    ->delete();
echo "Удалено неактивных пользователей: {$deleted}\n";

// Очистка таблицы
// Database::table('logs')->truncate();

// ============================================================================
// 15. Query Logging
// ============================================================================

echo "\n15. Query Logging:\n";

// Получить лог всех запросов
$queries = $db->getQueryLog();
echo "Выполнено запросов: " . count($queries) . "\n";

// Последний запрос
$lastQuery = $db->getLastQuery();
if ($lastQuery) {
    echo "Последний запрос: {$lastQuery['query']}\n";
    echo "Время выполнения: {$lastQuery['time']} мс\n";
}

// Статистика производительности
$stats = $db->getQueryStats();
echo "\nСтатистика:\n";
echo "- Всего запросов: {$stats['total_queries']}\n";
echo "- Общее время: {$stats['total_time']} мс\n";
echo "- Среднее время: {$stats['avg_time']} мс\n";
echo "- Макс время: {$stats['max_time']} мс\n";
echo "- Мин время: {$stats['min_time']} мс\n";

// Медленные запросы (больше 100ms)
$slowQueries = $db->getSlowQueries(100);
if (!empty($slowQueries)) {
    echo "\nМедленные запросы (> 100ms):\n";
    foreach ($slowQueries as $query) {
        echo "- {$query['query']} ({$query['time']} мс)\n";
    }
}

// ============================================================================
// 16. Транзакции
// ============================================================================

echo "\n16. Транзакции:\n";

try {
    $result = $db->transaction(function($db) {
        // Все операции внутри транзакции
        $userId = Database::table('users')->insertGetId([
            'name' => 'Transaction User',
            'email' => 'transaction@example.com'
        ]);
        
        Database::table('profiles')->insert([
            'user_id' => $userId,
            'bio' => 'Created in transaction'
        ]);
        
        return $userId;
    });
    
    echo "Транзакция успешно выполнена. User ID: {$result}\n";
} catch (Exception $e) {
    echo "Транзакция отменена: " . $e->getMessage() . "\n";
}

// Ручное управление транзакциями
$db->beginTransaction();
try {
    Database::table('users')->insert([...]);
    Database::table('posts')->insert([...]);
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
}

// ============================================================================
// 17. Дополнительные возможности DatabaseManager
// ============================================================================

echo "\n17. Дополнительные возможности:\n";

// Получить список таблиц
$tables = $db->getTables();
echo "Таблицы в БД: " . implode(', ', $tables) . "\n";

// Проверить существование таблицы
if ($db->hasTable('users')) {
    echo "Таблица 'users' существует\n";
}

// Получить колонки таблицы
$columns = $db->getColumns('users');
echo "Колонки таблицы 'users': " . count($columns) . "\n";

// Информация о соединении
$info = $db->getConnectionInfo();
echo "Драйвер: {$info['driver']}\n";

// Имя драйвера
$driver = $db->getDriverName();
echo "Драйвер БД: {$driver}\n";

// Имя базы данных
$dbName = $db->getDatabaseName();
echo "Имя БД: {$dbName}\n";

// ============================================================================
// 18. Debug методы
// ============================================================================

echo "\n18. Debug методы:\n";

// dump() - показать SQL и продолжить
Database::table('users')
    ->where('age', '>', 18)
    ->dump()
    ->get();

// dd() - показать SQL и остановить выполнение
// Database::table('users')->where('age', '>', 18)->dd();

// toSql() - получить SQL без выполнения
$sql = Database::table('users')
    ->where('age', '>', 18)
    ->orderBy('name')
    ->toSql();
echo "SQL: {$sql}\n";

// ============================================================================
// 19. Клонирование query builder
// ============================================================================

echo "\n19. Клонирование QueryBuilder:\n";

$baseQuery = Database::table('users')
    ->where('active', 1)
    ->where('country', 'USA');

// Клонируем для разных запросов
$youngUsers = $baseQuery->clone()->where('age', '<', 30)->get();
$oldUsers = $baseQuery->clone()->where('age', '>=', 30)->get();

echo "Молодых пользователей: " . count($youngUsers) . "\n";
echo "Взрослых пользователей: " . count($oldUsers) . "\n";

// ============================================================================
// 20. Сложный пример
// ============================================================================

echo "\n20. Сложный комплексный запрос:\n";

$results = Database::table('orders')
    ->select('users.name', 'users.email', 
             'COUNT(orders.id) as order_count',
             'SUM(orders.total) as total_spent',
             'AVG(orders.total) as avg_order')
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
    ->where('orders.status', 'completed')
    ->where(function($query) {
        $query->where('orders.created_at', '>=', '2024-01-01')
              ->orWhereNotNull('users.premium_until');
    })
    ->groupBy('users.id', 'users.name', 'users.email')
    ->having('order_count', '>=', 5)
    ->orderByDesc('total_spent')
    ->limit(100)
    ->get();

echo "SQL: Выборка топ-100 покупателей с более чем 5 заказами\n";
echo "Найдено: " . count($results) . " пользователей\n";

// ============================================================================
// Очистка
// ============================================================================

echo "\n=== Демонстрация завершена ===\n";

// Выключаем логирование
$db->disableQueryLog();

// Очищаем лог
$db->flushQueryLog();

echo "\nВсе новые возможности продемонстрированы!\n";
echo "Подробную документацию смотрите в docs/Database.md\n";
