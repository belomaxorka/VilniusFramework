# 🎉 Vilnius CLI - Полный список команд

Все команды доступны через `php vilnius [command]`

---

## 📦 Всего команд: 13

### 🗄️ Database Migrations (6 команд)

```bash
php vilnius migrate                # Выполнить миграции
php vilnius migrate:status         # Показать статус миграций
php vilnius migrate:rollback       # Откатить последнюю миграцию
php vilnius migrate:reset          # Откатить все миграции
php vilnius migrate:refresh        # Reset + Migrate (пересоздать всё)
php vilnius make:migration <name>  # Создать новую миграцию
```

**Примеры:**
```bash
php vilnius make:migration create_posts_table
php vilnius migrate
php vilnius migrate:status
php vilnius migrate:rollback --step=2
```

---

### 🏗️ Generators (2 команды)

```bash
php vilnius make:controller <name>  # Создать контроллер
php vilnius make:model <name>       # Создать модель
```

**Опции:**
```bash
# Контроллер с CRUD методами
php vilnius make:controller PostController --resource
php vilnius make:controller PostController -r

# Модель + миграция одной командой
php vilnius make:model Post --migration
php vilnius make:model Post -m
```

---

### 🛣️ Routes (3 команды)

```bash
php vilnius route:list   # Показать все роуты
php vilnius route:cache  # Создать кэш роутов (production)
php vilnius route:clear  # Очистить кэш роутов
```

**Когда использовать:**
- `route:cache` - перед деплоем на продакшн
- `route:clear` - после изменения роутов в dev
- `route:list` - для проверки зарегистрированных роутов

---

### 💾 Cache (1 команда)

```bash
php vilnius cache:clear  # Очистить весь кэш
```

**Очищает:**
- Application cache
- Template cache  
- Config cache
- Route cache

---

### 🐛 Debug (1 команда)

```bash
php vilnius dump-server  # Запустить dump server
```

**Опции:**
```bash
php vilnius dump-server --host=127.0.0.1 --port=9912
```

Принимает dumps из `dd()` и `dump()` в отдельном окне терминала.

---

## 🎯 Часто используемые команды

### Разработка (Development)

```bash
# Ежедневная работа
php vilnius migrate              # После создания новых миграций
php vilnius migrate:status       # Проверить, что накатилось
php vilnius route:list           # Посмотреть роуты
php vilnius cache:clear          # Очистить кэш после изменений

# Создание ресурсов
php vilnius make:model Post -m           # Модель + миграция
php vilnius make:controller PostController -r  # Resource контроллер
```

### Продакшн (Production)

```bash
# Перед деплоем
php vilnius migrate              # Накатить новые миграции
php vilnius route:cache          # Закэшировать роуты
php vilnius cache:clear          # Очистить старый кэш

# После изменений
php vilnius route:cache          # Пересоздать кэш роутов
```

### Откат изменений

```bash
# Откатить последнюю миграцию
php vilnius migrate:rollback

# Откатить последние 3 миграции
php vilnius migrate:rollback --step=3

# Полный reset БД
php vilnius migrate:reset
php vilnius migrate              # или migrate:refresh
```

---

## 💡 Tips & Tricks

### 1. Help для любой команды
```bash
php vilnius migrate --help
php vilnius make:controller --help
```

### 2. Список всех команд
```bash
php vilnius list
```

### 3. Версия фреймворка
```bash
php vilnius --version
```

### 4. Создание ресурса за 1 минуту
```bash
# 1. Создать модель и миграцию
php vilnius make:model Post -m

# 2. Отредактировать миграцию
# database/migrations/2025_10_03_XXXXXX_create_posts_table.php

# 3. Накатить миграцию
php vilnius migrate

# 4. Создать resource контроллер
php vilnius make:controller PostController -r

# 5. Зарегистрировать роуты в routes/web.php
# $router->resource('/posts', 'PostController');

# 6. Проверить
php vilnius route:list
```

---

## 🚀 Алиасы для удобства

### PowerShell
Добавьте в `$PROFILE`:
```powershell
function v { php vilnius $args }
function vm { php vilnius migrate }
function vms { php vilnius migrate:status }
function vmr { php vilnius migrate:rollback }
function vcc { php vilnius cache:clear }
function vrl { php vilnius route:list }
function vrc { php vilnius route:cache }
```

Использование:
```powershell
v list           # php vilnius list
vm               # php vilnius migrate
vms              # php vilnius migrate:status
vcc              # php vilnius cache:clear
```

### Bash/Zsh
Добавьте в `~/.bashrc` или `~/.zshrc`:
```bash
alias v='php vilnius'
alias vm='php vilnius migrate'
alias vms='php vilnius migrate:status'
alias vmr='php vilnius migrate:rollback'
alias vcc='php vilnius cache:clear'
alias vrl='php vilnius route:list'
alias vrc='php vilnius route:cache'
```

---

## 📊 Структура команд

```
vilnius
├── migrate                   # Миграции
│   ├── migrate              
│   ├── migrate:status       
│   ├── migrate:rollback     
│   ├── migrate:reset        
│   └── migrate:refresh      
│
├── make:*                    # Генераторы
│   ├── make:migration       
│   ├── make:controller      
│   └── make:model           
│
├── route:*                   # Роуты
│   ├── route:list           
│   ├── route:cache          
│   └── route:clear          
│
├── cache:*                   # Кэш
│   └── cache:clear          
│
└── dump-server              # Debug
```

---

## 🎨 Цветовая схема

| Символ | Цвет | Значение |
|--------|------|----------|
| ℹ | Cyan | Информация |
| ✓ | Green | Успех |
| ✗ | Red | Ошибка |
| ⚠ | Yellow | Предупреждение |

---

## 📚 Документация

Подробная информация:
- **[docs/ConsoleCommands.md](docs/ConsoleCommands.md)** - Cheat Sheet с примерами
- **[docs/Console.md](docs/Console.md)** - Полная документация CLI
- **[docs/MigrationsQuickStart.md](docs/MigrationsQuickStart.md)** - Quick Start миграций
- **[MIGRATION_TO_CLI.md](MIGRATION_TO_CLI.md)** - Миграция из bin/ в CLI

---

## ✨ Что заменяет CLI?

| Старое | Новое |
|--------|-------|
| ❌ `bin/route-cache.php cache` | ✅ `php vilnius route:cache` |
| ❌ `bin/route-cache.php clear` | ✅ `php vilnius route:clear` |
| ❌ `bin/dump-server.php` | ✅ `php vilnius dump-server` |

**Папка `bin/` больше не нужна!** 🎉

---

## 🆘 Помощь

Если что-то не работает:

1. **Проверьте базовую информацию:**
   ```bash
   php vilnius --version
   php vilnius list
   ```

2. **Посмотрите help конкретной команды:**
   ```bash
   php vilnius migrate --help
   ```

3. **Проверьте конфигурацию БД:**
   ```
   config/database.php
   .env (DB_CONNECTION)
   ```

4. **Очистите кэш:**
   ```bash
   php vilnius cache:clear
   ```

---

**Made with ❤️ for Vilnius Framework**

*Total Commands: 13*  
*Total Files: 25+*  
*Total Lines: ~5000*

