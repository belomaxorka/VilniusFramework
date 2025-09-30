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

### Включения

```twig
<!-- Включение другого шаблона -->
{% include 'header.tpl' %}
```

### Наследование

```twig
<!-- Расширение базового шаблона -->
{% extends 'base.tpl' %}

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
$html = $template->render('welcome.tpl', ['message' => 'Привет!']);

// Или вывод напрямую
$template->display('welcome.tpl', ['message' => 'Привет!']);
```

### Через глобальные функции

```php
// Рендеринг шаблона
$html = view('welcome.tpl', ['title' => 'Мой сайт']);

// Вывод шаблона
display('welcome.tpl', ['title' => 'Мой сайт']);

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
        
        display('home.tpl', $data);
    }
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
File: welcome.tpl:15
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

**base.tpl:**
```html
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}Мой сайт{% endblock %}</title>
</head>
<body>
    <header>
        {% include 'header.tpl' %}
    </header>
    
    <main>
        {% block content %}{% endblock %}
    </main>
    
    <footer>
        {% include 'footer.tpl' %}
    </footer>
</body>
</html>
```

**page.tpl:**
```html
{% extends 'base.tpl' %}

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
