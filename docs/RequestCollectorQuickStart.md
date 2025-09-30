# Request Collector - Quick Start

## Что это?

**Request Collector** - это коллектор для Debug Toolbar, который автоматически собирает и отображает полную информацию о каждом HTTP-запросе.

## Что было добавлено?

✅ **Request Collector** (`core/DebugToolbar/Collectors/RequestCollector.php`)
- Собирает GET, POST, FILES, COOKIES, Headers, Server variables
- Красивое отображение в виде таблиц
- Цветовая кодировка HTTP методов
- Автоматическое скрытие чувствительных данных
- Поддержка множественной загрузки файлов

✅ **Метод Router::any()** (`core/Router.php`)
- Регистрирует маршрут для всех HTTP методов (GET, POST, PUT, PATCH, DELETE, etc.)

✅ **Demo страница** (`/demo`)
- Интерактивная демонстрация возможностей Request Collector
- Формы для тестирования GET/POST запросов
- Загрузка файлов
- Установка cookies

## Быстрый старт

### 1. Запустите сервер

```bash
cd public
php -S localhost:8000 index.php
```

### 2. Откройте браузер

Перейдите по адресу:
- `http://localhost:8000/` - главная страница
- `http://localhost:8000/demo` - демо страница Request Collector

### 3. Изучите Debug Toolbar

Внизу страницы откройте Debug Toolbar и перейдите на вкладку **🌐 Request**.

## Что вы увидите?

### 📋 Basic Info
- HTTP метод (GET, POST, PUT, DELETE, etc.)
- URI и полный URL
- HTTP протокол
- IP адрес клиента
- Время запроса

### 📥 GET Parameters
Все параметры из query string в виде таблицы.

### 📤 POST Parameters
Все данные, отправленные через POST.

### 📎 Uploaded Files
Информация о загруженных файлах:
- Имя файла
- MIME тип
- Размер
- Статус загрузки
- Временное имя

### 🍪 Cookies
Все cookies, отправленные клиентом.

### 📋 HTTP Headers
Все HTTP заголовки запроса.

### ⚙️ Server Variables
Переменные окружения и серверные параметры (чувствительные данные автоматически скрыты).

## Примеры использования

### GET запрос с параметрами

```
http://localhost:8000/demo?page=1&limit=10&search=test
```

Request Collector покажет:
```
GET Parameters (3)
┌─────────┬────────┐
│ Key     │ Value  │
├─────────┼────────┤
│ page    │ 1      │
│ limit   │ 10     │
│ search  │ test   │
└─────────┴────────┘
```

### POST запрос с данными

Заполните форму на `/demo` и отправьте. Request Collector отобразит все POST данные в структурированном виде.

### Загрузка файлов

Используйте форму загрузки файлов на `/demo`. Request Collector покажет:
- Имена файлов
- Размеры
- MIME типы
- Статус загрузки (OK/Error)

### Работа с cookies

1. Нажмите "Set Demo Cookie" на `/demo`
2. Перезагрузите страницу
3. В Request Collector увидите установленный cookie

### Custom Headers (через cURL)

```bash
curl -H "X-Custom-Header: test" \
     -H "X-API-Key: your-api-key" \
     -d "username=john" \
     -d "email=john@example.com" \
     http://localhost:8000/demo
```

Request Collector покажет кастомные заголовки в секции HTTP Headers.

## Цветовая кодировка методов

- 🟢 **GET** - зеленый (#4caf50)
- 🔵 **POST** - синий (#2196f3)
- 🟠 **PUT** - оранжевый (#ff9800)
- 🟣 **PATCH** - фиолетовый (#9c27b0)
- 🔴 **DELETE** - красный (#f44336)
- ⚫ **Другие** - серый (#757575)

## Безопасность

Request Collector автоматически скрывает чувствительные данные:
- `PHP_AUTH_PW` → `***HIDDEN***`
- `PHP_AUTH_USER` → `***HIDDEN***`
- `HTTP_AUTHORIZATION` → `***HIDDEN***`

## Настройка

Request Collector не требует дополнительной настройки. Он автоматически включается в Debug режиме.

Если хотите изменить приоритет отображения:

```php
use Core\DebugToolbar;

$collector = DebugToolbar::getCollector('request');
$collector->setPriority(95); // По умолчанию 90
```

Чтобы отключить:

```php
$collector = DebugToolbar::getCollector('request');
$collector->setEnabled(false);
```

## Производительность

Request Collector работает **только в Debug режиме** и имеет минимальное влияние на производительность:
- Не выполняет запросов к БД
- Не делает дополнительных HTTP запросов
- Использует только встроенные PHP супергlobals
- Данные собираются один раз при рендеринге toolbar

## Интеграция с другими коллекторами

Request Collector отлично работает вместе с другими коллекторами:
- **Overview** - общая статистика
- **Queries** - SQL запросы
- **Timers** - измерение времени
- **Memory** - использование памяти
- **Dumps** - debug dumps
- **Contexts** - контексты выполнения

## Troubleshooting

### Request Collector не отображается

1. Убедитесь, что Debug режим включен (`APP_ENV=development`)
2. Проверьте, что Debug Toolbar включен
3. Очистите кеш (`rm -rf storage/cache/*`)

### Не видно POST данных

1. Проверьте, что форма отправляется методом POST
2. Проверьте `enctype` формы (должен быть `multipart/form-data` для файлов)
3. Убедитесь, что данные действительно отправляются (проверьте Network в DevTools)

### Cookies не отображаются

1. Cookies появляются только при следующем запросе после установки
2. Перезагрузите страницу после установки cookie
3. Проверьте, что cookies не заблокированы браузером

## Дополнительная информация

- [Request Collector Documentation](RequestCollector.md)
- [Debug Toolbar Documentation](DebugToolbar.md)
- [Creating Custom Collectors](DebugToolbarCollectors.md)

## Feedback

Если у вас есть предложения по улучшению Request Collector, создайте issue или pull request!

