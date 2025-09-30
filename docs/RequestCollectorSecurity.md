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

В production режиме (`APP_ENV=production`) Request Collector **скрывает все серверные переменные**:

✅ GET/POST/Cookies/Headers - показываются полностью  
🔒 **Server Variables - ВСЕ скрыты**  

```
🔒 PRODUCTION MODE

⚠️ Production Mode: All server variables are hidden for security reasons.
    Server variables are only visible in development mode.

Server Variables
┌─────────────────────┬──────────────────────────────────────┐
│ Key                 │ Value                                │
├─────────────────────┼──────────────────────────────────────┤
│ REQUEST_METHOD      │ ***HIDDEN (PRODUCTION MODE)***       │  ← Скрыто
│ REQUEST_URI         │ ***HIDDEN (PRODUCTION MODE)***       │  ← Скрыто
│ SERVER_PROTOCOL     │ ***HIDDEN (PRODUCTION MODE)***       │  ← Скрыто
│ DOCUMENT_ROOT       │ ***HIDDEN (PRODUCTION MODE)***       │  ← Скрыто
│ REMOTE_ADDR         │ ***HIDDEN (PRODUCTION MODE)***       │  ← Скрыто
│ SERVER_NAME         │ ***HIDDEN (PRODUCTION MODE)***       │  ← Скрыто
│ PATH                │ ***HIDDEN (PRODUCTION MODE)***       │  ← Скрыто
│ PHP_SELF            │ ***HIDDEN (PRODUCTION MODE)***       │  ← Скрыто
│ SCRIPT_FILENAME     │ ***HIDDEN (PRODUCTION MODE)***       │  ← Скрыто
│ PHP_AUTH_PW         │ ***HIDDEN***                         │  ← Всегда скрыто
└─────────────────────┴──────────────────────────────────────┘
```

**Почему все переменные скрыты?**

В production окружение может содержать непредсказуемые переменные (пути, токены, API ключи, etc.), 
которые могут появиться в любой момент. Вместо поддержки белых/черных списков, которые могут 
устареть, мы полностью скрываем все серверные переменные для максимальной безопасности.

## Типы защиты данных

### Простая логика: Production vs Development

В production режиме **все** серверные переменные скрываются как `***HIDDEN (PRODUCTION MODE)***`.

**Почему такой подход?**

- ✅ **Максимальная безопасность** - нет риска утечки новых переменных
- ✅ **Простота** - не нужно поддерживать белые/черные списки
- ✅ **Надежность** - работает с любыми переменными окружения
- ✅ **Zero-trust** - не угадываем, что безопасно, а скрываем всё

В development режиме показываются все переменные для удобства отладки.

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
| REQUEST_METHOD      | ✅ Видна     | 🔒 Скрыта   |
| REQUEST_URI         | ✅ Видна     | 🔒 Скрыта   |
| DOCUMENT_ROOT       | ✅ Видна     | 🔒 Скрыта   |
| REMOTE_ADDR         | ✅ Видна     | 🔒 Скрыта   |
| SERVER_NAME         | ✅ Видна     | 🔒 Скрыта   |
| SCRIPT_FILENAME     | ✅ Видна     | 🔒 Скрыта   |
| PATH                | ✅ Видна     | 🔒 Скрыта   |
| PHP_SELF            | ✅ Видна     | 🔒 Скрыта   |
| PHP_AUTH_PW         | 🔒 Скрыта    | 🔒 Скрыта   |
| DB_PASSWORD         | 🔒 Скрыта    | 🔒 Скрыта   |
| **ВСЕ остальные**   | ✅ Видны     | 🔒 Скрыты   |

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

## Философия "Hide All" в Production

Request Collector использует подход **"скрыть всё в production"** вместо белых списков.

**Преимущества:**

1. ✅ **Максимальная безопасность** - нет риска забыть добавить переменную в черный список
2. ✅ **Простота кода** - не нужно поддерживать сложные списки
3. ✅ **Будущее-proof** - работает с любыми новыми переменными
4. ✅ **Zero-trust** - безопасность по умолчанию

**Если нужно показать переменные в production:**

Не рекомендуется, но если действительно необходимо, используйте development режим с отключенным debug:

```bash
# Не рекомендуется для production!
APP_ENV=development
APP_DEBUG=false
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

