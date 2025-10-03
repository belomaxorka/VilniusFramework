# Примеры работы с базой данных

## Использование Database через Dependency Injection

В контроллерах рекомендуется использовать Database через DI вместо статических методов:

### Правильно ✅

```php
<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Database;
use Core\Request;
use Core\Response;

class UserController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
        protected Database $db,  // ← Внедрение через DI
    ) {
        parent::__construct($request, $response);
    }

    public function index(): Response
    {
        // Используем $this->db вместо статических вызовов
        $users = $this->db->table('users')->get();
        
        return $this->json($users);
    }
}
```

### Неправильно ❌

```php
// НЕ используйте статические вызовы в контроллерах
$users = Database::table('users')->get();
```

## Примеры запросов

### 1. Получение всех записей

```php
$users = $this->db->table('users')->get();
```

### 2. Получение с условиями

```php
$verifiedUsers = $this->db->table('users')
    ->whereNotNull('email_verified_at')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();
```

### 3. Получение одной записи

```php
$user = $this->db->table('users')
    ->where('id', 1)
    ->first();
```

### 4. Подсчет записей

```php
$totalUsers = $this->db->table('users')->count();
```

### 5. Сырые SQL запросы

```php
$results = $this->db->select(
    'SELECT name, email FROM users WHERE created_at > ? LIMIT ?',
    [date('Y-m-d', strtotime('-30 days')), 10]
);
```

### 6. Вставка данных

```php
$this->db->table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('password123', PASSWORD_DEFAULT),
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);
```

### 7. Обновление данных

```php
$this->db->table('users')
    ->where('id', 1)
    ->update([
        'name' => 'Updated Name',
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
```

### 8. Удаление данных

```php
$this->db->table('users')
    ->where('id', 1)
    ->delete();
```

### 9. Транзакции

```php
$this->db->transaction(function () {
    $this->db->table('users')->insert([
        'name' => 'User 1',
        'email' => 'user1@example.com',
        'password' => password_hash('password', PASSWORD_DEFAULT),
    ]);
    
    $this->db->table('users')->insert([
        'name' => 'User 2',
        'email' => 'user2@example.com',
        'password' => password_hash('password', PASSWORD_DEFAULT),
    ]);
});
```

## Использование в консольных командах

В консольных командах получайте Database через Container:

```php
<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\Container;
use Core\Database;

class MyCommand extends Command
{
    protected string $signature = 'my:command';
    protected string $description = 'My command description';

    public function handle(): int
    {
        // Получаем Database из контейнера
        $container = Container::getInstance();
        $db = $container->make(Database::class);
        
        // Используем $db
        $users = $db->table('users')->get();
        
        $this->info('Found ' . count($users) . ' users');
        
        return 0;
    }
}
```

## Заполнение тестовыми данными

Для добавления тестовых пользователей в базу данных используйте команду:

```bash
php vilnius db:seed
```

Эта команда добавит 5 тестовых пользователей в таблицу `users`.

## Миграции

Запустите миграции для создания таблиц:

```bash
php vilnius migrate
```

Просмотреть статус миграций:

```bash
php vilnius migrate:status
```

Откатить последнюю миграцию:

```bash
php vilnius migrate:rollback
```

## Преимущества Dependency Injection

1. **Тестируемость** - легко подменить зависимость в тестах
2. **Явные зависимости** - сразу видно, что нужно классу
3. **Слабая связанность** - класс не зависит от глобального состояния
4. **Гибкость** - легко изменить реализацию через контейнер

