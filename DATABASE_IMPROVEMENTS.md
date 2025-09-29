# Улучшения базы данных и QueryBuilder

## Обзор

Значительно улучшена система работы с базой данных вашего PHP фреймворка. Добавлено множество новых возможностей, повышена гибкость и производительность.

## Основные улучшения

### 1. QueryBuilder - Расширенные возможности WHERE

#### Новые методы WHERE:
- ✅ `whereIn()` / `whereNotIn()` - проверка вхождения в массив
- ✅ `orWhereIn()` / `orWhereNotIn()` - OR версии
- ✅ `whereNull()` / `whereNotNull()` - проверка на NULL
- ✅ `orWhereNull()` / `orWhereNotNull()` - OR версии
- ✅ `whereBetween()` / `whereNotBetween()` - диапазон значений
- ✅ `whereLike()` / `orWhereLike()` - LIKE поиск
- ✅ `orWhere()` - OR условия

#### Вложенные условия:
```php
Database::table('users')
    ->where('country', 'USA')
    ->where(function($query) {
        $query->where('age', '>', 18)
              ->orWhere('verified', 1);
    })
    ->get();
```

#### Массив условий:
```php
Database::table('users')
    ->where([
        'active' => 1,
        'verified' => 1
    ])
    ->get();
```

### 2. Расширенные JOIN

- ✅ `leftJoin()` - LEFT JOIN
- ✅ `rightJoin()` - RIGHT JOIN
- ✅ `crossJoin()` - CROSS JOIN
- ✅ Поддержка вложенных JOIN с несколькими условиями

```php
Database::table('orders')
    ->join('users', function($join) {
        $join->on('orders.user_id', '=', 'users.id')
             ->on('orders.status', '=', 'users.status');
    })
    ->get();
```

### 3. GROUP BY и HAVING

```php
Database::table('orders')
    ->select('user_id', 'COUNT(*) as total')
    ->groupBy('user_id', 'status')
    ->having('total', '>', 10)
    ->orHaving('SUM(amount)', '>', 1000)
    ->get();
```

### 4. DISTINCT

```php
Database::table('users')
    ->distinct()
    ->select('country')
    ->get();
```

### 5. Агрегатные функции

- ✅ `count()` - количество записей
- ✅ `sum()` - сумма значений
- ✅ `avg()` - среднее значение
- ✅ `max()` - максимальное значение
- ✅ `min()` - минимальное значение

```php
$count = Database::table('users')->count();
$avgAge = Database::table('users')->avg('age');
$total = Database::table('orders')->sum('amount');
```

### 6. Удобные helper методы

- ✅ `latest()` / `oldest()` - сортировка по времени
- ✅ `value()` - получить значение одной колонки
- ✅ `pluck()` - получить массив значений
- ✅ `exists()` / `doesntExist()` - проверка существования
- ✅ `take()` / `skip()` - алиасы для limit/offset
- ✅ `orderByDesc()` - сортировка по убыванию

```php
$email = Database::table('users')->where('id', 1)->value('email');
$emails = Database::table('users')->pluck('email', 'id');
$recent = Database::table('posts')->latest()->take(10)->get();
```

### 7. Пагинация

Встроенная автоматическая пагинация:

```php
$result = Database::table('users')
    ->where('active', 1)
    ->paginate($page = 1, $perPage = 15);

// Возвращает:
// - data: массив записей
// - total: общее количество
// - per_page: записей на страницу
// - current_page: текущая страница
// - last_page: последняя страница
// - from/to: диапазон записей
```

### 8. INSERT/UPDATE/DELETE в QueryBuilder

#### INSERT:
```php
// Одиночная вставка
Database::table('users')->insert([
    'name' => 'John',
    'email' => 'john@example.com'
]);

// С получением ID
$id = Database::table('users')->insertGetId([...]);

// Batch insert
Database::table('users')->insert([
    ['name' => 'User 1', ...],
    ['name' => 'User 2', ...],
]);
```

#### UPDATE:
```php
$affected = Database::table('users')
    ->where('id', 1)
    ->update(['name' => 'New Name']);

// Increment/Decrement
Database::table('posts')->where('id', 1)->increment('views', 5);
Database::table('users')->where('id', 1)->decrement('credits', 10);
```

#### DELETE:
```php
$deleted = Database::table('users')
    ->where('active', 0)
    ->delete();

Database::table('logs')->truncate(); // Очистка таблицы
```

### 9. Query Logging

Полное логирование всех SQL запросов для отладки и анализа производительности:

```php
$db = Database::getInstance();

// Включить логирование
$db->enableQueryLog();

// Выполнить запросы...

// Получить лог
$queries = $db->getQueryLog();
// [
//     ['query' => 'SELECT...', 'bindings' => [...], 'time' => 2.45, ...],
//     ...
// ]

// Последний запрос
$lastQuery = $db->getLastQuery();

// Статистика производительности
$stats = $db->getQueryStats();
// [
//     'total_queries' => 25,
//     'total_time' => 150.25,
//     'avg_time' => 6.01,
//     'max_time' => 45.32,
//     'min_time' => 0.84,
//     'failed_queries' => 0
// ]

// Медленные запросы (> 100ms)
$slowQueries = $db->getSlowQueries(100);
```

### 10. Улучшенный DatabaseManager

#### Автоматическое переподключение:
```php
// Автоматически переподключается при потере соединения
$db->setReconnectAttempts(5); // до 5 попыток
```

#### Информация о базе данных:
```php
$tables = $db->getTables();           // Список таблиц
$exists = $db->hasTable('users');     // Проверка существования
$columns = $db->getColumns('users');  // Колонки таблицы
$driver = $db->getDriverName();       // Имя драйвера
$dbName = $db->getDatabaseName();     // Имя БД
```

#### Улучшенные транзакции:
```php
// Проверка активной транзакции
if ($db->inTransaction()) {
    // Внутри транзакции
}

// Безопасный commit/rollback
$db->commit();   // Не выбросит исключение, если нет транзакции
$db->rollback(); // Не выбросит исключение, если нет транзакции
```

### 11. Улучшенный BaseModel

#### Атрибуты и Casts:
```php
class User extends BaseModel
{
    protected array $casts = [
        'age' => 'int',
        'is_active' => 'bool',
        'settings' => 'json',
        'verified_at' => 'datetime'
    ];
    
    protected array $hidden = ['password', 'secret_token'];
}
```

#### Accessors и Mutators:
```php
// Accessor - модифицирует при чтении
protected function getNameAttribute($value)
{
    return ucfirst($value);
}

// Mutator - модифицирует при записи
protected function setEmailAttribute($value)
{
    return strtolower($value);
}
```

#### Scopes:
```php
// Local scope
public function scopeActive($query)
{
    return $query->where('active', 1);
}

// Использование
$users = User::active()->get();
```

#### Global Scopes:
```php
protected function boot()
{
    parent::boot();
    
    static::addGlobalScope(function($query) {
        $query->where('tenant_id', getCurrentTenantId());
    });
}
```

#### Soft Deletes:
```php
class User extends BaseModel
{
    protected bool $softDeletes = true;
}

// Использование
User::destroy(1);              // Soft delete
User::forceDelete(1);          // Hard delete
User::restore(1);              // Восстановить
$deleted = User::onlyTrashed()->get();  // Только удаленные
$all = User::withTrashed()->get();      // Все включая удаленные
```

#### События (Events):
```php
protected function onCreating($data)
{
    // Перед созданием
}

protected function onCreated($id)
{
    // После создания
}

protected function onUpdating($data) { }
protected function onUpdated($id) { }
protected function onDeleting($id) { }
protected function onDeleted($id) { }
```

#### Relationships (базовая поддержка):
```php
public function profile()
{
    return $this->hasOne(Profile::class);
}

public function posts()
{
    return $this->hasMany(Post::class);
}

public function author()
{
    return $this->belongsTo(User::class);
}

public function roles()
{
    return $this->belongsToMany(Role::class, 'user_roles');
}
```

#### Статические методы:
```php
User::find($id);
User::findOrFail($id);
User::findBy('email', 'test@example.com');
User::all();
User::where('age', '>', 18)->get();
User::whereIn('status', ['active', 'pending'])->get();
User::latest()->get();
User::paginate(1, 15);
User::count();
User::max('age');
User::exists();
```

### 12. Улучшенная система биндингов

Биндинги теперь организованы по типам для лучшей структуризации:

```php
$bindings = [
    'select' => [],
    'join' => [],
    'where' => [18, 'USA'],
    'having' => [],
    'order' => []
];
```

### 13. Debug возможности

```php
// Показать SQL и продолжить
Database::table('users')
    ->where('age', '>', 18)
    ->dump()
    ->get();

// Показать SQL и остановить
Database::table('users')
    ->where('age', '>', 18)
    ->dd();

// Получить SQL
$sql = Database::table('users')
    ->where('age', '>', 18)
    ->toSql();
```

### 14. Клонирование QueryBuilder

```php
$baseQuery = Database::table('users')
    ->where('active', 1);

$youngUsers = $baseQuery->clone()->where('age', '<', 30)->get();
$oldUsers = $baseQuery->clone()->where('age', '>=', 30)->get();
```

### 15. Expression класс для raw SQL

```php
use Core\Database\Expression;

Database::table('users')->update([
    'views' => new Expression('views + 1')
]);
```

## Обратная совместимость

✅ Все изменения полностью обратно совместимы с существующим кодом.
✅ Старые методы продолжают работать как раньше.
✅ Добавлены только новые возможности и улучшения.

## Производительность

- Оптимизированная генерация SQL
- Prepared statements для защиты от SQL injection
- Автоматическое переподключение при потере соединения
- Query logging для анализа узких мест
- Эффективная работа с большими датасетами

## Документация

Полная документация доступна в:
- `docs/Database.md` - подробная документация со всеми примерами
- `examples/database_usage.php` - практические примеры использования

## Тесты

Все тесты обновлены и проходят успешно:
- ✅ `tests/Unit/Core/Database/DatabaseManagerTest.php`
- ✅ `tests/Unit/Core/Database/QueryBuilderTest.php`

## Что дальше?

Рекомендации по использованию:

1. **Изучите документацию** в `docs/Database.md`
2. **Запустите примеры** из `examples/database_usage.php`
3. **Включите query logging** на development окружении
4. **Используйте scopes** для инкапсуляции бизнес-логики
5. **Применяйте soft deletes** для важных данных
6. **Используйте транзакции** для связанных операций

## Поддержка

Все улучшения полностью протестированы и готовы к использованию в production.

---

**Автор улучшений:** AI Assistant  
**Дата:** 2025-09-29  
**Версия:** 2.0
