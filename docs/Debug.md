# Система дебага

Система дебага предоставляет мощные инструменты для отладки приложения в режиме разработки и безопасного логирования в продакшене.

## Окружения

Система автоматически определяет окружение на основе:
- Переменной `APP_ENV` в .env файле
- Имени сервера (localhost, .local, .dev = development)
- По умолчанию: production

### Доступные окружения:
- `development` - режим разработки
- `production` - продакшен
- `testing` - тестовое окружение

## Функции дебага

### Основные функции

#### `dump($var, $label = null)`
Выводит переменную без остановки выполнения.

```php
$data = ['name' => 'John', 'age' => 30];
dump($data, 'User Data');
```

#### `dd($var, $label = null)`
Выводит переменную и останавливает выполнение (dump and die).

```php
$data = ['name' => 'John', 'age' => 30];
dd($data, 'User Data'); // Остановит выполнение
```

#### `dump_pretty($var, $label = null)`
Выводит переменную с красивым форматированием (темная тема).

```php
$complex = [
    'user' => [
        'name' => 'John',
        'settings' => [
            'theme' => 'dark',
            'notifications' => true
        ]
    ]
];
dump_pretty($complex, 'User Settings');
```

#### `dd_pretty($var, $label = null)`
Красивый вывод с остановкой выполнения.

### Сбор данных

#### `collect($var, $label = null)`
Собирает данные для дебага без вывода.

```php
collect($userData, 'User Info');
collect($configData, 'Config');
collect($requestData, 'Request');
```

#### `dump_all($die = false)`
Выводит все собранные данные.

```php
collect($data1, 'Data 1');
collect($data2, 'Data 2');
dump_all(); // Покажет все собранные данные
```

#### `clear_debug()`
Очищает собранные данные.

```php
clear_debug();
```

### Дополнительные функции

#### `trace($label = null)`
Выводит backtrace (стек вызовов).

```php
function someFunction() {
    trace('Inside someFunction');
}
```

#### `benchmark($callback, $label = null)`
Измеряет время выполнения функции.

```php
$result = benchmark(function() {
    // Ваш код
    return expensiveOperation();
}, 'Expensive Operation');
```

### Проверка окружения

#### `is_debug()`
Проверяет, включен ли режим отладки.

```php
if (is_debug()) {
    dump($data);
}
```

#### `is_dev()`, `is_prod()`
Проверяют тип окружения.

```php
if (is_dev()) {
    // Код только для разработки
}
```

#### `env($key, $default = null)`
Получает переменную окружения.

```php
$dbHost = env('DB_HOST', 'localhost');
```

## Обработка ошибок

Система автоматически обрабатывает ошибки в зависимости от окружения:

### В режиме разработки:
- Показывает детальную информацию об ошибке
- Отображает stack trace
- Показывает файл и строку с ошибкой

### В продакшене:
- Логирует ошибку в файл
- Показывает общую страницу ошибки
- Скрывает детали от пользователей

## Конфигурация

### .env файл
```env
APP_ENV=development
APP_DEBUG=true
LOG_LEVEL=debug
```

### Настройки логгера
```php
// В коде
Logger::setMinLevel('debug');
Logger::addHandler(new Logger\FileHandler('/path/to/logfile.log'));
```

## Примеры использования

### Отладка контроллера
```php
class UserController 
{
    public function show($id) 
    {
        $user = User::find($id);
        
        // Собираем данные для дебага
        collect($user, 'User Data');
        collect($id, 'User ID');
        
        // Выводим данные
        dump($user, 'Found User');
        
        return view('user.show', compact('user'));
    }
}
```

### Отладка API
```php
public function apiEndpoint(Request $request) 
{
    // Собираем данные запроса
    collect($request->all(), 'Request Data');
    collect($request->headers->all(), 'Headers');
    
    $result = processData($request->all());
    
    // Выводим результат
    dump_pretty($result, 'API Result');
    
    return response()->json($result);
}
```

### Профилирование производительности
```php
public function expensiveOperation() 
{
    $result = benchmark(function() {
        // Дорогая операция
        return $this->processLargeDataset();
    }, 'Dataset Processing');
    
    return $result;
}
```

## Безопасность

- В продакшене все функции дебага отключены
- Детали ошибок скрыты от пользователей
- Логирование происходит только в файлы
- Переменные окружения защищены от случайного вывода

## Логи

Логи сохраняются в `storage/logs/debug.log` и содержат:
- Временные метки
- Уровни логирования
- Детали ошибок
- Stack traces
- Данные дебага (в продакшене)

## Рекомендации

1. Используйте `collect()` для сбора данных без вывода
2. Используйте `dump_all()` в конце метода для просмотра всех данных
3. Используйте `benchmark()` для профилирования производительности
4. Всегда проверяйте `is_debug()` перед выводом чувствительных данных
5. Используйте `trace()` для отладки сложных вызовов функций
