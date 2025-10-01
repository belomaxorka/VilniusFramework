# Request & Response - Quick Start

Быстрое руководство по работе с новой системой Request/Response.

## 🚀 Основы

### 1. Создайте контроллер

```php
namespace App\Controllers;

use Core\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return $this->json(['users' => []]);
    }
}
```

### 2. Зарегистрируйте роут

```php
// routes/web.php
$router->get('/users', [UserController::class, 'index']);
```

### 3. Готово! 🎉

---

## 📖 Основные паттерны

### JSON API

```php
public function show(int $id): Response
{
    $user = $this->findUser($id);
    
    if (!$user) {
        return $this->error('User not found', 404);
    }
    
    return $this->success('User found', $user);
}
```

### HTML View

```php
public function profile(): Response
{
    $user = $this->getCurrentUser();
    return $this->view('profile', ['user' => $user]);
}
```

### Работа с формами

```php
public function store(): Response
{
    // Получить данные
    $data = $this->request->only(['name', 'email']);
    
    // Валидация
    if (!$this->request->filled('email')) {
        return $this->error('Email required', 400);
    }
    
    // Создание
    $user = $this->createUser($data);
    
    return $this->created($user, 'User created');
}
```

### Редиректы

```php
public function update(int $id): Response
{
    // Обновление...
    
    return $this->redirectRoute('user.profile', ['id' => $id]);
}
```

### Download файлов

```php
public function downloadReport(): Response
{
    return $this->download('/path/to/report.pdf', 'report.pdf');
}
```

---

## 🔥 Helper функции

```php
// Получить Request
$request = request();
$name = request('name');

// JSON ответ
return json(['data' => $data]);

// Редирект
return redirect('/home');
return back();

// Прервать с ошибкой
abort(404);
abort_if($user === null, 404, 'User not found');
```

---

## 💡 Request методы

```php
// Данные
$this->request->input('name');
$this->request->all();
$this->request->only(['name', 'email']);
$this->request->except(['password']);

// Проверки
$this->request->has('email');
$this->request->filled('name');
$this->request->hasFile('avatar');

// Информация
$this->request->method();
$this->request->ip();
$this->request->userAgent();
$this->request->isJson();
$this->request->wantsJson();
```

---

## 🎯 Response методы

```php
// Базовые
$this->json($data);
$this->html($content);
$this->view($template, $data);

// Редиректы
$this->redirect($url);
$this->back();
$this->redirectRoute($name, $params);

// Готовые ответы
$this->success($message, $data);
$this->error($message, $status);
$this->notFound($message);
$this->unauthorized($message);
$this->forbidden($message);
$this->created($data, $message);
$this->noContent();

// Файлы
$this->download($path, $name);
```

---

## 📚 Полная документация

См. [RequestResponse.md](RequestResponse.md) для детального описания всех возможностей.

