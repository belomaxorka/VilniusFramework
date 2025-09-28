# Быстрый старт - Система дебага

## Установка

Система дебага уже интегрирована в проект. Просто создайте `.env` файл на основе `config/env.example`:

```bash
cp config/env.example .env
```

## Настройка окружения

Отредактируйте `.env` файл:

```env
# Для разработки
APP_ENV=development
APP_DEBUG=true

# Для продакшена
APP_ENV=production
APP_DEBUG=false
```

## Основные функции

### 1. Простой вывод переменных
```php
$data = ['name' => 'John', 'age' => 30];
dump($data); // Выводит и продолжает выполнение
dd($data);   // Выводит и останавливает выполнение
```

### 2. Красивый вывод
```php
$complex = ['nested' => ['data' => 'here']];
dump_pretty($complex); // Темная тема, подсветка синтаксиса
dd_pretty($complex);   // Красивый вывод + остановка
```

### 3. Сбор данных
```php
collect($userData, 'User Info');
collect($configData, 'Config');
collect($requestData, 'Request');

dump_all(); // Показать все собранные данные
```

### 4. Отладка производительности
```php
$result = benchmark(function() {
    return expensiveOperation();
}, 'Operation Name');
```

### 5. Просмотр стека вызовов
```php
trace('Current location');
```

## Демонстрация

Перейдите на `/debug` для просмотра всех возможностей системы дебага.

## Безопасность

- В продакшене (`APP_ENV=production`) все функции дебага отключены
- Ошибки логируются в `storage/logs/debug.log`
- Пользователи видят только общую страницу ошибки

## Полезные функции

```php
// Проверка окружения
if (is_debug()) { /* код только для отладки */ }
if (is_dev()) { /* код только для разработки */ }
if (is_prod()) { /* код только для продакшена */ }

// Получение переменных окружения
$dbHost = env('DB_HOST', 'localhost');

// Логирование только в режиме отладки
debug_log('Debug message');
```
