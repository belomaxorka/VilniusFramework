# 🐛 Dump Server - Правильное использование

## ⚠️ Частые ошибки

### ❌ НЕПРАВИЛЬНО:

```php
// Передаёте СТРОКУ вместо переменной!
$user = ['id' => 1, 'name' => 'John'];
server_dump("$user", "User Data");        // ❌ Выведет строку "Array"
server_dump('$user', "User Data");        // ❌ Выведет строку "$user"
server_dump("Test", "Debug");             // ❌ Выведет строку "Test"
```

### ✅ ПРАВИЛЬНО:

```php
// Передаёте ПЕРЕМЕННУЮ!
$user = ['id' => 1, 'name' => 'John'];
server_dump($user, "User Data");          // ✅ Покажет весь массив
server_dump($user['name'], "User Name");  // ✅ Покажет "John"

$test = "Some value";
server_dump($test, "Debug");              // ✅ Покажет "Some value"
```

---

## 🎯 Правильные примеры

### В контроллере

```php
<?php

namespace App\Controllers;

use Core\Response;

class UserController extends Controller
{
    public function show(int $id): Response
    {
        // Получаем данные
        $user = [
            'id' => $id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'roles' => ['admin', 'editor']
        ];
        
        // ✅ ПРАВИЛЬНО - передаём переменную
        server_dump($user, 'User from database');
        
        // ✅ ПРАВИЛЬНО - передаём часть данных
        server_dump($user['roles'], 'User roles');
        
        // ✅ ПРАВИЛЬНО - передаём ID
        server_dump($id, 'User ID');
        
        return $this->view('user.show', compact('user'));
    }
    
    public function update(Request $request, int $id): Response
    {
        // Получаем данные из запроса
        $data = $request->all();
        
        // ✅ Debug входящих данных
        server_dump($data, 'Request data');
        
        // Валидация
        $validated = $this->validate($data);
        
        // ✅ Debug после валидации
        server_dump($validated, 'Validated data');
        
        return Response::json(['success' => true]);
    }
}
```

---

## 📊 Что будет в Dump Server

### Теперь вы увидите:

```
────────────────────────────────────────────────────────────────────────────────
⏰ 14:23:45 📝 User from database 📍 app/Controllers/UserController.php:18
────────────────────────────────────────────────────────────────────────────────
🔍 Type: array
────────────────────────────────────────────────────────────────────────────────
Array
(
    [id] => 123
    [name] => John Doe
    [email] => john@example.com
    [roles] => Array
        (
            [0] => admin
            [1] => editor
        )
)
```

**Обратите внимание:**
- ✅ Правильный путь: `app/Controllers/UserController.php:18`
- ✅ Показывает тип данных: `Type: array`
- ✅ Красиво форматирует массив
- ✅ Данные приходят сразу (с `flush()`)

---

## 🧪 Протестируйте сейчас!

### Шаг 1: Dump Server должен быть запущен

```bash
php vilnius dump-server
```

### Шаг 2: Запустите тестовый скрипт

```bash
php test-dump-correct.php
```

### Шаг 3: Смотрите результат

В окне Dump Server вы увидите:
- ❌ Пример неправильного использования (строка "$user")
- ✅ Правильное использование (массивы, объекты)
- ✅ Разные типы данных
- ✅ Вложенные структуры
- ✅ Правильные пути к файлам

---

## 🔧 Что исправили

### 1. Backtrace теперь правильный

**Было:**
```
📍 server.php:17  ❌ (helper файл)
```

**Стало:**
```
📍 app/Controllers/UserController.php:25  ✅ (реальный файл)
```

**Как работает:**
- Пропускаем `DumpClient.php`
- Пропускаем `helpers/debug/server.php`
- Находим первый реальный вызов

### 2. Добавили тип данных

Теперь видно что именно пришло:
```
🔍 Type: array
🔍 Type: string  
🔍 Type: integer
🔍 Type: object
```

### 3. Принудительный flush

Данные теперь приходят **немедленно**, без задержки.

---

## 💡 Pro Tips

### Дебаг API

```php
public function api(Request $request): Response
{
    $input = $request->json();
    server_dump($input, 'API Input');
    
    $result = $this->processData($input);
    server_dump($result, 'API Result');
    
    // JSON response не затронут!
    return Response::json($result);
}
```

### Дебаг цепочки вызовов

```php
$data = $this->getData()
    ->tap(fn($d) => server_dump($d, 'After getData'))
    ->transform()
    ->tap(fn($d) => server_dump($d, 'After transform'))
    ->filter()
    ->tap(fn($d) => server_dump($d, 'After filter'))
    ->get();
```

### Условный дебаг

```php
if ($userId === 123) {
    server_dump($user, 'Debug user 123');
}

// Или
server_dump($user, "User {$user['id']}");
```

### Дебаг в цикле

```php
foreach ($users as $index => $user) {
    server_dump($user, "User #{$index}");
}
```

---

## 🎓 Запомните!

### ✅ ДА:
```php
server_dump($variable, 'Label');
server_dump($array['key'], 'Label');
server_dump($object->property, 'Label');
server_dump($this->method(), 'Label');
```

### ❌ НЕТ:
```php
server_dump("$variable", 'Label');    // Интерполяция строки
server_dump('$variable', 'Label');    // Строковый литерал
server_dump("text", 'Label');         // Если хотите массив/объект
```

---

## 🚀 Полный рабочий пример

```php
<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;

class PostController extends Controller
{
    public function store(Request $request): Response
    {
        // 1. Смотрим что пришло
        server_dump($request->all(), 'Raw request');
        
        // 2. Валидация
        $validated = $request->validate([
            'title' => 'required|min:3',
            'content' => 'required',
        ]);
        server_dump($validated, 'Validated data');
        
        // 3. Создание поста
        $post = [
            'id' => rand(1, 1000),
            'title' => $validated['title'],
            'content' => $validated['content'],
            'created_at' => date('Y-m-d H:i:s'),
        ];
        server_dump($post, 'Created post');
        
        // 4. Сохранение
        // Post::create($post);
        
        return Response::json($post);
    }
}
```

**В Dump Server увидите:**
1. Raw request - все данные формы
2. Validated data - после валидации
3. Created post - финальный объект

**Всё это БЕЗ влияния на JSON response!** 🎉

---

**Happy Debugging! 🐛✨**

