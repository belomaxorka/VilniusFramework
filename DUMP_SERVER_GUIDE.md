# 🐛 Dump Server - Руководство по использованию

## Что это такое?

**Dump Server** — это **НЕ веб-сервер**! Это специальный TCP-сервер для приёма debug-информации из вашего приложения.

### Зачем это нужно?

**Проблема:**
```php
var_dump($data); // ❌ Ломает HTML, некрасивый вывод
dd($data);       // ❌ Останавливает выполнение
```

**Решение:**
```php
server_dump($data); // ✅ Отправляет в отдельное окно!
```

---

## 🚀 Быстрый старт

### Шаг 1: Запустите Dump Server

**Откройте ПЕРВЫЙ терминал:**

```bash
php vilnius dump-server
```

**Вывод:**
```
╔═══════════════════════════════════════════════════════════╗
║                                                           ║
║              🐛 DEBUG DUMP SERVER 🐛                     ║
║                                                           ║
╚═══════════════════════════════════════════════════════════╝

ℹ Server listening on 127.0.0.1:9912
Press Ctrl+C to stop

🚀 Dump Server started on 127.0.0.1:9912
Waiting for dumps...
```

✅ **Сервер запущен!** Оставьте это окно открытым.

---

### Шаг 2: Тестовый запуск

**Откройте ВТОРОЙ терминал:**

```bash
php test-dump.php
```

**В первом окне (Dump Server) увидите:**
```
────────────────────────────────────────────────────────────────────────────────
⏰ 14:23:45 📝 Test Data 📍 test-dump.php:40
────────────────────────────────────────────────────────────────────────────────
Array
(
    [message] => Hello from test script!
    [timestamp] => 2025-10-03 14:23:45
    [random] => 5847
)

────────────────────────────────────────────────────────────────────────────────
⏰ 14:23:45 📝 User Object 📍 test-dump.php:49
────────────────────────────────────────────────────────────────────────────────
Array
(
    [id] => 123
    [name] => Test User
    [email] => test@example.com
    [roles] => Array
        (
            [0] => admin
            [1] => editor
        )
)
```

🎉 **Работает!**

---

## 💻 Использование в приложении

### В контроллерах

```php
<?php

namespace App\Controllers;

use Core\Response;

class UserController extends Controller
{
    public function show(int $id): Response
    {
        $user = User::find($id);
        
        // Отправляем на dump server (не влияет на вывод)
        server_dump($user, 'User Data');
        
        $permissions = $this->getPermissions($id);
        server_dump($permissions, 'User Permissions');
        
        // Страница работает нормально!
        return $this->view('user.show', compact('user'));
    }
}
```

### В middleware

```php
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): void
    {
        $token = $request->header('Authorization');
        
        // Debug токена без вывода на страницу
        server_dump($token, 'Auth Token');
        
        if (!$this->validateToken($token)) {
            throw new UnauthorizedException();
        }
    }
}
```

### В моделях

```php
class User extends Model
{
    public function save(): bool
    {
        // Debug перед сохранением
        server_dump($this->attributes, 'User attributes before save');
        
        $result = parent::save();
        
        if ($result) {
            server_dump($this->id, 'Saved user ID');
        }
        
        return $result;
    }
}
```

---

## 📚 API Функции

### `server_dump($data, $label = null)`

Отправить данные на dump server.

```php
server_dump($user);              // Без метки
server_dump($user, 'User Data'); // С меткой
server_dump(['key' => 'value']); // Любые данные
```

**Возвращает:** `bool` - успешно ли отправлено

---

### `dd_server($data, $label = null)`

Отправить на dump server и **остановить выполнение**.

```php
dd_server($user, 'Debug and Die');
// Код ниже не выполнится
```

**Возвращает:** `never` - завершает скрипт

---

### `dump_server_available()`

Проверить доступность dump server.

```php
if (dump_server_available()) {
    server_dump($data);
} else {
    // Fallback на обычный dump
    dump($data);
}
```

**Возвращает:** `bool`

---

## ⚙️ Конфигурация

### Изменить хост и порт

```bash
php vilnius dump-server --host=0.0.0.0 --port=9913
```

### В коде приложения

```php
use Core\DumpClient;

// Настроить клиент
DumpClient::configure('127.0.0.1', 9913);

// Отключить отправку
DumpClient::enable(false);

// Включить обратно
DumpClient::enable(true);
```

---

## 🎯 Сценарии использования

### 1. API Debugging

```php
public function apiLogin(Request $request): Response
{
    $credentials = $request->only(['email', 'password']);
    
    // Debug без влияния на JSON response
    server_dump($credentials, 'Login Attempt');
    
    $user = Auth::attempt($credentials);
    server_dump($user, 'Authenticated User');
    
    return Response::json([
        'token' => $user->generateToken()
    ]);
}
```

### 2. Database Query Debugging

```php
$users = DB::table('users')
    ->where('active', true)
    ->get();

// Смотрим результат в dump server
server_dump($users, 'Active Users Query');
```

### 3. Event Debugging

```php
class OrderCreated implements EventInterface
{
    public function handle(): void
    {
        server_dump($this->order, 'New Order');
        
        // Отправка email
        Mail::send(...);
        
        server_dump('Email sent', 'Order Email');
    }
}
```

---

## 🆚 server_dump() vs dd()

| Функция | Вывод | Останавливает? | Влияет на HTML? |
|---------|-------|----------------|-----------------|
| `dump()` | На странице | ❌ | ✅ Да |
| `dd()` | На странице | ✅ Да | ✅ Да |
| `server_dump()` | В dump server | ❌ | ❌ Нет |
| `dd_server()` | В dump server | ✅ Да | ❌ Нет |

---

## 🔧 Troubleshooting

### Ничего не приходит на сервер

**Проверьте:**

1. **Сервер запущен?**
   ```bash
   php vilnius dump-server
   ```

2. **Порт свободен?**
   ```bash
   # Windows
   netstat -ano | findstr :9912
   
   # Linux/Mac
   lsof -i :9912
   ```

3. **Debug режим включён?**
   ```env
   # .env
   APP_DEBUG=true
   APP_ENV=development
   ```

4. **Проверка доступности:**
   ```php
   if (dump_server_available()) {
       echo "✅ Server available";
   } else {
       echo "❌ Server not available";
   }
   ```

---

### Ошибка "Address already in use"

Порт 9912 занят. Используйте другой:

```bash
php vilnius dump-server --port=9913
```

И в коде:
```php
DumpClient::configure('127.0.0.1', 9913);
```

---

### Работает только локально

Для удалённого доступа:

```bash
# Слушать на всех интерфейсах
php vilnius dump-server --host=0.0.0.0 --port=9912
```

**⚠️ Внимание:** Открывает доступ из сети! Используйте только в dev.

---

## 💡 Pro Tips

### 1. Условный вывод

```php
if (config('app.debug')) {
    server_dump($data, 'Debug Data');
}
```

### 2. Временные метки

```php
server_dump($data, date('H:i:s') . ' - User Action');
```

### 3. Цепочка вызовов

```php
$result = SomeClass::method()
    ->tap(fn($r) => server_dump($r, 'After method'))
    ->anotherMethod()
    ->tap(fn($r) => server_dump($r, 'After another'))
    ->get();
```

### 4. В production

```php
// Автоматически отключается если APP_DEBUG=false
server_dump($data); // Ничего не сделает в production
```

---

## 🎨 Формат вывода

Dump Server показывает:

```
────────────────────────────────────────────────────────────────────────────────
⏰ 14:23:45           ← Время
📝 Label              ← Ваша метка (опционально)
📍 file.php:42        ← Файл и строка
────────────────────────────────────────────────────────────────────────────────
[Данные в читаемом формате]

```

---

## 🚀 Рабочий процесс

### Terminal 1: Dump Server
```bash
cd C:\OSPanel\home\torrentpier\public
php vilnius dump-server
# Оставить запущенным
```

### Terminal 2: Dev Server
```bash
cd C:\OSPanel\home\torrentpier\public
php -S localhost:8000 -t public
# Или OSPanel Apache
```

### Terminal 3: Commands
```bash
cd C:\OSPanel\home\torrentpier\public
php vilnius migrate
php vilnius route:list
# и т.д.
```

### Browser
```
http://localhost:8000
```

**Все dumps появятся в Terminal 1! 🎉**

---

## 📊 Сравнение с аналогами

| Инструмент | Vilnius Dump Server | Symfony VarDumper Server | XDebug |
|------------|---------------------|--------------------------|--------|
| Установка | ✅ Встроено | Нужен Composer | Нужен PHP extension |
| Вес | 🪶 Легкий | 📦 Средний | 🏋️ Тяжёлый |
| Настройка | 🚀 Мгновенная | ⚙️ Требует настройки | 🔧 Сложная |
| CLI команда | ✅ `php vilnius dump-server` | `php vendor/bin/var-dump-server` | N/A |

---

## ✅ Итого

Dump Server — это:
- ✅ **Отдельное окно** для debug-вывода
- ✅ **Не влияет** на HTML/JSON
- ✅ **Real-time** просмотр
- ✅ **Легковесный** и быстрый
- ✅ **Простой** в использовании

**Использование:**
1. Запустите: `php vilnius dump-server`
2. В коде: `server_dump($data, 'Label')`
3. Смотрите результат в консоли сервера!

---

**Happy Debugging! 🐛✨**

