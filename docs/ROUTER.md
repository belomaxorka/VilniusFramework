# Router Documentation

Минималистичный роутер для MyFramework (PHP 8.1+).  
Поддерживает:

- GET и POST маршруты
- Динамические параметры `{param}`
- Регулярные ограничения `{param:regex}`
- Передачу параметров в контроллеры или замыкания

---

## 📌 Регистрация маршрутов

### Статические маршруты

```php
$router->get('', [\App\Controllers\HomeController::class, 'index']);
$router->get('about', fn() => print "About page");
```

### Динамические маршруты с параметрами

```php
$router->get('user/{id}', [UserController::class, 'show']);
```

> [!IMPORTANT]
> {id} — любой сегмент URI, кроме /

> [!NOTE]
> Передаётся в метод контроллера как аргумент $id

## ⚡ Маршруты с регулярными ограничениями

Синтаксис: {param:regex}

```php
$router->get('user/{id:\d+}', [UserController::class, 'show']); 
$router->get('post/{id:\d+}/{slug:[a-z\-]+}', [PostController::class, 'view']);

```

Примеры:
* `/user/42` → вызовет `UserController::show(42)` ✅
* `/user/abc` → 404 ❌
* `/post/123/hello-world` → вызовет `PostController::view(123, 'hello-world')` ✅
* `/post/123/HelloWorld` → 404 ❌

## 💡 Передача параметров в контроллеры

Контроллер:

```php
class UserController
{
    public function show($id): void
    {
        echo "User profile, id = " . htmlspecialchars($id);
    }
}

class PostController
{
    public function view($id, $slug): void
    {
        echo "Post $id — $slug";
    }
}
```

> [!TIP]
> Маршруты автоматически распознают параметры по имени и передают их как аргументы

## 🔧 Поддержка замыканий (closures)

```php
$router->get('hello/{name}', fn($name) => print "Hello, $name!");
```

Запрос `/hello/John` → вывод: Hello, John!

## 🛠️ Особенности

* Поддержка нескольких параметров
* Регулярные ограничения для валидации сегментов URI
* Поддержка полного namespace класса или короткого имени контроллера
* Возврат 404 для несоответствующих маршрутов

## 📂 Пример использования

```php
$router->get('', [HomeController::class, 'index']);
$router->get('about', fn() => print "About page");
$router->get('user/{id:\d+}', [UserController::class, 'show']);
$router->get('post/{id:\d+}/{slug:[a-z\-]+}', [PostController::class, 'view']);
```
