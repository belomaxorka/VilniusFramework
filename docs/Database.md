# База данных и QueryBuilder

Мощная система работы с базами данных с поддержкой MySQL, PostgreSQL и SQLite.

## Содержание

- [Подключение](#подключение)
- [QueryBuilder](#querybuilder)
- [Модели (Models)](#модели-models)
- [Query Logging](#query-logging)
- [Транзакции](#транзакции)

## Подключение

Конфигурация в `config/database.php`:

```php
return [
    'default' => env('DB_CONNECTION', 'sqlite'),
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => (int)env('DB_PORT', 3306),
            'database' => env('DB_NAME', 'myapp'),
            'username' => env('DB_USER', 'root'),
            'password' => env('DB_PASS', ''),
        ],
    ],
];
```

Инициализация:

```php
use Core\Database;

Database::init();
```

## QueryBuilder

### Базовые запросы

#### SELECT

```php
// Получить все записи
$users = Database::table('users')->get();

// Получить первую запись
$user = Database::table('users')->first();

// Выбор колонок
$users = Database::table('users')
    ->select('id', 'name', 'email')
    ->get();

// Или массивом
$users = Database::table('users')
    ->select(['id', 'name', 'email'])
    ->get();

// DISTINCT
$users = Database::table('users')
    ->distinct()
    ->select('country')
    ->get();
```

#### WHERE условия

```php
// Простое WHERE
$users = Database::table('users')
    ->where('age', '>', 18)
    ->get();

// Короткая форма (оператор = по умолчанию)
$user = Database::table('users')
    ->where('email', 'john@example.com')
    ->first();

// Несколько условий
$users = Database::table('users')
    ->where('age', '>', 18)
    ->where('country', '=', 'USA')
    ->get();

// Массив условий (все через AND)
$users = Database::table('users')
    ->where([
        'active' => 1,
        'verified' => 1
    ])
    ->get();

// OR WHERE
$users = Database::table('users')
    ->where('country', 'USA')
    ->orWhere('country', 'Canada')
    ->get();
```

#### WHERE IN / NOT IN

```php
// WHERE IN
$users = Database::table('users')
    ->whereIn('id', [1, 2, 3, 4, 5])
    ->get();

// WHERE NOT IN
$users = Database::table('users')
    ->whereNotIn('status', ['banned', 'deleted'])
    ->get();

// OR WHERE IN
$users = Database::table('users')
    ->where('country', 'USA')
    ->orWhereIn('country', ['Canada', 'Mexico'])
    ->get();
```

#### WHERE NULL / NOT NULL

```php
// WHERE NULL
$users = Database::table('users')
    ->whereNull('deleted_at')
    ->get();

// WHERE NOT NULL
$users = Database::table('users')
    ->whereNotNull('email_verified_at')
    ->get();

// OR WHERE NULL
$users = Database::table('users')
    ->where('active', 1)
    ->orWhereNull('phone')
    ->get();
```

#### WHERE BETWEEN

```php
// WHERE BETWEEN
$users = Database::table('users')
    ->whereBetween('age', [18, 65])
    ->get();

// WHERE NOT BETWEEN
$products = Database::table('products')
    ->whereNotBetween('price', [100, 500])
    ->get();
```

#### WHERE LIKE

```php
// WHERE LIKE
$users = Database::table('users')
    ->whereLike('name', 'John%')
    ->get();

// OR WHERE LIKE
$users = Database::table('users')
    ->whereLike('name', 'John%')
    ->orWhereLike('name', 'Jane%')
    ->get();
```

#### Вложенные условия

```php
// Сложные вложенные условия
$users = Database::table('users')
    ->where('country', 'USA')
    ->where(function($query) {
        $query->where('age', '>', 18)
              ->orWhere('verified', 1);
    })
    ->get();

// SQL: SELECT * FROM users WHERE country = 'USA' AND (age > 18 OR verified = 1)
```

### JOINs

```php
// INNER JOIN
$orders = Database::table('orders')
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->select('orders.*', 'users.name')
    ->get();

// LEFT JOIN
$users = Database::table('users')
    ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
    ->get();

// RIGHT JOIN
$orders = Database::table('orders')
    ->rightJoin('users', 'orders.user_id', '=', 'users.id')
    ->get();

// CROSS JOIN
$combinations = Database::table('colors')
    ->crossJoin('sizes')
    ->get();

// Сложный JOIN с несколькими условиями
$orders = Database::table('orders')
    ->join('users', function($join) {
        $join->on('orders.user_id', '=', 'users.id')
             ->on('orders.status', '=', 'users.status');
    })
    ->get();
```

### GROUP BY и HAVING

```php
// GROUP BY
$stats = Database::table('orders')
    ->select('user_id', 'COUNT(*) as total')
    ->groupBy('user_id')
    ->get();

// Несколько колонок
$stats = Database::table('sales')
    ->groupBy('country', 'city')
    ->get();

// GROUP BY с HAVING
$users = Database::table('orders')
    ->select('user_id', 'COUNT(*) as order_count')
    ->groupBy('user_id')
    ->having('order_count', '>', 10)
    ->get();

// HAVING с OR
$users = Database::table('orders')
    ->groupBy('user_id')
    ->having('COUNT(*)', '>', 10)
    ->orHaving('SUM(total)', '>', 1000)
    ->get();
```

### Сортировка

```php
// ORDER BY
$users = Database::table('users')
    ->orderBy('name', 'ASC')
    ->get();

// Короткая форма для DESC
$users = Database::table('users')
    ->orderByDesc('created_at')
    ->get();

// Несколько сортировок
$users = Database::table('users')
    ->orderBy('country', 'ASC')
    ->orderBy('name', 'ASC')
    ->get();

// Latest / Oldest (для timestamps)
$posts = Database::table('posts')->latest()->get();
$posts = Database::table('posts')->oldest()->get();

// С кастомной колонкой
$posts = Database::table('posts')->latest('published_at')->get();
```

### LIMIT и OFFSET

```php
// LIMIT
$users = Database::table('users')->limit(10)->get();

// Или через alias
$users = Database::table('users')->take(10)->get();

// OFFSET
$users = Database::table('users')
    ->offset(20)
    ->limit(10)
    ->get();

// Или через alias
$users = Database::table('users')
    ->skip(20)
    ->take(10)
    ->get();
```

### Пагинация

```php
// Автоматическая пагинация
$result = Database::table('users')
    ->where('active', 1)
    ->paginate($page = 1, $perPage = 15);

/*
Результат:
[
    'data' => [...],           // Записи текущей страницы
    'total' => 150,            // Всего записей
    'per_page' => 15,          // Записей на страницу
    'current_page' => 1,       // Текущая страница
    'last_page' => 10,         // Последняя страница
    'from' => 1,               // Начальный номер записи
    'to' => 15                 // Конечный номер записи
]
*/
```

### Агрегатные функции

```php
// COUNT
$count = Database::table('users')->count();
$count = Database::table('users')->where('active', 1)->count();

// COUNT с колонкой
$count = Database::table('orders')->count('id');

// SUM
$total = Database::table('orders')->sum('amount');

// AVG
$average = Database::table('products')->avg('price');

// MAX
$maxPrice = Database::table('products')->max('price');

// MIN
$minPrice = Database::table('products')->min('price');
```

### Проверка существования

```php
// Проверить существование записей
if (Database::table('users')->where('email', 'test@example.com')->exists()) {
    // Записи существуют
}

// Проверить отсутствие записей
if (Database::table('users')->where('banned', 1)->doesntExist()) {
    // Нет забаненных пользователей
}
```

### Получение значений

```php
// Получить значение одной колонки первой записи
$email = Database::table('users')
    ->where('id', 1)
    ->value('email');

// Получить массив значений одной колонки
$emails = Database::table('users')
    ->pluck('email');
// Результат: ['john@example.com', 'jane@example.com', ...]

// Получить массив с ключами
$users = Database::table('users')
    ->pluck('name', 'id');
// Результат: [1 => 'John', 2 => 'Jane', ...]
```

### INSERT

```php
// Вставить одну запись
Database::table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30
]);

// Вставить и получить ID
$userId = Database::table('users')->insertGetId([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);

// Batch insert - вставить несколько записей
Database::table('users')->insert([
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com'],
    ['name' => 'User 3', 'email' => 'user3@example.com'],
]);
```

### UPDATE

```php
// Обновить записи
$affected = Database::table('users')
    ->where('id', 1)
    ->update([
        'name' => 'John Smith',
        'updated_at' => date('Y-m-d H:i:s')
    ]);

// Обновить несколько записей
$affected = Database::table('users')
    ->where('country', 'USA')
    ->update(['timezone' => 'America/New_York']);

// Increment / Decrement
$affected = Database::table('posts')
    ->where('id', 1)
    ->increment('views'); // +1

$affected = Database::table('posts')
    ->where('id', 1)
    ->increment('views', 5); // +5

$affected = Database::table('users')
    ->where('id', 1)
    ->decrement('credits', 10); // -10

// С дополнительными полями
Database::table('posts')
    ->where('id', 1)
    ->increment('views', 1, ['last_viewed_at' => date('Y-m-d H:i:s')]);
```

### DELETE

```php
// Удалить записи
$deleted = Database::table('users')
    ->where('active', 0)
    ->delete();

// Удалить по ID
$deleted = Database::table('users')
    ->where('id', 1)
    ->delete();

// Очистить таблицу
Database::table('logs')->truncate();
```

### Debug

```php
// Показать SQL и биндинги
Database::table('users')
    ->where('age', '>', 18)
    ->dump() // Выводит SQL и продолжает
    ->get();

// Показать SQL и остановить выполнение
Database::table('users')
    ->where('age', '>', 18)
    ->dd(); // Die and dump

// Получить SQL без выполнения
$sql = Database::table('users')
    ->where('age', '>', 18)
    ->toSql();
echo $sql; // SELECT * FROM users WHERE age > ?
```

## Модели (Models)

### Создание модели

```php
namespace App\Models;

use App\Models\BaseModel;

class User extends BaseModel
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    
    // Разрешенные для заполнения поля
    protected array $fillable = ['name', 'email', 'age', 'country'];
    
    // Или защищенные поля (противоположность fillable)
    // protected array $guarded = ['id', 'password'];
    
    // Скрытые поля (не попадут в toArray/toJson)
    protected array $hidden = ['password', 'secret_token'];
    
    // Приведение типов
    protected array $casts = [
        'age' => 'int',
        'is_active' => 'bool',
        'settings' => 'json',
        'verified_at' => 'datetime'
    ];
    
    // Автоматические timestamps
    protected bool $timestamps = true;
    
    // Soft deletes
    protected bool $softDeletes = true;
}
```

### Использование моделей

#### Получение данных

```php
use App\Models\User;

// Получить все записи
$users = User::all();

// Найти по ID
$user = User::find(1);

// Найти или выбросить исключение
$user = User::findOrFail(1);

// Найти по атрибуту
$user = User::findBy('email', 'john@example.com');

// Получить первую запись
$user = User::first();

// WHERE условия
$users = User::where('age', '>', 18)->get();
$users = User::whereIn('country', ['USA', 'Canada'])->get();
$users = User::whereNull('deleted_at')->get();

// Сортировка
$users = User::orderBy('name', 'ASC')->get();
$users = User::latest()->get(); // По created_at DESC
$users = User::oldest()->get(); // По created_at ASC

// Limit
$users = User::limit(10)->get();

// Пагинация
$result = User::where('active', 1)->paginate(1, 15);
```

#### Создание записей

```php
// Создать запись
$userId = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30
]);

// Timestamps (created_at, updated_at) добавятся автоматически
```

#### Обновление записей

```php
// Обновить запись
$affected = User::updateRecord(1, [
    'name' => 'John Smith',
    'age' => 31
]);

// updated_at обновится автоматически
```

#### Удаление записей

```php
// Удалить запись
$deleted = User::destroy(1);

// При включенном softDeletes это будет soft delete
// Запись не удалится физически, только установится deleted_at

// Принудительное удаление (hard delete)
User::forceDelete(1);

// Восстановить удаленную запись
User::restore(1);

// Получить только удаленные записи
$deleted = User::onlyTrashed()->get();

// Получить все записи включая удаленные
$all = User::withTrashed()->get();
```

#### Агрегатные функции

```php
// Количество
$count = User::count();
$count = User::where('active', 1)->count();

// Сумма
$total = User::sum('credits');

// Среднее
$avgAge = User::avg('age');

// Максимум
$maxAge = User::max('age');

// Минимум
$minAge = User::min('age');

// Существование
if (User::where('email', 'test@example.com')->exists()) {
    // Пользователь существует
}
```

### Accessors и Mutators

```php
class User extends BaseModel
{
    // Accessor - модифицирует значение при чтении
    protected function getNameAttribute($value)
    {
        return ucfirst($value);
    }
    
    // Mutator - модифицирует значение при записи
    protected function setEmailAttribute($value)
    {
        return strtolower($value);
    }
}

// Использование
$user = new User(['name' => 'john', 'email' => 'JOHN@EXAMPLE.COM']);
echo $user->name;  // "John" (uppercase first letter)
echo $user->email; // "john@example.com" (lowercase)
```

### Scopes

```php
class User extends BaseModel
{
    // Local scope
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }
    
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }
    
    public function scopeOfCountry($query, $country)
    {
        return $query->where('country', $country);
    }
}

// Использование scopes
$users = User::active()->get();
$users = User::active()->verified()->get();
$users = User::ofCountry('USA')->get();
```

### Global Scopes

```php
class User extends BaseModel
{
    protected function boot()
    {
        parent::boot();
        
        // Применяется ко всем запросам автоматически
        static::addGlobalScope(function($query) {
            $query->where('tenant_id', getCurrentTenantId());
        });
    }
}
```

### Events (События)

```php
class User extends BaseModel
{
    // Вызывается перед созданием
    protected function onCreating($data)
    {
        // Можно модифицировать данные перед вставкой
    }
    
    // Вызывается после создания
    protected function onCreated($id)
    {
        // Выполнить действия после создания
        // Например, отправить email
    }
    
    // Перед обновлением
    protected function onUpdating($data)
    {
        // Действия перед обновлением
    }
    
    // После обновления
    protected function onUpdated($id)
    {
        // Действия после обновления
    }
    
    // Перед удалением
    protected function onDeleting($id)
    {
        // Действия перед удалением
    }
    
    // После удаления
    protected function onDeleted($id)
    {
        // Действия после удаления
    }
}
```

### Relationships (Связи)

```php
class User extends BaseModel
{
    // One to One
    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id', 'id');
    }
    
    // One to Many
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id', 'id');
    }
}

class Post extends BaseModel
{
    // Inverse relationship
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

class User extends BaseModel
{
    // Many to Many
    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles',      // pivot table
            'user_id',         // foreign key
            'role_id'          // related key
        );
    }
}
```

## Query Logging

Логирование всех SQL запросов для отладки и анализа производительности.

### Включение логирования

```php
use Core\Database;

$db = Database::getInstance();

// Включить логирование
$db->enableQueryLog();

// Выполнить запросы
User::where('age', '>', 18)->get();
Database::table('posts')->count();

// Получить лог запросов
$queries = $db->getQueryLog();

/*
Результат:
[
    [
        'query' => 'SELECT * FROM users WHERE age > ?',
        'bindings' => [18],
        'time' => 2.45,  // миллисекунды
        'error' => null,
        'timestamp' => '2025-01-15 10:30:45'
    ],
    ...
]
*/

// Получить последний запрос
$lastQuery = $db->getLastQuery();

// Очистить лог
$db->flushQueryLog();

// Выключить логирование
$db->disableQueryLog();
```

### Статистика производительности

```php
$db = Database::getInstance();
$db->enableQueryLog();

// Выполнить запросы...

// Получить статистику
$stats = $db->getQueryStats();

/*
Результат:
[
    'total_queries' => 25,
    'total_time' => 150.25,      // мс
    'avg_time' => 6.01,          // мс
    'max_time' => 45.32,         // мс
    'min_time' => 0.84,          // мс
    'failed_queries' => 0
]
*/

// Получить медленные запросы (> 100ms)
$slowQueries = $db->getSlowQueries(100);
```

### В конфигурации

```php
// config/database.php
return [
    'default' => 'mysql',
    'log_queries' => env('DB_LOG_QUERIES', false), // Включить логирование
    'connections' => [
        // ...
    ]
];
```

## Транзакции

```php
use Core\Database;

$db = Database::getInstance();

// Автоматическая транзакция с callback
try {
    $result = $db->transaction(function($db) {
        // Все операции внутри транзакции
        $userId = Database::table('users')->insertGetId([
            'name' => 'John',
            'email' => 'john@example.com'
        ]);
        
        Database::table('profiles')->insert([
            'user_id' => $userId,
            'bio' => 'Hello world'
        ]);
        
        return $userId;
    });
    
    // Транзакция успешно завершена
} catch (\Exception $e) {
    // При ошибке автоматически сделан rollback
}

// Ручное управление транзакциями
$db->beginTransaction();

try {
    // Операции с базой
    User::create([...]);
    Post::create([...]);
    
    $db->commit();
} catch (\Exception $e) {
    $db->rollback();
    throw $e;
}

// Проверить активна ли транзакция
if ($db->inTransaction()) {
    // Внутри транзакции
}
```

## Дополнительные возможности

### Переподключение

```php
$db = Database::getInstance();

// Переподключиться к базе данных
$db->reconnect();

// Настроить количество попыток переподключения
$db->setReconnectAttempts(5);

// При потере соединения будет автоматически
// произведено до 5 попыток переподключения
```

### Информация о базе данных

```php
$db = Database::getInstance();

// Получить информацию о соединении
$info = $db->getConnectionInfo();

// Получить имя драйвера
$driver = $db->getDriverName(); // 'mysql', 'pgsql', 'sqlite'

// Получить имя базы данных
$dbName = $db->getDatabaseName();

// Получить список таблиц
$tables = $db->getTables();

// Проверить существование таблицы
if ($db->hasTable('users')) {
    // Таблица существует
}

// Получить колонки таблицы
$columns = $db->getColumns('users');
```

### Raw SQL

```php
// Выполнить произвольный SQL
Database::raw("UPDATE users SET status = 'active' WHERE verified = 1");

// С биндингами
Database::raw(
    "UPDATE users SET status = ? WHERE created_at < ?",
    ['inactive', date('Y-m-d', strtotime('-1 year'))]
);
```

## Примеры использования

### Пример 1: Сложный запрос с условиями

```php
$users = Database::table('users')
    ->select('users.*', 'profiles.bio')
    ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
    ->where(function($query) {
        $query->where('users.country', 'USA')
              ->orWhere('users.country', 'Canada');
    })
    ->where('users.age', '>=', 18)
    ->whereNotNull('users.email_verified_at')
    ->orderBy('users.created_at', 'DESC')
    ->limit(50)
    ->get();
```

### Пример 2: Статистика по пользователям

```php
$stats = Database::table('orders')
    ->select('user_id', 'COUNT(*) as order_count', 'SUM(total) as total_spent')
    ->groupBy('user_id')
    ->having('order_count', '>', 5)
    ->orderByDesc('total_spent')
    ->get();
```

### Пример 3: Batch операции

```php
// Вставить много записей
$users = [
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com'],
    // ... еще 1000 пользователей
];

Database::table('users')->insert($users);

// Обновить много записей
Database::table('users')
    ->where('last_login', '<', date('Y-m-d', strtotime('-1 year')))
    ->update(['status' => 'inactive']);
```

### Пример 4: Модель с полным функционалом

```php
namespace App\Models;

class Post extends BaseModel
{
    protected string $table = 'posts';
    protected array $fillable = ['title', 'content', 'user_id', 'status'];
    protected array $hidden = ['user_id'];
    protected array $casts = [
        'published_at' => 'datetime',
        'views' => 'int',
        'metadata' => 'json'
    ];
    protected bool $softDeletes = true;
    
    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->whereNotNull('published_at');
    }
    
    public function scopePopular($query, $minViews = 1000)
    {
        return $query->where('views', '>=', $minViews);
    }
    
    // Accessor
    protected function getTitleAttribute($value)
    {
        return ucwords($value);
    }
    
    // Mutator
    protected function setSlugAttribute($value)
    {
        return strtolower(str_replace(' ', '-', $value));
    }
    
    // Events
    protected function onCreating($data)
    {
        // Генерируем slug автоматически
        if (!isset($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }
    }
    
    // Relations
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }
    
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }
}

// Использование
$posts = Post::published()
    ->popular(5000)
    ->latest()
    ->paginate(1, 20);
```

## Best Practices

1. **Используйте биндинги** - всегда используйте параметризованные запросы вместо конкатенации строк
2. **Индексируйте колонки** - добавляйте индексы на колонки, используемые в WHERE, JOIN
3. **Ограничивайте выборки** - используйте `select()` для выбора только нужных колонок
4. **Используйте пагинацию** - для больших выборок используйте `paginate()`
5. **Включайте query log на development** - для отладки и оптимизации запросов
6. **Используйте транзакции** - для связанных операций используйте транзакции
7. **Модели для бизнес-логики** - используйте модели для инкапсуляции логики
8. **Soft deletes для важных данных** - используйте мягкое удаление для пользовательских данных

## Производительность

- QueryBuilder генерирует эффективные SQL запросы
- Поддержка prepared statements для защиты от SQL injection
- Автоматическое переподключение при потере соединения
- Query logging для анализа медленных запросов
- Поддержка batch операций для массовых вставок/обновлений
