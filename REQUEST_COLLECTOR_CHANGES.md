# Request Collector - Changelog

## 🎉 Что было добавлено в Debug Toolbar

Добавлен новый коллектор **Request Collector** для отображения полной информации о HTTP-запросах в Debug Toolbar.

## 📦 Новые файлы

### 1. `core/DebugToolbar/Collectors/RequestCollector.php`
Основной файл коллектора, который собирает и отображает:
- ✅ GET параметры
- ✅ POST данные
- ✅ Загруженные файлы (FILES)
- ✅ Cookies
- ✅ HTTP Headers
- ✅ Server Variables
- ✅ Базовая информация о запросе (метод, URI, IP, время)

**Особенности:**
- Автоматическое скрытие чувствительных данных (пароли, токены)
- Красивое табличное отображение
- Цветовая кодировка HTTP методов
- Поддержка множественной загрузки файлов
- Свертываемые секции для больших данных
- Определение реального IP клиента (с учетом proxy)
- Определение HTTPS соединений

### 2. `app/Controllers/RequestDemoController.php`
Контроллер для демонстрационной страницы.

### 3. `resources/views/request-demo.tpl`
Интерактивная демонстрационная страница с:
- Формами для тестирования POST запросов
- Ссылками для GET запросов с параметрами
- Загрузкой файлов (одиночной и множественной)
- Установкой cookies
- Красивым современным UI

### 4. `docs/RequestCollector.md`
Полная документация по Request Collector.

### 5. `docs/RequestCollectorQuickStart.md`
Краткое руководство по использованию.

### 6. `REQUEST_COLLECTOR_CHANGES.md`
Этот файл с описанием изменений.

## 🔧 Измененные файлы

### 1. `core/DebugToolbar.php`
**Изменения:**
- Добавлен import `RequestCollector`
- Зарегистрирован новый коллектор в методе `initialize()`

```php
use Core\DebugToolbar\Collectors\RequestCollector;

// ...

self::addCollector(new RequestCollector());
```

### 2. `core/Router.php`
**Изменения:**
- Добавлен метод `any()` для регистрации маршрутов на все HTTP методы

```php
public function any(string $uri, callable|array $action): void
{
    foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'] as $method) {
        $this->addRoute($method, $uri, $action);
    }
}
```

### 3. `public/index.php`
**Изменения:**
- Добавлен маршрут для демо-страницы

```php
$router->any('demo', [\App\Controllers\RequestDemoController::class, 'demo']);
```

## 🚀 Как использовать

### Запуск сервера

```bash
cd public
php -S localhost:8000 index.php
```

### Открыть в браузере

```
http://localhost:8000/demo
```

### Посмотреть в Debug Toolbar

1. Откройте Debug Toolbar внизу страницы
2. Перейдите на вкладку **🌐 Request**
3. Изучите все секции с данными запроса

## 📊 Структура Request Collector

```
🌐 Request Information
│
├── 📋 Basic Info
│   ├── Method (GET/POST/PUT/DELETE/etc.)
│   ├── URI
│   ├── Full URL
│   ├── Protocol (HTTP/1.1)
│   ├── Remote Address (IP)
│   └── Request Time
│
├── 📥 GET Parameters
│   └── Key-Value таблица
│
├── 📤 POST Parameters
│   └── Key-Value таблица
│
├── 📎 Uploaded Files
│   └── Таблица с: name, type, size, error, tmp_name
│
├── 🍪 Cookies
│   └── Key-Value таблица
│
├── 📋 HTTP Headers
│   └── Key-Value таблица
│
└── ⚙️ Server Variables
    └── Key-Value таблица (свертываемая)
```

## 🎨 Цветовая кодировка

Request Collector использует цветовую кодировку для HTTP методов:

| Метод  | Цвет       | Hex     |
|--------|------------|---------|
| GET    | 🟢 Зеленый | #4caf50 |
| POST   | 🔵 Синий   | #2196f3 |
| PUT    | 🟠 Оранжевый | #ff9800 |
| PATCH  | 🟣 Фиолетовый | #9c27b0 |
| DELETE | 🔴 Красный | #f44336 |
| Другие | ⚫ Серый   | #757575 |

## 🔒 Безопасность

Request Collector автоматически скрывает чувствительные данные:

```
PHP_AUTH_PW       → ***HIDDEN***
PHP_AUTH_USER     → ***HIDDEN***
HTTP_AUTHORIZATION → ***HIDDEN***
```

## 📈 Приоритет отображения

Request Collector имеет приоритет **90**, что означает:

1. **Overview** (100) - отображается первым
2. **Request** (90) - отображается вторым ← НОВЫЙ
3. Другие коллекторы (< 90)

## ✅ Тестирование

### Тест GET запроса

```
http://localhost:8000/demo?page=1&limit=10&search=test
```

### Тест POST запроса

Используйте форму на `/demo` или cURL:

```bash
curl -X POST http://localhost:8000/demo \
  -d "username=john" \
  -d "email=john@example.com" \
  -d "message=Hello World"
```

### Тест загрузки файлов

```bash
curl -X POST http://localhost:8000/demo \
  -F "username=john" \
  -F "uploaded_file=@/path/to/file.txt"
```

### Тест custom headers

```bash
curl -H "X-Custom-Header: test" \
     -H "X-API-Key: secret-key" \
     http://localhost:8000/demo
```

## 📝 Примеры использования API

### Получить коллектор

```php
use Core\DebugToolbar;

$collector = DebugToolbar::getCollector('request');
```

### Изменить приоритет

```php
$collector->setPriority(95);
```

### Отключить коллектор

```php
$collector->setEnabled(false);
```

### Получить собранные данные

```php
$data = $collector->getData();

echo $data['method'];  // GET, POST, etc.
echo $data['uri'];     // /demo
echo $data['path'];    // /demo
print_r($data['get']); // GET параметры
print_r($data['post']); // POST данные
```

## 🎯 Производительность

Request Collector имеет **минимальное влияние** на производительность:

- ✅ Работает только в Debug режиме
- ✅ Не выполняет запросов к БД
- ✅ Не делает HTTP запросов
- ✅ Использует только встроенные супергlobals
- ✅ Данные собираются один раз при рендеринге
- ✅ Легкий HTML вывод без тяжелых библиотек

## 📚 Документация

- **Полная документация**: `docs/RequestCollector.md`
- **Quick Start**: `docs/RequestCollectorQuickStart.md`
- **Debug Toolbar**: `docs/DebugToolbar.md`
- **Custom Collectors**: `docs/DebugToolbarCollectors.md`

## 🔮 Будущие улучшения

Возможные улучшения в будущем:

- [ ] Session данные
- [ ] Response information
- [ ] Request/Response diff
- [ ] Export данных запроса в JSON
- [ ] History запросов
- [ ] Search/Filter по данным
- [ ] Raw request body для JSON/XML API
- [ ] GraphQL query inspector

## 🐛 Известные ограничения

1. Request Collector работает только в Debug режиме
2. Не отображает raw request body (для JSON/XML API)
3. Не показывает Response данные (только Request)
4. Не сохраняет историю запросов между перезагрузками

## 🤝 Вклад

Request Collector - это часть фреймворка TorrentPier. Если у вас есть идеи или предложения:

1. Создайте issue с описанием
2. Или сделайте pull request с реализацией
3. Обсудите в команде разработчиков

## 📞 Поддержка

Если возникли проблемы с Request Collector:

1. Проверьте, что Debug режим включен
2. Убедитесь, что Debug Toolbar активен
3. Очистите кеш: `rm -rf storage/cache/*`
4. Проверьте логи: `storage/logs/app.log`

## 🎓 Заключение

Request Collector - это мощный инструмент для отладки HTTP-запросов, который:

✅ Экономит время разработчика
✅ Упрощает отладку
✅ Предоставляет полную информацию о запросе
✅ Имеет красивый и понятный интерфейс
✅ Безопасен (скрывает чувствительные данные)
✅ Производителен (минимальный overhead)

**Приятной разработки! 🚀**

