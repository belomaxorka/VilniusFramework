# Request Collector - Security Features

## 🔒 Безопасность в Production режиме

Request Collector автоматически адаптирует уровень детализации информации в зависимости от окружения приложения.

## Режимы работы

### Development Mode (разработка)

В режиме разработки (`APP_ENV=development`) Request Collector показывает **полную информацию**:

✅ Все GET параметры  
✅ Все POST данные  
✅ Все cookies  
✅ Все HTTP headers  
✅ **Все server variables** (кроме критически чувствительных)  

```
Server Variables
┌─────────────────────┬──────────────────────┐
│ Key                 │ Value                │
├─────────────────────┼──────────────────────┤
│ DOCUMENT_ROOT       │ /var/www/html        │
│ REMOTE_ADDR         │ 127.0.0.1            │
│ SERVER_NAME         │ localhost            │
│ PATH                │ /usr/bin:/bin        │
│ PHP_SELF            │ /index.php           │
│ SCRIPT_FILENAME     │ /var/www/index.php   │
│ PHP_AUTH_PW         │ ***HIDDEN***         │  ← Всегда скрыто
└─────────────────────┴──────────────────────┘
```

### Production Mode (продакшн)

В production режиме (`APP_ENV=production`) Request Collector **скрывает чувствительные данные**:

✅ Базовые параметры запроса (METHOD, URI, TIME)  
✅ GET/POST/Cookies/Headers - показываются полностью  
🔒 **Server Variables - скрыты** (кроме безопасных)  

```
🔒 PRODUCTION MODE

⚠️ Production Mode: Sensitive server variables are hidden for security reasons.
    Only safe variables are shown.

Server Variables
┌─────────────────────┬──────────────────────────────────────┐
│ Key                 │ Value                                │
├─────────────────────┼──────────────────────────────────────┤
│ REQUEST_METHOD      │ GET                                  │  ← Безопасно
│ REQUEST_URI         │ /demo                                │  ← Безопасно
│ SERVER_PROTOCOL     │ HTTP/1.1                             │  ← Безопасно
│ DOCUMENT_ROOT       │ ***HIDDEN (PRODUCTION MODE)***       │  ← Скрыто
│ REMOTE_ADDR         │ ***HIDDEN (PRODUCTION MODE)***       │  ← Скрыто
│ SERVER_NAME         │ ***HIDDEN (PRODUCTION MODE)***       │  ← Скрыто
│ PATH                │ ***HIDDEN (PRODUCTION MODE)***       │  ← Скрыто
│ PHP_SELF            │ ***HIDDEN (PRODUCTION MODE)***       │  ← Скрыто
│ SCRIPT_FILENAME     │ ***HIDDEN (PRODUCTION MODE)***       │  ← Скрыто
│ PHP_AUTH_PW         │ ***HIDDEN***                         │  ← Всегда скрыто
└─────────────────────┴──────────────────────────────────────┘
```

## Типы защиты данных

### 1. Всегда скрытые переменные (все режимы)

Эти переменные **всегда** скрыты независимо от режима:

```php
'PHP_AUTH_PW'           → ***HIDDEN***
'PHP_AUTH_USER'         → ***HIDDEN***
'HTTP_AUTHORIZATION'    → ***HIDDEN***
'DATABASE_URL'          → ***HIDDEN***
'DB_PASSWORD'           → ***HIDDEN***
'DB_USERNAME'           → ***HIDDEN***
'API_KEY'               → ***HIDDEN***
'SECRET_KEY'            → ***HIDDEN***
'AWS_SECRET'            → ***HIDDEN***
'STRIPE_SECRET'         → ***HIDDEN***
```

### 2. Автоматическое определение чувствительных данных

Request Collector автоматически скрывает переменные, содержащие в имени:

- `PASSWORD`
- `SECRET`
- `TOKEN`
- `KEY`
- `AUTH`
- `CREDENTIAL`

**Примеры:**
```
MY_API_KEY          → ***HIDDEN***
JWT_TOKEN           → ***HIDDEN***
DB_PASSWORD         → ***HIDDEN***
AUTH_SECRET         → ***HIDDEN***
OAUTH_CREDENTIAL    → ***HIDDEN***
```

### 3. Безопасные переменные в Production

В production режиме разрешено показывать только эти переменные:

```php
✅ REQUEST_METHOD       // HTTP метод
✅ REQUEST_URI          // URI запроса
✅ REQUEST_TIME         // Время запроса
✅ REQUEST_TIME_FLOAT   // Время с микросекундами
✅ SERVER_PROTOCOL      // HTTP/1.1, HTTP/2
✅ GATEWAY_INTERFACE    // CGI/1.1
✅ SERVER_SOFTWARE      // Apache, Nginx
✅ QUERY_STRING         // Query string
✅ CONTENT_TYPE         // Content-Type
✅ CONTENT_LENGTH       // Content-Length
```

Все остальные переменные скрываются как `***HIDDEN (PRODUCTION MODE)***`.

## Примеры

### Development Mode

```bash
export APP_ENV=development
php -S localhost:8000
```

Откройте `http://localhost:8000/demo` и посмотрите на Server Variables:
- Все переменные видны
- Только критически чувствительные скрыты (*_PASSWORD, *_SECRET, etc.)
- Полная информация для отладки

### Production Mode

```bash
export APP_ENV=production
php -S localhost:8000
```

Откройте `http://localhost:8000/demo` и посмотрите на Server Variables:
- 🔒 Красный badge "PRODUCTION MODE"
- ⚠️ Предупреждающее сообщение
- Большинство переменных скрыто
- Видны только безопасные параметры

## Сравнительная таблица

| Переменная          | Development | Production |
|---------------------|-------------|------------|
| REQUEST_METHOD      | ✅ Видна     | ✅ Видна    |
| REQUEST_URI         | ✅ Видна     | ✅ Видна    |
| DOCUMENT_ROOT       | ✅ Видна     | 🔒 Скрыта   |
| REMOTE_ADDR         | ✅ Видна     | 🔒 Скрыта   |
| SERVER_NAME         | ✅ Видна     | 🔒 Скрыта   |
| SCRIPT_FILENAME     | ✅ Видна     | 🔒 Скрыта   |
| PATH                | ✅ Видна     | 🔒 Скрыта   |
| PHP_SELF            | ✅ Видна     | 🔒 Скрыта   |
| PHP_AUTH_PW         | 🔒 Скрыта    | 🔒 Скрыта   |
| DB_PASSWORD         | 🔒 Скрыта    | 🔒 Скрыта   |

## Визуальная индикация

### Production Mode Badge

В production режиме в заголовке секции Server Variables отображается красный badge:

```html
📋 Server Variables 🔒 PRODUCTION MODE
```

### Warning Message

Перед таблицей показывается предупреждение:

```
⚠️ Production Mode: Sensitive server variables are hidden 
   for security reasons. Only safe variables are shown.
```

## Настройка списка безопасных переменных

Если вы хотите изменить список разрешенных переменных в production, отредактируйте массив `$safeInProduction` в методе `filterServer()`:

```php
// core/DebugToolbar/Collectors/RequestCollector.php

private function filterServer(array $server): array
{
    // ...
    
    $safeInProduction = [
        'REQUEST_METHOD',
        'REQUEST_URI',
        'REQUEST_TIME',
        'REQUEST_TIME_FLOAT',
        'SERVER_PROTOCOL',
        // Добавьте свои безопасные переменные
        'MY_SAFE_VAR',
        'ANOTHER_SAFE_VAR',
    ];
    
    // ...
}
```

## Добавление чувствительных переменных

Чтобы добавить дополнительные всегда скрытые переменные:

```php
$alwaysHidden = [
    'PHP_AUTH_PW',
    'PHP_AUTH_USER',
    'HTTP_AUTHORIZATION',
    // Добавьте свои
    'MY_SECRET_VAR',
    'CUSTOM_API_KEY',
];
```

## Паттерны чувствительных данных

Автоматическое определение работает по паттернам:

```php
$patterns = [
    'PASSWORD',
    'SECRET', 
    'TOKEN',
    'KEY',
    'AUTH',
    'CREDENTIAL'
];
```

Добавьте свои паттерны в метод `isSensitiveKey()`:

```php
$patterns = [
    'PASSWORD',
    'SECRET',
    'TOKEN',
    'KEY',
    'AUTH',
    'CREDENTIAL',
    'PRIVATE',  // Ваш паттерн
    'SENSITIVE', // Ваш паттерн
];
```

## Best Practices

### ✅ Рекомендуется

1. **Всегда используйте production режим на боевых серверах**
   ```bash
   APP_ENV=production
   ```

2. **Отключайте Debug Toolbar в production**
   ```bash
   APP_DEBUG=false
   ```

3. **Проверяйте логи на предмет утечек данных**
   ```bash
   grep -r "PASSWORD\|SECRET\|TOKEN" storage/logs/
   ```

4. **Используйте .env файлы для чувствительных данных**
   ```bash
   # .env
   DB_PASSWORD=secret123
   API_KEY=your-api-key
   ```

### ❌ Не рекомендуется

1. ❌ Оставлять Debug Toolbar включенным в production
2. ❌ Хранить пароли в коде
3. ❌ Коммитить .env файлы в git
4. ❌ Логировать чувствительные данные

## Тестирование безопасности

### Тест 1: Проверка скрытия паролей

```bash
# Development
curl http://localhost:8000/demo | grep "PHP_AUTH_PW"
# Должно показать: ***HIDDEN***

# Production
export APP_ENV=production
curl http://localhost:8000/demo | grep "PHP_AUTH_PW"
# Должно показать: ***HIDDEN***
```

### Тест 2: Проверка production режима

```bash
export APP_ENV=production
curl http://localhost:8000/demo | grep "PRODUCTION MODE"
# Должно найти badge и warning
```

### Тест 3: Проверка фильтрации

```bash
# Установите тестовую переменную
export MY_SECRET_KEY="supersecret"

# Development - должна быть скрыта
curl http://localhost:8000/demo | grep "MY_SECRET_KEY"
# Результат: ***HIDDEN***

# Production - тоже скрыта
export APP_ENV=production
curl http://localhost:8000/demo | grep "MY_SECRET_KEY"
# Результат: ***HIDDEN***
```

## Compliance & Regulations

Request Collector помогает соответствовать требованиям:

- ✅ **GDPR** - не показывает IP адреса в production
- ✅ **PCI DSS** - скрывает пути к файлам и конфиденциальные данные
- ✅ **OWASP** - защита от утечки информации
- ✅ **ISO 27001** - контроль доступа к чувствительным данным

## Заключение

Request Collector обеспечивает **многоуровневую защиту** чувствительных данных:

1. 🛡️ **Автоматическое определение** чувствительных переменных
2. 🔒 **Production режим** с минимальной информацией
3. ⚠️ **Визуальная индикация** режима безопасности
4. ✅ **Белый список** безопасных переменных
5. 🚫 **Черный список** всегда скрытых данных

**Безопасность по умолчанию!** 🔐

