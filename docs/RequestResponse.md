# Request & Response System

Система работы с HTTP запросами и ответами в объектно-ориентированном стиле.

## Содержание

- [Request](#request)
- [Response](#response)
- [BaseController](#basecontroller)
- [Helper функции](#helper-функции)
- [Примеры использования](#примеры-использования)

---

## Request

Класс `Core\Request` предоставляет ООП обертку над HTTP запросом.

### Основные методы

#### Получение экземпляра

```php
use Core\Request;

// Получить глобальный экземпляр
$request = Request::getInstance();

// Или через helper
$request = request();
```

#### Методы запроса

```php
// Получить HTTP метод
$method = $request->method(); // GET, POST, PUT, PATCH, DELETE, etc.

// Проверить метод
if ($request->isMethod('POST')) {
    // ...
}
```

#### Получение данных

```php
// Получить значение из запроса (GET или POST)
$name = $request->input('name');
$name = $request->input('name', 'default'); // С дефолтным значением

// Получить все данные
$all = $request->all();

// Получить только указанные ключи
$data = $request->only(['name', 'email']);

// Получить все кроме указанных
$data = $request->except(['password']);

// Magic get
$name = $request->name;
```

#### Проверка наличия параметров

```php
// Проверить наличие параметра
if ($request->has('email')) {
    // ...
}

// Проверить наличие всех параметров
if ($request->hasAll(['name', 'email', 'password'])) {
    // ...
}

// Проверить наличие хотя бы одного параметра
if ($request->hasAny(['email', 'username'])) {
    // ...
}

// Проверить, что параметр заполнен (не пустой)
if ($request->filled('name')) {
    // ...
}
```

#### Работа с Query и POST данными

```php
// Query параметры (?name=value)
$name = $request->query('name');
$allQuery = $request->query(); // Все query параметры

// POST данные
$email = $request->post('email');
$allPost = $request->post(); // Все POST данные

// JSON данные
$data = $request->json(); // Все JSON данные
$name = $request->json('name'); // Конкретное поле
```

#### Заголовки

```php
// Получить заголовок
$contentType = $request->header('Content-Type');

// Получить все заголовки
$headers = $request->headers();

// Bearer токен
$token = $request->bearerToken();
```

#### Информация о клиенте

```php
// IP адрес
$ip = $request->ip();

// User Agent
$userAgent = $request->userAgent();

// Referer
$referer = $request->referer();
```

#### URL информация

```php
// Текущий URI
$uri = $request->uri();

// Полный URL
$url = $request->url();

// Путь
$path = $request->path();

// Схема (http/https)
$scheme = $request->scheme();

// Хост
$host = $request->host();

// Порт
$port = $request->port();
```

#### Проверки типа запроса

```php
// JSON запрос
if ($request->isJson()) {
    // Content-Type: application/json
}

// Принимает JSON
if ($request->acceptsJson()) {
    // Accept: application/json
}

// Хочет JSON (либо isJson, либо prefers json)
if ($request->wantsJson()) {
    // ...
}

// AJAX запрос
if ($request->isAjax()) {
    // X-Requested-With: XMLHttpRequest
}

// HTTPS
if ($request->isSecure()) {
    // ...
}

// Мобильное устройство
if ($request->isMobile()) {
    // ...
}

// Бот
if ($request->isBot()) {
    // ...
}
```

#### Работа с файлами

```php
// Получить файл
$file = $request->file('avatar');

// Все файлы
$files = $request->files();

// Проверить наличие файла
if ($request->hasFile('avatar')) {
    // ...
}
```

#### Cookies

```php
// Получить cookie
$value = $request->cookie('name');

// Все cookies
$cookies = $request->cookies();
```

---

## Response

Класс `Core\Response` управляет HTTP ответами.

### Создание Response

```php
use Core\Response;

// Создать новый экземпляр
$response = new Response();

// Через статический метод
$response = Response::make('Hello World', 200, ['X-Custom' => 'Value']);
```

### Типы ответов

#### JSON

```php
// JSON ответ
$response = $response->json(['message' => 'Success'], 200);

// Через helper
return json(['message' => 'Success']);

// Статический метод
return Response::jsonResponse(['data' => $data], 200);
```

#### HTML

```php
// HTML ответ
$response = $response->html('<h1>Hello</h1>', 200);

// Статический метод
return Response::htmlResponse('<h1>Hello</h1>');
```

#### Plain Text

```php
$response = $response->text('Plain text response');
```

#### XML

```php
$response = $response->xml('<?xml version="1.0"?><root></root>');
```

#### View

```php
// Рендер шаблона
$response = $response->view('welcome.tpl', ['name' => 'John']);
```

### Редиректы

```php
// Простой редирект
$response = $response->redirect('/home');

// Статический метод
return Response::redirectTo('/home');

// Редирект с другим статусом
$response = $response->redirect('/home', 301); // Permanent redirect

// Редирект назад
$response = $response->back();

// Редирект на именованный роут
$response = $response->route('user.profile', ['id' => 123]);
```

### Работа с файлами

```php
// Download файла
$response = $response->download('/path/to/file.pdf', 'document.pdf');

// Статический метод
return Response::downloadFile('/path/to/file.pdf');

// Stream файла (inline)
$response = $response->file('/path/to/image.jpg');
```

### Статус коды

```php
// Установить статус
$response->status(404);

// Предопределенные константы
$response->status(Response::HTTP_OK); // 200
$response->status(Response::HTTP_CREATED); // 201
$response->status(Response::HTTP_NO_CONTENT); // 204
$response->status(Response::HTTP_NOT_FOUND); // 404
$response->status(Response::HTTP_UNAUTHORIZED); // 401
$response->status(Response::HTTP_FORBIDDEN); // 403
$response->status(Response::HTTP_UNPROCESSABLE_ENTITY); // 422
$response->status(Response::HTTP_TOO_MANY_REQUESTS); // 429
$response->status(Response::HTTP_INTERNAL_SERVER_ERROR); // 500

// No content (204)
$response = $response->noContent();
```

### Заголовки

```php
// Один заголовок
$response->header('X-Custom', 'Value');

// Несколько заголовков
$response->withHeaders([
    'X-API-Version' => '1.0',
    'X-Rate-Limit' => '100',
]);

// Fluent interface
$response
    ->status(200)
    ->header('X-Custom', 'Value')
    ->json(['data' => $data]);
```

### Cookies

```php
// Установить cookie
$response->cookie('name', 'value', time() + 3600);

// С параметрами
$response->cookie(
    name: 'session',
    value: 'abc123',
    expires: time() + 3600,
    path: '/',
    domain: '',
    secure: true,
    httponly: true,
    samesite: 'Strict'
);

// Удалить cookie
$response->withoutCookie('name');
```

### Отправка ответа

```php
// Отправить ответ клиенту
$response->send();

// В роутере и контроллерах это происходит автоматически
```

---

## BaseController

Базовый класс `App\Controllers\Controller` для всех контроллеров.

### Использование

```php
namespace App\Controllers;

use Core\Response;

class MyController extends Controller
{
    public function index(): Response
    {
        // $this->request - доступен автоматически
        // $this->response - доступен автоматически
        
        return $this->view('home');
    }
}
```

### Методы базового контроллера

#### JSON ответы

```php
// Простой JSON
return $this->json(['data' => $data]);

// Success response
return $this->success('Operation successful', $data);

// Error response
return $this->error('Something went wrong', 400, ['field' => 'error']);
```

#### HTML и Views

```php
// HTML
return $this->html('<h1>Hello</h1>');

// View
return $this->view('welcome.tpl', ['name' => 'John']);
```

#### Редиректы

```php
// Простой редирект
return $this->redirect('/home');

// Назад
return $this->back();

// На именованный роут
return $this->redirectRoute('user.profile', ['id' => 123]);
```

#### Готовые ответы для распространенных случаев

```php
// Not Found (404)
return $this->notFound('User not found');

// Unauthorized (401)
return $this->unauthorized('Login required');

// Forbidden (403)
return $this->forbidden('Access denied');

// Created (201)
return $this->created($newUser, 'User created successfully');

// No Content (204)
return $this->noContent();

// Download
return $this->download('/path/to/file.pdf');
```

---

## Helper функции

### request()

```php
// Получить Request
$request = request();

// Получить значение
$name = request('name');
$name = request('name', 'default');
```

### response()

```php
// Создать Response
$response = response('Hello World');
$response = response('Hello', 200, ['X-Custom' => 'Value']);
```

### json()

```php
// Создать JSON response
return json(['message' => 'Success']);
return json($data, 201);
```

### redirect()

```php
// Редирект
return redirect('/home');
return redirect('/home', 301);
```

### back()

```php
// Редирект назад
return back();
```

### abort()

```php
// Прервать с 404
abort(404);

// С сообщением
abort(403, 'Access denied');

// Будет автоматически JSON для AJAX запросов
```

### abort_if() / abort_unless()

```php
// Прервать если условие истинно
abort_if($user === null, 404, 'User not found');

// Прервать если условие ложно
abort_unless($user->isAdmin(), 403, 'Admin access required');
```

---

## Примеры использования

### Простой контроллер

```php
namespace App\Controllers;

use Core\Response;

class UserController extends Controller
{
    public function show(int $id): Response
    {
        $user = $this->findUser($id);
        
        if (!$user) {
            return $this->notFound('User not found');
        }
        
        if ($this->request->wantsJson()) {
            return $this->json($user);
        }
        
        return $this->view('users/show', ['user' => $user]);
    }
    
    public function store(): Response
    {
        $data = $this->request->only(['name', 'email', 'password']);
        
        // Валидация
        if (!$this->request->filled('email')) {
            return $this->error('Email is required', 400);
        }
        
        // Создание пользователя
        $user = $this->createUser($data);
        
        return $this->created($user, 'User created successfully');
    }
    
    public function update(int $id): Response
    {
        $user = $this->findUser($id);
        
        abort_if(!$user, 404, 'User not found');
        
        $data = $this->request->only(['name', 'email']);
        $this->updateUser($user, $data);
        
        return $this->success('User updated', $user);
    }
    
    public function delete(int $id): Response
    {
        $user = $this->findUser($id);
        
        abort_unless($user, 404);
        
        $this->deleteUser($user);
        
        return $this->noContent();
    }
}
```

### API контроллер

```php
namespace App\Controllers\Api;

use Core\Response;
use App\Controllers\Controller;

class PostController extends Controller
{
    public function index(): Response
    {
        $posts = $this->getPosts();
        
        return $this->json([
            'data' => $posts,
            'meta' => [
                'total' => count($posts),
            ],
        ]);
    }
    
    public function store(): Response
    {
        // Получаем JSON данные
        $title = $this->request->json('title');
        $content = $this->request->json('content');
        
        $post = $this->createPost($title, $content);
        
        return $this->created($post, 'Post created');
    }
}
```

### Работа с файлами

```php
namespace App\Controllers;

use Core\Response;

class FileController extends Controller
{
    public function upload(): Response
    {
        if (!$this->request->hasFile('document')) {
            return $this->error('No file uploaded', 400);
        }
        
        $file = $this->request->file('document');
        
        // Сохранение файла
        $path = $this->saveFile($file);
        
        return $this->success('File uploaded', ['path' => $path]);
    }
    
    public function download(int $id): Response
    {
        $file = $this->findFile($id);
        
        abort_unless($file, 404);
        
        return $this->download($file->path, $file->name);
    }
}
```

### Условные ответы

```php
public function data(): Response
{
    $data = $this->getData();
    
    // Автоматически JSON для API, HTML для браузера
    if ($this->request->wantsJson()) {
        return $this->json($data);
    }
    
    return $this->view('data/index', ['data' => $data]);
}
```

### Работа с headers

```php
public function apiEndpoint(): Response
{
    // Проверка Bearer токена
    $token = $this->request->bearerToken();
    
    abort_unless($token, 401, 'Token required');
    
    $data = $this->getProtectedData();
    
    return $this->json($data)
        ->withHeaders([
            'X-API-Version' => '1.0',
            'X-Rate-Limit-Remaining' => '99',
        ]);
}
```

---

## Интеграция с Router

Router автоматически обрабатывает Response объекты:

```php
// routes/web.php

$router->get('/users/{id:\d+}', [UserController::class, 'show']);

$router->post('/users', [UserController::class, 'store']);

$router->get('/download/{id:\d+}', [FileController::class, 'download']);
```

Если контроллер возвращает `Response`, он автоматически отправляется клиенту.

---

## Миграция со старого кода

### Было

```php
public function show(): void
{
    $data = ['user' => $user];
    
    echo json_encode($data);
    header('Content-Type: application/json');
    http_response_code(200);
}
```

### Стало

```php
public function show(): Response
{
    return $this->json(['user' => $user]);
}
```

---

## Best Practices

1. **Всегда возвращайте Response** из методов контроллеров
2. **Используйте type hints** для методов контроллеров
3. **Используйте базовый Controller** для всех контроллеров
4. **Используйте готовые методы** (`success`, `error`, `notFound` и т.д.)
5. **Используйте helpers** для краткости кода
6. **Проверяйте тип запроса** (`wantsJson()`) для универсальных эндпоинтов
7. **Используйте abort()** для быстрого прерывания с ошибкой

---

## Дополнительно

- Request и Response объекты полностью интегрированы с роутером
- Middleware может работать с Response объектами
- Автоматическая обработка JSON запросов
- Поддержка всех HTTP методов и статусов

