# Template Engine

Шаблонизатор TorrentPier предоставляет простой и мощный способ создания динамических HTML-страниц с Twig-подобным синтаксисом.

## Основные возможности

- **Twig-подобный синтаксис** - знакомый и удобный синтаксис
- **Кэширование шаблонов** - автоматическое кэширование для повышения производительности
- **Безопасность** - автоматическое экранирование HTML
- **Расширяемость** - поддержка включений и наследования шаблонов
- **Простота использования** - глобальные функции-хелперы

## Установка и настройка

Шаблонизатор автоматически инициализируется при запуске приложения через `Core::init()`.

### Конфигурация

По умолчанию используются следующие пути:
- **Шаблоны**: `resources/views/`
- **Кэш**: `storage/cache/templates/`

## Синтаксис

### Переменные

```twig
<!-- Экранированная переменная -->
{{ title }}

<!-- Неэкранированная переменная (для HTML) -->
{! html_content !}
```

### Условия

```twig
{% if condition %}
    Контент отображается если condition истинно
{% elseif another_condition %}
    Альтернативный контент
{% else %}
    Контент по умолчанию
{% endif %}
```

### Циклы

```twig
<!-- Цикл foreach -->
{% for item in items %}
    {{ item }}
{% endfor %}

<!-- Цикл while -->
{% while condition %}
    Контент
{% endwhile %}
```

### Функции

```twig
<!-- Вызов функции без аргументов -->
{! vite() !}

<!-- Вызов функции с аргументами -->
{! vite("app") !}

<!-- Вызов функции с переменной -->
{! greet(username) !}

<!-- Вызов функции с несколькими аргументами -->
{! format_date(date, "Y-m-d") !}

<!-- Вложенные вызовы функций -->
{! upper(greet(username)) !}

<!-- Функции в условиях -->
{% if is_admin() %}
    Admin Panel
{% endif %}
```

### Включения

```twig
<!-- Включение другого шаблона -->
{% include 'header.twig' %}
```

### Наследование

```twig
<!-- Расширение базового шаблона -->
{% extends 'base.twig' %}

{% block content %}
    Содержимое блока
{% endblock %}
```

## Использование

### Через класс

```php
use Core\TemplateEngine;

$template = TemplateEngine::getInstance();

// Установка переменных
$template->assign('title', 'Мой сайт');
$template->assignMultiple([
    'name' => 'Иван',
    'age' => 25
]);

// Рендеринг
$html = $template->render('welcome.twig', ['message' => 'Привет!']);

// Или вывод напрямую
$template->display('welcome.twig', ['message' => 'Привет!']);
```

### Через глобальные функции

```php
// Рендеринг шаблона
$html = view('welcome.twig', ['title' => 'Мой сайт']);

// Вывод шаблона
display('welcome.twig', ['title' => 'Мой сайт']);

// Получение экземпляра шаблонизатора
$template = template();
```

### В контроллере

```php
class HomeController
{
    public function index(): void
    {
        $data = [
            'title' => 'Главная страница',
            'users' => [
                ['name' => 'Иван', 'email' => 'ivan@example.com'],
                ['name' => 'Мария', 'email' => 'maria@example.com']
            ]
        ];
        
        display('home.twig', $data);
    }
}
```

## Функции

### Регистрация функций

Вы можете регистрировать свои функции для использования в шаблонах:

```php
$template = TemplateEngine::getInstance();

// Регистрация простой функции
$template->addFunction('hello', function() {
    return 'Hello, World!';
});

// Регистрация функции с аргументами
$template->addFunction('greet', function($name) {
    return "Hello, {$name}!";
});

// Регистрация функции с несколькими аргументами
$template->addFunction('format_price', function($price, $currency = 'USD') {
    return number_format($price, 2) . ' ' . $currency;
});
```

### Использование функций в шаблонах

```twig
{! hello() !}
{! greet("John") !}
{! greet(username) !}
{! format_price(100.5) !}
{! format_price(price, "EUR") !}
```

### Встроенные функции

Шаблонизатор автоматически регистрирует следующие функции (если они определены в вашем проекте):

- `vite(entry)` - генерация тегов для Vite assets
- `vite_asset(entry, type)` - получение URL Vite asset  
- `asset(path)` - получение URL для статического ресурса
- `url(path)` - генерация URL
- `route(name, params)` - генерация URL по имени маршрута
- `csrf_token()` - получение CSRF токена
- `csrf_field()` - генерация скрытого поля с CSRF токеном
- `old(key, default)` - получение старого значения формы
- `config(key, default)` - получение значения конфигурации
- `env(key, default)` - получение переменной окружения
- `trans(key, params)` - перевод строки

### Проверка существования функции

```php
if ($template->hasFunction('vite')) {
    // Функция зарегистрирована
}
```

## Кэширование

### Настройка кэширования

```php
$template = TemplateEngine::getInstance();

// Включить/выключить кэширование
$template->setCacheEnabled(true);

// Установить время жизни кэша (в секундах)
$template->setCacheLifetime(3600); // 1 час
```

### Очистка кэша

```php
$template = TemplateEngine::getInstance();
$template->clearCache();
```

## Логирование неопределенных переменных

Шаблонизатор автоматически отслеживает использование неопределенных переменных и логирует их в production режиме.

### Как это работает

**В Development режиме:**
- Ошибки обрабатываются через красивый ErrorHandler
- Показывается детальная страница ошибки с stack trace
- Отображается информация о файле и строке
- Помогает быстро находить и исправлять проблемы

**В Production режиме:**
- Ошибки скрыты от пользователей (не ломают страницу)
- Автоматически логируются в `storage/logs/app.log`
- Логируются с информацией о доступных переменных

### Настройка логирования

```php
$template = TemplateEngine::getInstance();

// Включить логирование (по умолчанию)
$template->setLogUndefinedVars(true);

// Отключить логирование
$template->setLogUndefinedVars(false);
```

### Получение статистики

```php
// Получить список всех неопределенных переменных за сессию
$undefinedVars = TemplateEngine::getUndefinedVars();

foreach ($undefinedVars as $varName => $info) {
    echo "Variable: \${$varName}\n";
    echo "Count: {$info['count']}\n";
    echo "File: {$info['file']}:{$info['line']}\n";
}

// Очистить статистику
TemplateEngine::clearUndefinedVars();
```

### Пример лога

В production режиме в логе появится:

```
[2025-09-30 12:34:56] WARNING: Template undefined variable: $user_name
Message: Undefined variable $user_name
File: welcome.twig:15
Available variables: title, content, footer, config
```

### Рекомендации

1. **В Development:** Исправляйте все undefined variables сразу - вы увидите красивую страницу ошибки
2. **В Production:** Регулярно проверяйте логи на наличие таких ошибок
3. **Используйте статистику:** `getUndefinedVars()` для анализа проблемных мест
4. **Всегда передавайте переменные:** Убедитесь что все переменные из шаблона передаются из контроллера
5. **Не игнорируйте ошибки:** В development они выводятся специально, чтобы вы их исправили

## Примеры шаблонов

### Простой шаблон

```html
<!DOCTYPE html>
<html>
<head>
    <title>{{ title }}</title>
</head>
<body>
    <h1>{{ title }}</h1>
    <p>{{ message }}</p>
</body>
</html>
```

### Шаблон с условиями и циклами

```html
<!DOCTYPE html>
<html>
<head>
    <title>{{ title }}</title>
</head>
<body>
    <h1>{{ title }}</h1>
    
    {% if message %}
    <div class="message">{{ message }}</div>
    {% endif %}
    
    {% if users %}
    <ul>
        {% for user in users %}
        <li>{{ user.name }} - {{ user.email }}</li>
        {% endfor %}
    </ul>
    {% endif %}
</body>
</html>
```

### Базовый шаблон с наследованием

**base.twig:**
```html
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}Мой сайт{% endblock %}</title>
</head>
<body>
    <header>
        {% include 'header.twig' %}
    </header>
    
    <main>
        {% block content %}{% endblock %}
    </main>
    
    <footer>
        {% include 'footer.twig' %}
    </footer>
</body>
</html>
```

**page.twig:**
```html
{% extends 'base.twig' %}

{% block title %}{{ page_title }} - Мой сайт{% endblock %}

{% block content %}
<h1>{{ page_title }}</h1>
<p>{{ content }}</p>
{% endblock %}
```

## Производительность

- Шаблоны компилируются в PHP код
- Автоматическое кэширование скомпилированных шаблонов
- Проверка изменений исходных файлов
- Настраиваемое время жизни кэша

## Безопасность

- Автоматическое экранирование HTML в переменных `{{ }}`
- Возможность вывода неэкранированного контента через `{! !}`
- Защита от XSS атак

## Расширение функциональности

Шаблонизатор можно легко расширить, добавив новые теги и функции в метод `compileTemplate()` класса `TemplateEngine`.
