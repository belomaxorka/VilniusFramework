# Helper Functions Documentation

## ⚡ Философия: Минимализм

Фреймворк предоставляет только **критически важные** helper-функции, которые используются повсеместно.

## 📦 Доступные хелперы

Все хелперы загружаются автоматически при старте приложения через `core/bootstrap.php`.

---

### config()

Получить значение из конфигурации.

**Сигнатура:**
```php
config(string $key, mixed $default = null): mixed
```

**Примеры:**
```php
$dbHost = config('database.host', 'localhost');
$appName = config('app.name');
$debug = config('app.debug', false);
```

**Местоположение:** `core/helpers/app/config.php`

---

### env()

Получить переменную окружения из `.env` файла.

**Сигнатура:**
```php
env(string $key, mixed $default = null): mixed
```

**Примеры:**
```php
$apiKey = env('API_KEY');
$dbPassword = env('DB_PASSWORD', '');
$appEnv = env('APP_ENV', 'production');
```

**Местоположение:** `core/helpers/app/env.php`

---

### __()

Получить переведенную строку (локализация).

**Сигнатура:**
```php
__(string $key, array $params = []): string
```

**Примеры:**
```php
echo __('welcome.message');
echo __('welcome.greeting', ['name' => 'John']);
echo __('validation.required', ['field' => 'email']);
```

**Местоположение:** `core/helpers/app/lang.php`

---

### vite()

Подключить Vite assets (CSS и JavaScript).

**Сигнатура:**
```php
vite(string $entry = 'app'): string
```

**Примеры:**
```php
<!-- В Twig шаблоне -->
{! vite('app') !}

<!-- В PHP -->
<?= vite('app') ?>
<?= vite('admin') ?>
```

**Возвращает HTML:**
```html
<!-- Development режим -->
<script type="module" src="http://localhost:5173/@vite/client"></script>
<script type="module" src="http://localhost:5173/resources/js/app.js"></script>

<!-- Production режим -->
<link rel="stylesheet" href="/build/assets/app-[hash].css">
<script type="module" src="/build/assets/app-[hash].js"></script>
```

**Местоположение:** `core/helpers/app/vite.php`

---

## 🗑️ Удаленные хелперы

Большинство helper-функций было удалено в пользу прямого использования классов.

См. полный список миграции: [DeprecatedHelpers.md](DeprecatedHelpers.md)

### Почему удалены?

1. **Явность** - использование классов напрямую делает код более понятным
2. **IDE поддержка** - лучше работает автодополнение и навигация
3. **Типизация** - статический анализ работает лучше
4. **Производительность** - меньше overhead на загрузку файлов
5. **Меньше магии** - проще понять что происходит в коде

### Вместо хелперов используйте:

**HTTP & Responses:**
- `$this->json()`, `$this->view()`, `$this->redirect()` в контроллерах
- `Request::getInstance()`, `Response::make()` напрямую

**Cache:**
- `Cache::get()`, `Cache::remember()`, `Cache::forget()`

**Debug:**
- `Debug::dump()`, `Debug::dd()`, `Logger::info()`

**И так далее...**

Полный список см. в [DeprecatedHelpers.md](DeprecatedHelpers.md)

---

## 📁 Структура

```
core/helpers/
└── app/              # Критические хелперы приложения
    ├── config.php    # config()
    ├── env.php       # env()
    ├── lang.php      # __()
    └── vite.php      # vite()
```

---

## 🎯 Когда создавать свои хелперы?

**Создавайте хелпер только если:**
1. Функция используется в **десятках мест** по всему приложению
2. Функция **критически важна** для работы приложения
3. Функция **простая** и не имеет сложной логики
4. Альтернатива через класс **значительно** длиннее и неудобнее

**Примеры плохих кандидатов:**
- ❌ Функции, используемые в 2-3 местах
- ❌ Обертки над методами классов (просто используйте класс)
- ❌ Функции со сложной логикой (создайте класс)
- ❌ Функции специфичные для одного модуля (создайте сервис)

**Примеры хороших кандидатов:**
- ✅ `config()` - используется везде, простая, критичная
- ✅ `__()` - локализация нужна в шаблонах и контроллерах
- ✅ `env()` - доступ к .env нужен часто
- ✅ `vite()` - упрощает подключение assets в шаблонах

---

## 🔧 Как добавить свой хелпер?

Если вы уверены, что хелпер действительно нужен:

1. Создайте файл в `core/helpers/app/your_helper.php`
2. Используйте `if (!function_exists())` для избежания конфликтов
3. Добавьте PHPDoc с типами
4. Обновите эту документацию

**Пример:**
```php
<?php declare(strict_types=1);

if (!function_exists('my_helper')) {
    /**
     * Description of what it does
     *
     * @param string $param Parameter description
     * @return mixed
     */
    function my_helper(string $param): mixed
    {
        // Implementation
    }
}
```

---

## 🚀 Рекомендации

1. **Используйте классы напрямую** когда это возможно
2. **Используйте методы Controller** для HTTP операций
3. **Создавайте Service классы** для сложной бизнес-логики
4. **Оставьте хелперы** только для самых частых и простых операций

**Хороший код - явный код! 🎯**
