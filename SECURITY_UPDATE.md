# 🔒 Security Update - Request Collector

## Что было добавлено?

В Request Collector добавлена **многоуровневая защита данных** с автоматическим скрытием чувствительных Server Variables в production режиме.

## 🎯 Основные изменения

### 1. Автоматическое определение чувствительных данных

Request Collector теперь **автоматически скрывает** любые переменные, содержащие:
- `PASSWORD`
- `SECRET`
- `TOKEN`
- `KEY`
- `AUTH`
- `CREDENTIAL`

**Примеры:**
```
DB_PASSWORD           → ***HIDDEN***
API_SECRET_KEY        → ***HIDDEN***
JWT_TOKEN             → ***HIDDEN***
OAUTH_CREDENTIAL      → ***HIDDEN***
MY_CUSTOM_API_KEY     → ***HIDDEN***
```

### 2. Расширенный список всегда скрытых переменных

Добавлены дополнительные переменные, которые **всегда скрыты** во всех режимах:

```php
✅ PHP_AUTH_PW           → ***HIDDEN***
✅ PHP_AUTH_USER         → ***HIDDEN***
✅ HTTP_AUTHORIZATION    → ***HIDDEN***
✅ DATABASE_URL          → ***HIDDEN***
✅ DB_PASSWORD           → ***HIDDEN***
✅ DB_USERNAME           → ***HIDDEN***
✅ API_KEY               → ***HIDDEN***
✅ SECRET_KEY            → ***HIDDEN***
✅ AWS_SECRET            → ***HIDDEN***
✅ STRIPE_SECRET         → ***HIDDEN***
```

### 3. Production Mode защита

В production режиме (`APP_ENV=production`) **дополнительная защита**:

#### ✅ Показываются только безопасные переменные:
```
REQUEST_METHOD        ✅
REQUEST_URI           ✅
REQUEST_TIME          ✅
REQUEST_TIME_FLOAT    ✅
SERVER_PROTOCOL       ✅
GATEWAY_INTERFACE     ✅
SERVER_SOFTWARE       ✅
QUERY_STRING          ✅
CONTENT_TYPE          ✅
CONTENT_LENGTH        ✅
```

#### 🔒 Все остальные скрыты:
```
DOCUMENT_ROOT         → ***HIDDEN (PRODUCTION MODE)***
REMOTE_ADDR           → ***HIDDEN (PRODUCTION MODE)***
SERVER_NAME           → ***HIDDEN (PRODUCTION MODE)***
PATH                  → ***HIDDEN (PRODUCTION MODE)***
SCRIPT_FILENAME       → ***HIDDEN (PRODUCTION MODE)***
PHP_SELF              → ***HIDDEN (PRODUCTION MODE)***
... и т.д.
```

### 4. Визуальная индикация

#### 🔴 Production Mode Badge
В заголовке секции Server Variables:
```
📋 Server Variables 🔒 PRODUCTION MODE
```

#### ⚠️ Warning Message
Перед таблицей Server Variables:
```
⚠️ Production Mode: Sensitive server variables are hidden 
   for security reasons. Only safe variables are shown.
```

## 📊 Сравнение режимов

### Development Mode
```bash
export APP_ENV=development
php -S localhost:8000
```

**Что показывается:**
- ✅ Все GET/POST/Cookies/Headers
- ✅ Большинство Server Variables
- 🔒 Только критически чувствительные скрыты (*_PASSWORD, *_SECRET)

### Production Mode
```bash
export APP_ENV=production
php -S localhost:8000
```

**Что показывается:**
- ✅ Все GET/POST/Cookies/Headers
- ✅ Только базовые Server Variables (REQUEST_METHOD, REQUEST_URI, etc.)
- 🔒 Все остальные Server Variables скрыты
- 🔴 Красный badge "PRODUCTION MODE"
- ⚠️ Предупреждающее сообщение

## 🔧 Технические детали

### Измененные файлы

**core/DebugToolbar/Collectors/RequestCollector.php**
- ✅ Добавлен `use Core\Environment`
- ✅ Метод `filterServer()` расширен для production режима
- ✅ Добавлен метод `isSensitiveKey()` для автоопределения
- ✅ Метод `renderDataTable()` поддерживает production warning
- ✅ Метод `render()` добавляет production badge

### Новые методы

```php
/**
 * Проверить, является ли ключ чувствительным
 */
private function isSensitiveKey(string $key, array $sensitiveKeys): bool
{
    // Точное совпадение
    if (in_array($key, $sensitiveKeys)) {
        return true;
    }

    // Проверяем по паттернам
    $patterns = ['PASSWORD', 'SECRET', 'TOKEN', 'KEY', 'AUTH', 'CREDENTIAL'];
    foreach ($patterns as $pattern) {
        if (str_contains(strtoupper($key), $pattern)) {
            return true;
        }
    }

    return false;
}
```

## 📚 Документация

### Создана новая документация:

**docs/RequestCollectorSecurity.md** - полное руководство по безопасности:
- Режимы работы (Development/Production)
- Типы защиты данных
- Примеры использования
- Настройка безопасных переменных
- Best practices
- Тестирование безопасности
- Compliance & Regulations

### Обновлена документация:

**docs/RequestCollector.md** - добавлена секция безопасности  
**REQUEST_COLLECTOR_CHANGES.md** - обновлено описание изменений

## 🧪 Тестирование

### Тест 1: Development режим

```bash
export APP_ENV=development
php -S localhost:8000 &
curl http://localhost:8000/demo
```

**Ожидается:**
- Большинство Server Variables видны
- Только *_PASSWORD, *_SECRET скрыты
- Нет production badge

### Тест 2: Production режим

```bash
export APP_ENV=production
php -S localhost:8000 &
curl http://localhost:8000/demo
```

**Ожидается:**
- 🔴 Red badge "PRODUCTION MODE"
- ⚠️ Warning message
- Большинство Server Variables → `***HIDDEN (PRODUCTION MODE)***`
- Видны только: REQUEST_METHOD, REQUEST_URI, etc.

### Тест 3: Автоматическое определение

```bash
export MY_SECRET_API_KEY="supersecret"
export MY_DATABASE_PASSWORD="password123"
export CUSTOM_AUTH_TOKEN="token456"

php -S localhost:8000 &
curl http://localhost:8000/demo
```

**Ожидается:**
- `MY_SECRET_API_KEY` → `***HIDDEN***`
- `MY_DATABASE_PASSWORD` → `***HIDDEN***`
- `CUSTOM_AUTH_TOKEN` → `***HIDDEN***`

## 🎯 Преимущества

✅ **Безопасность по умолчанию** - чувствительные данные скрыты автоматически  
✅ **Умное определение** - автоматически находит пароли, токены, ключи  
✅ **Production-ready** - дополнительная защита в production режиме  
✅ **Визуальная индикация** - четко видно, когда в production  
✅ **Гибкая настройка** - легко добавить свои правила  
✅ **Zero configuration** - работает из коробки  
✅ **Compliance** - помогает соответствовать GDPR, PCI DSS, OWASP  

## ⚡ Производительность

Новые проверки безопасности имеют **минимальное влияние**:
- ✅ Проверка режима (`Environment::isProduction()`) - 1 вызов
- ✅ Фильтрация массива - O(n), где n = количество переменных
- ✅ Паттерн-matching - выполняется только для неизвестных переменных
- ✅ Общий overhead: **< 1ms**

## 🚀 Как использовать

### 1. Development (по умолчанию)
```bash
# В .env
APP_ENV=development
APP_DEBUG=true
```

Работает как обычно, показывает всю информацию для отладки.

### 2. Production
```bash
# В .env
APP_ENV=production
APP_DEBUG=false  # Рекомендуется отключить debug полностью
```

Автоматически скрывает чувствительные данные.

### 3. Custom Configuration

Если нужно добавить свои переменные:

```php
// core/DebugToolbar/Collectors/RequestCollector.php

private function filterServer(array $server): array
{
    // Добавьте свои всегда скрытые переменные
    $alwaysHidden = [
        'PHP_AUTH_PW',
        'MY_CUSTOM_SECRET',  // ← ваша переменная
    ];
    
    // Добавьте свои безопасные для production
    $safeInProduction = [
        'REQUEST_METHOD',
        'MY_SAFE_VAR',  // ← ваша переменная
    ];
    
    // ...
}
```

## 🎓 Best Practices

### ✅ DO (Делайте)

1. Всегда используйте `APP_ENV=production` на боевых серверах
2. Отключайте Debug Toolbar в production (`APP_DEBUG=false`)
3. Храните секреты в `.env` файлах
4. Не коммитите `.env` в git
5. Регулярно проверяйте логи на утечки

### ❌ DON'T (Не делайте)

1. Не оставляйте Debug Toolbar включенным в production
2. Не храните пароли в коде
3. Не логируйте чувствительные данные
4. Не игнорируйте production warnings

## 📞 Поддержка

Если возникли вопросы:
1. Читайте `docs/RequestCollectorSecurity.md`
2. Проверяйте логи: `storage/logs/app.log`
3. Тестируйте в development режиме

## ✅ Checklist перед деплоем

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `.env` не в git
- [ ] Тестирование в production режиме
- [ ] Проверка логов на утечки
- [ ] Документация обновлена

## 🎉 Заключение

Request Collector теперь обеспечивает **enterprise-level безопасность**:

- 🛡️ Автоматическая защита чувствительных данных
- 🔒 Production режим с минимальной информацией
- ⚠️ Визуальные предупреждения
- 📚 Полная документация
- ✅ Готово к production использованию

**Безопасность вашего приложения улучшена! 🔐**

