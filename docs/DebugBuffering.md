# Буферизация Debug Вывода

## Проблема

Ранее функции debug (`dump()`, `dump_pretty()`, `benchmark()`) использовали прямой `echo`, что приводило к следующим проблемам:

1. Вывод происходил **до** рендеринга шаблона страницы
2. Debug информация не встраивалась корректно в HTML
3. Невозможно было контролировать место вывода debug данных

## Решение

Теперь все debug функции используют **буферизацию вывода**:

- Debug данные сохраняются во внутренний буфер
- Вывод происходит автоматически в конце выполнения скрипта
- Можно вручную контролировать место вывода в шаблоне

## Автоматический вывод

По умолчанию debug данные выводятся **автоматически в конце страницы** через shutdown handler:

```php
// В контроллере
dump(['test' => 'data'], 'My Debug');
dump_pretty(['user' => 'John'], 'User Data');
benchmark(fn() => someHeavyOperation(), 'Heavy Operation');

// Debug данные автоматически выведутся в конце страницы
```

## Ручной вывод в шаблоне

Вы можете отключить автоматический вывод и самостоятельно выбрать место для debug информации:

```php
// В начале скрипта
\Core\Debug::setAutoDisplay(false);

// Используйте debug функции как обычно
dump($data, 'Some data');
```

В шаблоне вставьте вывод в нужное место:

```html
<div class="container">
    <!-- Ваш контент -->
</div>

<!-- Debug информация -->
<?php if (has_debug_output()): ?>
    <div class="debug-section">
        <h2>Debug Information</h2>
        <?= render_debug() ?>
    </div>
<?php endif; ?>
```

Или используя синтаксис шаблонизатора:

```twig
{% if has_debug_output() %}
<div class="debug-section">
    <h2>Debug Information</h2>
    {!! render_debug() !!}
</div>
{% endif %}
```

## Новые функции

### Управление буфером

```php
// Проверить наличие debug данных
if (has_debug_output()) {
    // есть данные
}

// Получить debug вывод как строку
$debugHtml = debug_output();

// Вывести debug данные немедленно
debug_flush();

// Очистить буфер debug вывода
\Core\Debug::clearOutput();
```

### Настройка автовывода

```php
// Отключить автоматический вывод
\Core\Debug::setAutoDisplay(false);

// Проверить статус
if (\Core\Debug::isAutoDisplay()) {
    // автовывод включен
}
```

### Прямое добавление HTML в буфер

```php
// Добавить произвольный HTML в debug буфер
\Core\Debug::addOutput('<div class="custom-debug">My debug info</div>');
```

## Примеры использования

### Пример 1: Автоматический вывод (по умолчанию)

```php
class HomeController
{
    public function index(): void
    {
        dump($_GET, 'Request GET');
        dump_pretty($_POST, 'Request POST');
        
        $result = benchmark(function() {
            return $this->heavyCalculation();
        }, 'Heavy Calculation');
        
        display('home.twig', ['result' => $result]);
    }
}
```

Debug информация автоматически выведется после страницы.

### Пример 2: Ручное размещение

```php
// bootstrap.php или в начале контроллера
\Core\Debug::setAutoDisplay(false);

// В контроллере
class ProductController
{
    public function show(int $id): void
    {
        $product = $this->getProduct($id);
        dump($product, 'Product Data');
        
        benchmark(function() use ($product) {
            return $product->calculatePrice();
        }, 'Price Calculation');
        
        display('product.twig', ['product' => $product]);
    }
}
```

В шаблоне `product.twig`:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Product</title>
</head>
<body>
    <h1>{{ product.name }}</h1>
    <p>{{ product.description }}</p>
    
    <!-- Debug панель в конце страницы -->
    {% if has_debug_output() %}
    <div style="margin-top: 50px; padding: 20px; background: #f5f5f5;">
        <h2>🐛 Debug Information</h2>
        {!! render_debug() !!}
    </div>
    {% endif %}
</body>
</html>
```

### Пример 3: Условный вывод

```php
class ApiController
{
    public function getData(): void
    {
        // Debug только для админов
        if (is_admin()) {
            dump($_REQUEST, 'API Request');
            trace('API Call Stack');
        }
        
        $data = $this->fetchData();
        
        // Вывод JSON без debug (даже если есть)
        \Core\Debug::clearOutput();
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
```

## Режимы работы

### Development (APP_ENV=development)

- Debug функции активны
- Вывод в HTML формате
- Автоматический вывод включен по умолчанию
- Подробная информация о файлах и строках

### Production (APP_ENV=production)

- Debug функции отключены
- Данные логируются в файл
- Вывод на страницу не происходит
- Безопасность данных сохраняется

## API Reference

### Core\Debug

```php
// Основные методы
Debug::dump($var, ?string $label = null, bool $die = false): void
Debug::dumpPretty($var, ?string $label = null, bool $die = false): void
Debug::collect($var, ?string $label = null): void
Debug::dumpAll(bool $die = false): void

// Управление буфером
Debug::flush(): void
Debug::getOutput(): string
Debug::hasOutput(): bool
Debug::clearOutput(): void
Debug::addOutput(string $html): void

// Настройки
Debug::setAutoDisplay(bool $auto): void
Debug::isAutoDisplay(): bool
Debug::setMaxDepth(int $depth): void
Debug::setShowBacktrace(bool $show): void
```

### Глобальные функции

```php
// Основные
dump($var, ?string $label = null): void
dd($var, ?string $label = null): never
dump_pretty($var, ?string $label = null): void
dd_pretty($var, ?string $label = null): never

// Коллекция
collect($var, ?string $label = null): void
dump_all(bool $die = false): void
clear_debug(): void

// Дополнительные
trace(?string $label = null): void
benchmark(callable $callback, ?string $label = null): mixed

// Управление буфером
debug_flush(): void
debug_output(): string
has_debug_output(): bool
render_debug(): string

// Проверки окружения
is_debug(): bool
is_dev(): bool
is_prod(): bool
```

## Миграция со старой версии

Если у вас есть код, который полагается на прямой вывод debug данных, не беспокойтесь:

**Старый код продолжит работать!** Debug данные теперь будут выводиться автоматически в конце страницы вместо середины.

Если вам нужно старое поведение (немедленный вывод), используйте:

```php
dump($data);
debug_flush(); // Немедленный вывод
```

Или вызовите напрямую:

```php
\Core\Debug::dump($data);
\Core\Debug::flush();
```

## Производительность

Буферизация debug вывода практически не влияет на производительность:

- Данные хранятся в памяти (массив)
- Вывод происходит один раз в конце
- В production режиме буфер не используется
- Shutdown handler выполняется только в development

## Устранение проблем

### Debug не отображается

1. Проверьте `APP_ENV`:
```php
var_dump(\Core\Environment::get()); // должно быть 'development'
```

2. Проверьте, включен ли debug:
```php
var_dump(\Core\Environment::isDebug()); // должно быть true
```

3. Проверьте автовывод:
```php
var_dump(\Core\Debug::isAutoDisplay()); // должно быть true
```

### Debug выводится в неправильном месте

Отключите автовывод и используйте `render_debug()` в шаблоне:

```php
\Core\Debug::setAutoDisplay(false);
```

### Конфликт с output buffering

Если вы используете собственную буферизацию:

```php
ob_start();
// ваш код
dump($data);
ob_end_flush();

// Debug выведется автоматически после ob_end_flush()
```

## Лучшие практики

1. **Используйте метки**: Всегда добавляйте понятные метки к dump
```php
dump($user, 'Current User');
```

2. **Benchmark критичных участков**: Измеряйте производительность важных операций
```php
$result = benchmark(fn() => $this->complexQuery(), 'Database Query');
```

3. **Collect для множественных данных**: Собирайте данные без вывода
```php
collect($var1, 'Variable 1');
collect($var2, 'Variable 2');
dump_all(); // Вывести всё в конце
```

4. **Очищайте буфер для API**: Не забывайте очищать debug данные для JSON ответов
```php
\Core\Debug::clearOutput();
echo json_encode($data);
```

5. **Используйте условия**: Debug только когда нужно
```php
if (is_debug()) {
    dump($sensitiveData, 'Sensitive');
}
```
