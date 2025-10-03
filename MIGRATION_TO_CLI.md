# 🔄 Миграция из bin/ в Vilnius CLI

## ✅ Теперь всё через CLI!

Все скрипты из папки `bin/` были перенесены в команды Vilnius CLI.

---

## 📦 Старые скрипты → Новые команды

### Route Cache Management

#### ❌ Старый способ:
```bash
php bin/route-cache.php cache
php bin/route-cache.php clear
php bin/route-cache.php status
```

#### ✅ Новый способ:
```bash
php vilnius route:cache    # Создать кэш роутов
php vilnius route:clear    # Очистить кэш роутов
php vilnius route:list     # Список роутов (+ информация о кэше)
```

---

### Dump Server

#### ❌ Старый способ:
```bash
php bin/dump-server.php
php bin/dump-server.php --host=127.0.0.1 --port=9912
```

#### ✅ Новый способ:
```bash
php vilnius dump-server
php vilnius dump-server --host=127.0.0.1 --port=9912
```

---

## 🗑️ Удаление папки bin/

Теперь папка `bin/` больше не нужна и может быть удалена:

```bash
# Windows PowerShell
Remove-Item -Recurse -Force bin/

# Linux/Mac
rm -rf bin/
```

Или просто удалите папку через проводник/файловый менеджер.

---

## 🎯 Преимущества нового подхода

### 1. **Единая точка входа**
Все команды через `php vilnius` вместо разных скриптов:
```bash
php vilnius list              # Все команды в одном месте
```

### 2. **Больше возможностей**
Теперь доступно **13+ команд** вместо 2 скриптов:
- ✅ Миграции (6 команд)
- ✅ Генераторы (2 команды)
- ✅ Роуты и кэш (4 команды)
- ✅ Debug утилиты (1 команда)

### 3. **Лучший UX**
- Цветной вывод
- Таблицы
- Прогресс-бары
- Интерактивные промпты
- Подробная help информация

### 4. **Консистентный интерфейс**
Все команды следуют единому формату:
```bash
php vilnius [command] [arguments] [--options]
php vilnius [command] --help
```

### 5. **Расширяемость**
Легко добавить новую команду:
```php
// core/Console/Commands/MyCommand.php
class MyCommand extends Command
{
    protected string $signature = 'my:command';
    protected string $description = 'My custom command';
    
    public function handle(): int
    {
        $this->success('Done!');
        return 0;
    }
}

// vilnius
$app->registerCommands([
    \Core\Console\Commands\MyCommand::class,
]);
```

---

## 📚 Полная документация

Все команды описаны в:
- **[docs/ConsoleCommands.md](docs/ConsoleCommands.md)** - Cheat Sheet всех команд
- **[docs/Console.md](docs/Console.md)** - Полная документация CLI
- **[README.md](README.md)** - Quick Start

---

## 🔍 Что изменилось внутри?

### Route Cache Command
```php
// Было: bin/route-cache.php (160 строк)
// Стало: core/Console/Commands/RouteCacheCommand.php (65 строк)
//       + core/Console/Commands/RouteClearCommand.php (25 строк)
```

**Улучшения:**
- ✅ Лучшая обработка ошибок
- ✅ Использует Container и DI
- ✅ Более читаемый код
- ✅ Разделение ответственности

### Dump Server Command
```php
// Было: bin/dump-server.php (62 строки)
// Стало: core/Console/Commands/DumpServerCommand.php (50 строк)
```

**Улучшения:**
- ✅ Интеграция с Console Output
- ✅ Поддержка опций через --host и --port
- ✅ Лучшее форматирование вывода
- ✅ Обработка исключений

---

## 🎊 Заключение

Папка `bin/` была временным решением для ранних версий фреймворка.

Теперь у нас есть **полноценный CLI инструмент** как у Laravel Artisan! 🚀

**Вы можете безопасно удалить папку `bin/`** - всё теперь через `php vilnius`!

---

## 💡 Quick Start

Попробуйте новые команды:

```bash
# Посмотреть все команды
php vilnius list

# Закэшировать роуты для продакшена
php vilnius route:cache

# Посмотреть все роуты
php vilnius route:list

# Запустить dump server
php vilnius dump-server

# Помощь по любой команде
php vilnius route:cache --help
```

---

**Made with ❤️ for Vilnius Framework**

