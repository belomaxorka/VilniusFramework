# 🎨 Dump Server + Debug Toolbar Integration

## Что добавлено

Теперь когда **Dump Server недоступен**, предупреждения появляются в **Debug Toolbar**!

---

## 🎯 Как это работает

```php
// В вашем коде
server_dump($user, 'User Data');
```

### Если Dump Server запущен ✅
→ Данные идут в сервер (real-time)  
→ Debug Toolbar: ничего

### Если Dump Server остановлен ⚠️
→ Данные логируются в `storage/logs/dumps.log`  
→ **Debug Toolbar: появляется WARNING!** 🔔

---

## 📊 В Debug Toolbar

**Вкладка "Logs"** покажет:

```
[WARNING] Dump Server unavailable, data logged to file
  Context:
    ├─ label: User Data
    ├─ type: array
    ├─ file: app/Controllers/HomeController.php
    ├─ line: 25
    └─ log_file: storage/logs/dumps.log
```

**Преимущества:**
- ✅ Сразу видно что dump server не работает
- ✅ Знаете откуда вызван dump
- ✅ Есть путь к лог-файлу для просмотра данных
- ✅ Не нужно смотреть в терминал

---

## 🧪 Быстрый тест

### 1. Остановите Dump Server (если запущен)

```bash
Ctrl+C в окне с php vilnius dump-server
```

### 2. Запустите веб-сервер

```bash
php -S localhost:8000 -t public
```

### 3. Откройте тест

```
http://localhost:8000/test-dump-debug-toolbar.php
```

### 4. Откройте Debug Toolbar

Внизу страницы → вкладка **"Logs"** → увидите **WARNING**!

---

## 💻 Пример в контроллере

```php
<?php

namespace App\Controllers;

use Core\Response;

class UserController extends Controller
{
    public function show(int $id): Response
    {
        $user = User::find($id);
        
        // Debug без влияния на вывод
        server_dump($user, 'User Data');
        
        $permissions = $this->getPermissions($id);
        server_dump($permissions, 'Permissions');
        
        // Страница работает нормально
        return $this->view('user.show', compact('user'));
    }
}
```

**Если dump server не запущен:**
- Страница отображается нормально ✅
- Данные в `storage/logs/dumps.log` ✅
- В Debug Toolbar 2 предупреждения ⚠️

---

## 🔄 Workflow

### Development с Dump Server

**Terminal 1:**
```bash
php vilnius dump-server
```

**Terminal 2:**
```bash
php -S localhost:8000 -t public
```

**Результат:**
- Dumps в real-time (Terminal 1)
- Debug Toolbar: чистый

---

### Development без Dump Server

**Terminal 1:**
```bash
php -S localhost:8000 -t public
```

**Результат:**
- Dumps в `storage/logs/dumps.log`
- **Debug Toolbar: WARNING** ⚠️

**Просмотр логов:**
```bash
php vilnius dump:log --tail=10
```

---

## 🎨 Скриншот Debug Toolbar

```
╔════════════════════════════════════════════════╗
║  Debug Toolbar                                 ║
╠════════════════════════════════════════════════╣
║  Logs (5)                                      ║
║  ─────────────────────────────────────────────║
║  [INFO] Application started                   ║
║  [DEBUG] Route matched: user.show             ║
║  ⚠️ [WARNING] Dump Server unavailable         ║
║     label: User Data                           ║
║     file: app/Controllers/UserController.php   ║
║  ⚠️ [WARNING] Dump Server unavailable         ║
║     label: Permissions                         ║
║     file: app/Controllers/UserController.php   ║
║  [INFO] Response sent                          ║
╚════════════════════════════════════════════════╝
```

---

## ✅ Итого

### Добавлено:

1. ✅ **Автоматическое логирование** через `Logger::warning()`
2. ✅ **Видимость в Debug Toolbar** (вкладка Logs)
3. ✅ **Детальный контекст** в каждом предупреждении
4. ✅ **Тестовая страница** для проверки

### Теперь вы:

- 🔔 **Сразу видите** когда dump server не работает
- 📍 **Знаете откуда** был вызван dump
- 📂 **Имеете путь** к лог-файлу
- 🚀 **Не теряете** отладочную информацию

---

## 📚 Связанные документы

- [DUMP_SERVER_FALLBACK.md](DUMP_SERVER_FALLBACK.md) - Полная документация fallback механизма
- [DUMP_SERVER_GUIDE.md](DUMP_SERVER_GUIDE.md) - Руководство по Dump Server
- [docs/Logger.md](docs/Logger.md) - Документация Logger

---

## 🎓 Best Practices

### ✅ Рекомендуется:

```php
// Использовать server_dump() для отладки
server_dump($data, 'Descriptive Label');

// Периодически проверять Debug Toolbar
// на наличие WARNING'ов
```

### ⚠️ Обратите внимание:

- Warning появляется **только если dump server недоступен**
- В production (`APP_DEBUG=false`) ничего не логируется
- Warnings не влияют на работу приложения

### 💡 Совет:

Держите dump server запущенным для real-time отладки:

```bash
php vilnius dump-server
```

Если забыли запустить - увидите в Debug Toolbar! 🔔

---

**Made with ❤️ for Vilnius Framework**

