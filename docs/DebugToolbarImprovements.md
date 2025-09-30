# Debug Toolbar - Идеи для улучшения

## 📊 Текущее состояние

### Существующие коллекторы:
1. ✅ **OverviewCollector** - общая статистика
2. ✅ **RequestCollector** - данные запроса (GET, POST, Cookies, Headers)
3. ✅ **QueriesCollector** - SQL запросы
4. ✅ **TimersCollector** - измерение времени
5. ✅ **MemoryCollector** - использование памяти
6. ✅ **DumpsCollector** - debug dumps
7. ✅ **ContextsCollector** - контексты выполнения
8. ✅ **CacheCollector** - операции с кешем

## 🚀 Предлагаемые улучшения

### A. Новые коллекторы (High Priority)

#### 1. 🛣️ Routes Collector
**Зачем:** Показывает все зарегистрированные маршруты и помогает понять структуру приложения

**Что показывать:**
- Список всех маршрутов (GET, POST, etc.)
- Текущий активный маршрут (подсветить)
- Контроллеры и методы
- Параметры маршрутов
- Паттерны и regex

**Пример:**
```
🛣️ Routes (15)

✅ ACTIVE: GET /demo → RequestDemoController::demo

All Routes:
┌────────┬─────────────────┬──────────────────────────────┐
│ Method │ URI             │ Action                       │
├────────┼─────────────────┼──────────────────────────────┤
│ GET    │ /               │ HomeController::index        │
│ GET    │ /user/{name}    │ HomeController::name         │
│ ANY    │ /demo           │ RequestDemoController::demo  │
└────────┴─────────────────┴──────────────────────────────┘
```

**Приоритет:** ⭐⭐⭐⭐⭐

#### 2. 📤 Response Collector
**Зачем:** Показывает информацию об ответе сервера

**Что показывать:**
- HTTP Status Code (200, 404, 500, etc.)
- Response Headers (отправленные)
- Content-Type
- Content-Length
- Response time
- Cookies (set by server)

**Пример:**
```
📤 Response

Status: 200 OK
Content-Type: text/html; charset=UTF-8
Content-Length: 15.4 KB
Response Time: 45.3 ms

Headers Sent:
- Content-Type: text/html
- Set-Cookie: session_id=abc123
- X-Powered-By: TorrentPier
```

**Приоритет:** ⭐⭐⭐⭐⭐

#### 3. 🔧 Config Collector
**Зачем:** Показывает текущую конфигурацию приложения

**Что показывать:**
- Все загруженные config файлы
- Значения конфигураций
- Environment variables (с учетом безопасности)
- PHP settings (важные)

**Пример:**
```
🔧 Configuration

App:
- APP_ENV: development
- APP_DEBUG: true
- APP_URL: http://localhost

Database:
- DB_CONNECTION: mysql
- DB_HOST: localhost
- DB_PORT: 3306
- DB_PASSWORD: ***HIDDEN***

PHP Settings:
- memory_limit: 128M
- max_execution_time: 30
- upload_max_filesize: 2M
```

**Приоритет:** ⭐⭐⭐⭐

#### 4. 📝 Logs Collector
**Зачем:** Показывает логи, созданные во время текущего запроса

**Что показывать:**
- Все log записи текущего запроса
- Уровни (debug, info, warning, error)
- Timestamp
- Контекст
- Фильтрация по уровню

**Пример:**
```
📝 Logs (12)

[ERROR] 10:30:45 - Database connection failed
Context: {host: localhost, port: 3306}

[WARNING] 10:30:45 - Slow query detected (2.5s)
Query: SELECT * FROM users WHERE active = 1

[INFO] 10:30:46 - User logged in
User ID: 123
```

**Приоритет:** ⭐⭐⭐⭐

#### 5. 🎨 Views Collector
**Зачем:** Показывает какие шаблоны были отрендерены

**Что показывать:**
- Список отрендеренных шаблонов
- Время рендеринга каждого
- Переданные данные
- Undefined переменные (если есть)
- Вложенность шаблонов

**Пример:**
```
🎨 Views (3)

welcome.tpl (15.2 ms)
├─ Data: {title, message, users[3]}
├─ Undefined: none
└─ Size: 2.4 KB

header.tpl (2.1 ms)
footer.tpl (1.5 ms)

Total rendering time: 18.8 ms
```

**Приоритет:** ⭐⭐⭐⭐

#### 6. 🔐 Session Collector
**Зачем:** Показывает данные сессии пользователя

**Что показывать:**
- Session ID
- Session data
- Session lifetime
- Flash messages
- CSRF token

**Пример:**
```
🔐 Session

Session ID: abc123xyz
Started: 2025-09-30 10:25:30
Lifetime: 7200s (2h)

Data:
- user_id: 123
- username: john
- last_activity: 10:30:45

Flash Messages:
- success: "Profile updated successfully"
```

**Приоритет:** ⭐⭐⭐⭐

#### 7. 🌍 Environment Collector
**Зачем:** Показывает информацию об окружении

**Что показывать:**
- PHP version
- Extensions loaded
- OS info
- Server info
- Framework version

**Пример:**
```
🌍 Environment

PHP: 8.3.0
OS: Windows 10
Server: Built-in server
Framework: TorrentPier 2.0

Extensions:
✅ mysqli, ✅ pdo, ✅ mbstring, ✅ json,
✅ curl, ✅ openssl, ✅ zip
```

**Приоритет:** ⭐⭐⭐

#### 8. ⚡ Performance Collector
**Зачем:** Детальное профилирование производительности

**Что показывать:**
- Waterfall chart (timeline)
- Function calls profiling
- Bottlenecks
- CPU usage
- I/O operations

**Пример:**
```
⚡ Performance Timeline

0ms ━━━━━━━━━━━━━━━━━━━━ 50ms
│
├─ Bootstrap (5ms) ████
├─ Route matching (2ms) ██
├─ Controller (25ms) ██████████████
│  ├─ Database (15ms) ████████
│  └─ Rendering (10ms) ██████
└─ Shutdown (3ms) ██

Total: 45ms
```

**Приоритет:** ⭐⭐⭐

#### 9. 🔒 Security Collector
**Зачем:** Показывает информацию о безопасности

**Что показывать:**
- HTTPS status
- CSRF protection
- XSS checks
- SQL injection attempts
- Security headers

**Пример:**
```
🔒 Security

HTTPS: ❌ Not secure (use HTTPS in production)
CSRF: ✅ Token valid
XSS: ✅ No threats detected

Security Headers:
✅ X-Content-Type-Options: nosniff
✅ X-Frame-Options: SAMEORIGIN
❌ Strict-Transport-Security: missing
❌ Content-Security-Policy: missing
```

**Приоритет:** ⭐⭐⭐

#### 10. 📨 Events Collector
**Зачем:** Если есть event system - показывает события

**Что показывать:**
- Fired events
- Event listeners
- Execution time
- Parameters

**Приоритет:** ⭐⭐

### B. Улучшения UI/UX (High Priority)

#### 1. 🔍 Поиск по данным
**Зачем:** Быстро находить нужную информацию

**Функции:**
- Глобальный поиск по всем коллекторам
- Поиск по конкретному коллектору
- Regex support
- Highlight результатов

**Приоритет:** ⭐⭐⭐⭐⭐

#### 2. 📥 Экспорт данных
**Зачем:** Сохранить данные для анализа или bug report

**Форматы:**
- JSON
- HTML
- Text
- CSV (для таблиц)

**Приоритет:** ⭐⭐⭐⭐

#### 3. 🕐 История запросов
**Зачем:** Смотреть предыдущие запросы без перезагрузки

**Функции:**
- Хранить последние N запросов
- Навигация между запросами
- Сравнение запросов
- Persistence в localStorage

**Пример:**
```
History: [< Prev] Request #15 of 20 [Next >]

Current: GET /demo (45ms, 2MB)
Previous: GET / (32ms, 1.5MB)
```

**Приоритет:** ⭐⭐⭐⭐⭐

#### 4. ⌨️ Keyboard Shortcuts
**Зачем:** Быстрая навигация

**Shortcuts:**
- `Ctrl+D` - Toggle toolbar
- `Ctrl+Shift+D` - Toggle collapse
- `1-9` - Switch tabs
- `/` - Focus search
- `Esc` - Close search/panels

**Приоритет:** ⭐⭐⭐

#### 5. 🎨 Темы оформления
**Зачем:** Удобство для разных предпочтений

**Темы:**
- Light (по умолчанию)
- Dark
- High Contrast
- Customizable colors

**Приоритет:** ⭐⭐⭐

#### 6. 📊 Ajax Request Tracking
**Зачем:** Отслеживать AJAX запросы на SPA

**Функции:**
- Перехват fetch/XMLHttpRequest
- Показывать список всех AJAX запросов
- Timing и размеры
- Фильтрация

**Пример:**
```
📡 Ajax Requests (5)

1. GET /api/users (125ms) → 200 OK
2. POST /api/login (245ms) → 200 OK
3. GET /api/profile (85ms) → 200 OK
```

**Приоритет:** ⭐⭐⭐⭐

#### 7. 📌 Customizable Layout
**Зачем:** Каждый разработчик предпочитает свой layout

**Опции:**
- Position (top/bottom/left/right)
- Size (compact/normal/expanded)
- Visible tabs
- Tab order
- Pin/unpin tabs

**Приоритет:** ⭐⭐⭐

#### 8. 🔔 Notifications & Alerts
**Зачем:** Привлечь внимание к проблемам

**Типы:**
- Slow queries (> 100ms)
- High memory usage (> 75%)
- Errors and warnings
- N+1 query problems
- Deprecated code usage

**Пример:**
```
🔴 3 Issues Found

⚠️ Slow Query: 2.5s
⚠️ Memory: 85% (110MB/128MB)
⚠️ 5 Deprecated functions
```

**Приоритет:** ⭐⭐⭐⭐

### C. Advanced Features (Medium Priority)

#### 1. 📊 Charts & Graphs
- Memory usage over time
- Response time trends
- Query distribution
- Cache hit/miss ratio

#### 2. 🔗 Deep Links
- Share link to specific tab/data
- Permalinks to debug session

#### 3. 🧪 Comparison Mode
- Compare two requests side-by-side
- Diff показать изменения

#### 4. 📸 Screenshots
- Capture full debug state
- Share with team

#### 5. 🤖 AI Suggestions
- Detect performance issues
- Suggest optimizations
- Best practices warnings

## 🎯 Рекомендуемый план внедрения

### Phase 1 (Quick Wins)
1. ✅ Routes Collector - просто и полезно
2. ✅ Response Collector - важно для debugging
3. ✅ Search functionality - сильно повышает UX

### Phase 2 (Core Features)
4. Config Collector
5. Logs Collector
6. Views Collector
7. History requests
8. Export functionality

### Phase 3 (Advanced)
9. Ajax tracking
10. Performance waterfall
11. Keyboard shortcuts
12. Session Collector

### Phase 4 (Nice to Have)
13. Themes
14. Charts
15. AI suggestions
16. Comparison mode

## 💡 Примеры из других фреймворков

### Laravel Debugbar
✅ Routes  
✅ Views  
✅ Queries  
✅ Timeline  
✅ Exceptions  

### Symfony Web Profiler
✅ Request/Response  
✅ Performance  
✅ Security  
✅ Events  
✅ Emails  

### Django Debug Toolbar
✅ SQL  
✅ Templates  
✅ Cache  
✅ Signals  
✅ Static files  

### Что взять лучшее:
- Laravel: Timeline visualization
- Symfony: Separate profiler page
- Django: SQL explain

## 🎬 Заключение

Debug Toolbar уже имеет хорошую базу! Следующие шаги:

**Must Have (немедленно):**
1. 🛣️ Routes Collector
2. 📤 Response Collector
3. 🔍 Search

**Should Have (скоро):**
4. 🕐 History
5. 📥 Export
6. 📝 Logs Collector

**Nice to Have (позже):**
7. Themes, Charts, AI suggestions

Хотите начнем с Routes Collector? Это будет очень полезно! 🚀

