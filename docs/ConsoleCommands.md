# Vilnius Console Commands - Cheat Sheet

## 📋 Список всех команд

```bash
php vilnius list
```

---

## 🗄️ Database Migrations

### Создать миграцию
```bash
php vilnius make:migration create_users_table
php vilnius make:migration add_email_to_users_table
php vilnius make:migration drop_old_table
```

### Выполнить миграции
```bash
php vilnius migrate
```

### Статус миграций
```bash
php vilnius migrate:status
```

### Откатить последнюю миграцию
```bash
php vilnius migrate:rollback

# Откатить несколько шагов
php vilnius migrate:rollback --step=3
```

### Откатить все миграции
```bash
php vilnius migrate:reset
```

### Пересоздать все таблицы
```bash
php vilnius migrate:refresh
```

---

## 🏗️ Make Commands

### Создать контроллер
```bash
# Простой контроллер
php vilnius make:controller UserController

# Resource контроллер (с CRUD методами)
php vilnius make:controller PostController --resource
php vilnius make:controller PostController -r
```

**Resource контроллер включает методы:**
- `index()` - список
- `create()` - форма создания
- `store()` - сохранение
- `show($id)` - просмотр
- `edit($id)` - форма редактирования
- `update($id)` - обновление
- `destroy($id)` - удаление

### Создать модель
```bash
# Простая модель
php vilnius make:model User

# Модель + миграция
php vilnius make:model Post --migration
php vilnius make:model Post -m
```

---

## 🛠️ Utility Commands

### Список роутов
```bash
php vilnius route:list
```

**Показывает:**
- HTTP метод
- URI
- Controller@method

### Очистить кэш
```bash
php vilnius cache:clear
```

**Очищает:**
- Application cache
- Template cache
- Config cache
- Route cache

---

## 💡 Примеры использования

### Создать полноценный ресурс

```bash
# 1. Создать модель с миграцией
php vilnius make:model Post -m

# 2. Отредактировать миграцию
# database/migrations/YYYY_MM_DD_HHMMSS_create_posts_table.php

# 3. Выполнить миграцию
php vilnius migrate

# 4. Создать resource контроллер
php vilnius make:controller PostController --resource

# 5. Зарегистрировать resource роуты
# routes/web.php:
# $router->resource('/posts', 'PostController');

# 6. Проверить роуты
php vilnius route:list
```

### Быстрый рестарт БД

```bash
# Откатить все и выполнить заново
php vilnius migrate:refresh

# Проверить статус
php vilnius migrate:status
```

### Очистка перед деплоем

```bash
# Очистить все кэши
php vilnius cache:clear

# Проверить роуты
php vilnius route:list
```

---

## 🎨 Цветовая схема вывода

| Тип | Цвет | Использование |
|-----|------|---------------|
| ℹ Info | Cyan | Информация |
| ✓ Success | Green | Успех |
| ✗ Error | Red | Ошибки |
| ⚠ Warning | Yellow | Предупреждения |

---

## 🔮 Будущие команды (Roadmap)

### v1.1.0
- [ ] `make:middleware` - создать middleware
- [ ] `make:seeder` - создать seeder
- [ ] `db:seed` - выполнить seeders
- [ ] `migrate:fresh` - drop all + migrate
- [ ] `config:cache` - кэшировать конфигурацию

### v1.2.0
- [ ] `make:factory` - создать factory
- [ ] `make:request` - создать form request
- [ ] `make:mail` - создать mail класс
- [ ] `queue:work` - обработать задачи
- [ ] `schedule:run` - запустить scheduler

### v1.3.0
- [ ] `storage:link` - создать symlink
- [ ] `key:generate` - генерировать APP_KEY
- [ ] `optimize` - оптимизировать все
- [ ] `down` - maintenance mode
- [ ] `up` - выйти из maintenance

---

## 📚 Документация

- [Полная документация Console](Console.md)
- [Quick Start Guide](MigrationsQuickStart.md)
- [SQLite Setup](SQLiteSetup.md)

---

## 🚀 Tips & Tricks

### Алиасы для PowerShell

Добавьте в ваш PowerShell profile (`$PROFILE`):

```powershell
function vilnius { php vilnius $args }
function vm { php vilnius migrate }
function vmr { php vilnius migrate:rollback }
function vms { php vilnius migrate:status }
function vcc { php vilnius cache:clear }
function vrl { php vilnius route:list }
```

Теперь можно использовать:
```bash
vm      # вместо php vilnius migrate
vms     # вместо php vilnius migrate:status
vcc     # вместо php vilnius cache:clear
```

### Алиасы для Bash/Zsh

Добавьте в `~/.bashrc` или `~/.zshrc`:

```bash
alias vilnius='php vilnius'
alias vm='php vilnius migrate'
alias vmr='php vilnius migrate:rollback'
alias vms='php vilnius migrate:status'
alias vcc='php vilnius cache:clear'
alias vrl='php vilnius route:list'
```

---

## 🎯 Quick Reference Card

```
┌─────────────────────────────────────────────────────┐
│            VILNIUS CLI QUICK REFERENCE              │
├─────────────────────────────────────────────────────┤
│ MIGRATIONS                                          │
│  migrate              Run migrations                │
│  migrate:status       Show status                   │
│  migrate:rollback     Rollback last                 │
│  migrate:refresh      Reset & re-run                │
│                                                      │
│ GENERATORS                                          │
│  make:migration       Create migration              │
│  make:controller      Create controller             │
│  make:model          Create model                   │
│                                                      │
│ UTILITIES                                           │
│  route:list          Show all routes                │
│  cache:clear         Clear all cache                │
│  list                Show all commands              │
└─────────────────────────────────────────────────────┘
```

---

**Made with ❤️ for Vilnius Framework**

