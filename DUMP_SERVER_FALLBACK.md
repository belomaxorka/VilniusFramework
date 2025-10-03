# 🛡️ Dump Server Fallback - Автоматическое логирование

## Что это?

Теперь если **Dump Server недоступен**, данные **автоматически логируются** в файл вместо того чтобы теряться!

---

## 🎯 Как это работает

### Dump Server запущен ✅
```php
server_dump($user, 'User Data');
```
→ Данные отправляются в **Dump Server** (real-time в консоли)

### Dump Server остановлен ⚠️
```php
server_dump($user, 'User Data');
```
→ Данные **автоматически логируются** в `storage/logs/dumps.log`  
→ Предупреждение добавляется в **Logger** (появляется в Debug Toolbar!)  
→ В CLI выводится предупреждение в STDERR

**Вы ничего не теряете!** 🎉

---

## 🎨 Debug Toolbar интеграция

Когда Dump Server недоступен, каждый `server_dump()` создаёт запись в Logger с уровнем **WARNING**.

### В Debug Toolbar вы увидите:

**Вкладка "Logs":**
```
[WARNING] Dump Server unavailable, data logged to file
  ├─ label: User Data
  ├─ type: array
  ├─ file: app/Controllers/HomeController.php
  ├─ line: 25
  └─ log_file: storage/logs/dumps.log
```

Это позволяет:
- ✅ Видеть все проблемы с Dump Server в одном месте
- ✅ Знать откуда были вызваны dumps
- ✅ Быстро найти лог-файл для просмотра данных

---

## 📋 Формат лога

Файл: `storage/logs/dumps.log`

```
────────────────────────────────────────────────────────────────────────────────
[2025-10-03 13:15:42] 📝 User Data | 🔍 Type: array | 📍 app/Controllers/HomeController.php:25
────────────────────────────────────────────────────────────────────────────────
array(
  "id" => 123,
  "name" => "John Doe",
  "email" => "john@example.com",
  "roles" => array(
    0 => "admin",
    1 => "editor",
  ),
)

────────────────────────────────────────────────────────────────────────────────
[2025-10-03 13:15:43] 📝 Config | 🔍 Type: array | 📍 app/Controllers/HomeController.php:30
────────────────────────────────────────────────────────────────────────────────
...
```

**Содержит:**
- ✅ Timestamp
- ✅ Label
- ✅ Тип данных
- ✅ Файл и строка
- ✅ Полные данные

---

## 🎮 Команды для работы с логами

### Просмотреть все логи
```bash
php vilnius dump:log
```

### Последние 10 записей
```bash
php vilnius dump:log --tail=10
php vilnius dump:log -n 10
```

### Очистить логи
```bash
php vilnius dump:log --clear
php vilnius dump:log -c
```

---

## 🧪 Тестирование

### Тест 1: CLI скрипт

#### Шаг 1: Убедитесь что Dump Server остановлен

Если он запущен - нажмите `Ctrl+C`

#### Шаг 2: Запустите тест

```bash
php test-dump-fallback.php
```

**Вывод:**
```
🧪 Тест Fallback механизма Dump Server
────────────────────────────────────────────────────────

✅ Dump Server недоступен - fallback активирован!

📤 Отправка данных...

⚠️  Dump Server unavailable, logged to: storage/logs/dumps.log
⚠️  Dump Server unavailable, logged to: storage/logs/dumps.log
...

✅ Данные отправлены!

📋 Данные сохранены в лог-файл:
   C:\OSPanel\home\torrentpier\public\storage\logs\dumps.log

📖 Просмотреть логи:
   php vilnius dump:log              # Весь лог
   php vilnius dump:log --tail=5     # Последние 5 записей
   php vilnius dump:log --clear      # Очистить лог
```

#### Шаг 3: Просмотрите логи

```bash
php vilnius dump:log --tail=5
```

Вы увидите все dumps которые были отправлены!

---

### Тест 2: Web + Debug Toolbar

#### Шаг 1: Запустите веб-сервер

```bash
php -S localhost:8000 -t public
```

#### Шаг 2: Откройте тестовую страницу

Откройте в браузере: `http://localhost:8000/test-dump-debug-toolbar.php`

#### Шаг 3: Проверьте Debug Toolbar

1. Откройте **Debug Toolbar** (внизу страницы)
2. Перейдите на вкладку **"Logs"**
3. Вы увидите записи уровня **WARNING**:
   ```
   [WARNING] Dump Server unavailable, data logged to file
   ```
4. Каждая запись содержит контекст с информацией о dump'е

#### Что вы увидите:

**На странице:**
- ✅ Подтверждение что dumps отправлены
- ℹ️ Информация о fallback механизме
- 📋 Инструкции по просмотру

**В Debug Toolbar (вкладка Logs):**
- ⚠️ 3 предупреждения о недоступности Dump Server
- 📝 Детальная информация о каждом dump'е
- 🔍 Путь к лог-файлу

---

## 💡 Рабочий процесс

### Development (с Dump Server)

**Terminal 1:**
```bash
php vilnius dump-server
```

**Terminal 2:**
```bash
php -S localhost:8000 -t public
```

Все `server_dump()` идут в **Terminal 1** (real-time).

---

### Development (без Dump Server)

**Terminal 1:**
```bash
php -S localhost:8000 -t public
```

Все `server_dump()` идут в **`storage/logs/dumps.log`**.

Просмотр:
```bash
php vilnius dump:log --tail=10
```

---

### Production

В production `APP_DEBUG=false`, поэтому `server_dump()` **ничего не делает**.

---

## 🎨 CLI уведомления

### В CLI скриптах

Когда вы запускаете PHP CLI скрипт и dump server недоступен:

```bash
php some-script.php
⚠️  Dump Server unavailable, logged to: storage/logs/dumps.log
```

### В Web запросах

Логирование происходит **тихо**, без вывода в браузер.

---

## 🔧 Конфигурация

### Отключить fallback логирование

Если не хотите логировать в файл:

```php
// В core/DumpClient.php::logToFile()
// Закомментируйте содержимое метода или добавьте проверку
```

### Изменить путь к логу

```php
// В core/DumpClient.php::logToFile()
$logFile = $logDir . '/custom-dumps.log';
```

### Ротация логов

Для ротации больших логов добавьте в cron:

```bash
# Каждый день в полночь
0 0 * * * php /path/to/project/vilnius dump:log --clear
```

Или создайте команду для ротации:

```bash
php vilnius dump:log --clear  # Ручная очистка
```

---

## 📊 Статистика

### Посмотреть размер лога

```bash
php vilnius dump:log
```

Покажет:
```
Dump Server Logs:
File: C:\OSPanel\home\torrentpier\public\storage\logs\dumps.log
Size: 15.42 KB

[содержимое]
```

---

## 🎯 Сценарии использования

### 1. Забыли запустить Dump Server

```php
// В коде
server_dump($user, 'Debug user');
```

**Без fallback:** ❌ Данные потеряны  
**С fallback:** ✅ Данные в логе

---

### 2. Dump Server упал

```php
// Много dumps в цикле
foreach ($users as $user) {
    server_dump($user, "User {$user['id']}");
}
```

Если сервер упадёт во время выполнения - остальные dumps сохранятся в лог!

---

### 3. Отладка без второго терминала

Не хотите держать открытым второй терминал с dump server?

Просто не запускайте его - всё пойдёт в лог:

```bash
# Разработка
php -S localhost:8000 -t public

# Отладка
php vilnius dump:log --tail=20
```

---

### 4. Сохранение истории

Логи остаются в файле, можно вернуться позже:

```bash
# Утром
server_dump($data1);
server_dump($data2);

# Вечером
php vilnius dump:log  # Всё ещё там!
```

---

## ⚙️ Технические детали

### Приоритет

1. **Попытка отправить в Dump Server** (TCP 127.0.0.1:9912)
2. **Если не удалось** → логирование в файл
3. **CLI:** вывод предупреждения в STDERR
4. **Web:** тихое логирование

### Производительность

- Fallback не блокирует выполнение
- Использует `FILE_APPEND | LOCK_EX` для безопасной записи
- Ошибки записи игнорируются (graceful degradation)

### Безопасность

- Логи создаются в `storage/logs/` (вне `public/`)
- Права доступа: `0755` для директории
- В production (`APP_DEBUG=false`) ничего не логируется

---

## 🆚 Сравнение

| Ситуация | Без Fallback | С Fallback |
|----------|---------------|------------|
| Dump Server запущен | ✅ Real-time | ✅ Real-time |
| Dump Server остановлен | ❌ Данные потеряны | ✅ Логируются в файл |
| Сервер упал | ❌ Данные потеряны | ✅ Остальные в файл |
| Production | ❌ Не работает | ✅ Не работает (правильно) |

---

## 🎓 Best Practices

### ✅ ДА:

```php
// Real-time отладка - запустите dump server
server_dump($data);

// Потом просмотрите что пропустили
php vilnius dump:log --tail=50
```

### ✅ ДА:

```php
// Отладка без лишних терминалов
// Просто не запускайте dump server
server_dump($data);

// Периодически смотрите лог
php vilnius dump:log
```

### ✅ ДА:

```php
// Очищайте логи регулярно
php vilnius dump:log --clear
```

### ❌ НЕТ:

```php
// Не храните логи слишком долго (могут разрастись)
// Очищайте их периодически
```

---

## 📦 Итого

### Что добавлено:

1. ✅ **Автоматический fallback** - логирование в файл
2. ✅ **Команда `dump:log`** - просмотр логов
3. ✅ **CLI предупреждения** - видно что сервер недоступен
4. ✅ **Graceful degradation** - ошибки не ломают приложение

### Теперь у вас:

- 🛡️ **Надёжность** - данные не теряются
- 📋 **История** - можно вернуться к старым dumps
- 🚀 **Гибкость** - работает с сервером и без
- 🎯 **Удобство** - просмотр через CLI

---

**Больше никаких потерянных dumps! 🎉**

