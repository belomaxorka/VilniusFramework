# SQLite Setup - Zero Configuration Database

## Почему SQLite?

✅ **Нет настройки сервера** - работает из коробки  
✅ **Один файл** - вся база данных в `storage/database.sqlite`  
✅ **Идеально для разработки** - быстрый старт  
✅ **Поддержка транзакций** - ACID compliant  
✅ **Легко делиться** - просто скопируйте файл

## Быстрый старт

### 1. Создайте файл БД (уже сделано)

```bash
# Файл уже создан при первом запуске:
storage/database.sqlite
```

### 2. Проверьте конфигурацию

Файл `.env` должен содержать:

```env
DB_CONNECTION=sqlite
```

### 3. Запустите миграции

```bash
php vilnius migrate
```

**Готово!** База данных создана и готова к работе. ✅

---

## Переключение на MySQL

Если позже захотите использовать MySQL:

### 1. Обновите `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=vilnius
DB_USER=root
DB_PASS=your_password
```

### 2. Создайте базу данных MySQL:

```sql
CREATE DATABASE vilnius CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Запустите миграции:

```bash
php vilnius migrate
```

---

## Сравнение драйверов

| Характеристика | SQLite | MySQL | PostgreSQL |
|----------------|--------|-------|------------|
| **Настройка** | ⭐⭐⭐⭐⭐ Нет | ⭐⭐ Средняя | ⭐⭐ Средняя |
| **Скорость (чтение)** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Скорость (запись)** | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Конкурентность** | ⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Размер БД** | До ~280TB | Практически ∞ | Практически ∞ |
| **Для разработки** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Для production** | ⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |

### Когда использовать SQLite:

✅ Разработка и тестирование  
✅ Небольшие приложения (до 100K посещений/день)  
✅ Встроенные приложения  
✅ Прототипы и MVP  
✅ CLI утилиты  
✅ Мобильные приложения

### Когда переходить на MySQL/PostgreSQL:

⚠️ Много одновременных записей  
⚠️ Большая нагрузка (100K+ посещений/день)  
⚠️ Несколько серверов  
⚠️ Репликация и высокая доступность  
⚠️ Сложные хранимые процедуры

---

## Особенности SQLite в миграциях

### ✅ Поддерживается

- Все базовые типы колонок
- Primary keys и auto-increment
- Foreign keys (включены по умолчанию)
- Индексы и unique constraints
- Timestamps
- NULL/NOT NULL
- DEFAULT values

### ⚠️ Ограничения

- **ALTER TABLE** очень ограничен:
  - Нельзя удалить колонку (до SQLite 3.35+)
  - Нельзя изменить тип колонки
  - Нельзя добавить foreign key к существующей таблице

**Решение:** Используйте migrations для создания правильной структуры с самого начала.

### 🔄 Workaround для изменений

Если нужно изменить структуру:

```php
// 1. Создать новую таблицу
Schema::create('users_new', function ($table) {
    // новая структура
});

// 2. Скопировать данные
Database::statement('INSERT INTO users_new SELECT * FROM users');

// 3. Удалить старую
Schema::drop('users');

// 4. Переименовать новую
Schema::rename('users_new', 'users');
```

---

## Полезные команды

### Просмотр структуры

```bash
# SQLite CLI
sqlite3 storage/database.sqlite

# Список таблиц
.tables

# Структура таблицы
.schema users

# Выйти
.quit
```

### Бэкап базы данных

```bash
# Копирование файла
cp storage/database.sqlite storage/database.backup.sqlite

# Или через SQLite
sqlite3 storage/database.sqlite ".backup storage/database.backup.sqlite"
```

### Очистка и пересоздание

```bash
# Удалить БД
rm storage/database.sqlite

# Создать заново и выполнить миграции
touch storage/database.sqlite
php vilnius migrate
```

---

## FAQ

### Q: Где хранятся данные?

**A:** В одном файле `storage/database.sqlite` (около 24KB пустая база)

### Q: Могу ли я использовать SQLite в production?

**A:** Да, для небольших и средних приложений. Например, Stack Overflow использовал SQLite на ранних этапах!

### Q: Как настроить foreign keys?

**A:** Они включены по умолчанию в нашем драйвере. Проверьте:

```php
Database::statement('PRAGMA foreign_keys = ON');
```

### Q: Безопасно ли хранить SQLite файл в storage/?

**A:** Да, но убедитесь, что `.gitignore` содержит `storage/` чтобы не коммитить базу в git.

### Q: Можно ли использовать SQLite с несколькими серверами?

**A:** Нет, SQLite - это локальный файл. Для кластера нужен MySQL или PostgreSQL.

---

## Миграция с SQLite на MySQL

Когда приложение вырастет:

### 1. Экспорт данных из SQLite

```bash
# Через mysqldump аналог
sqlite3 storage/database.sqlite .dump > backup.sql
```

### 2. Создайте MySQL базу

```sql
CREATE DATABASE vilnius CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Обновите .env

```env
DB_CONNECTION=mysql
...
```

### 4. Запустите миграции

```bash
php vilnius migrate:fresh
```

### 5. Импортируйте данные

Конвертируйте SQL и импортируйте через MySQL client или используйте специальные инструменты.

---

## Заключение

**SQLite** - идеальный выбор для начала! Вы можете:

- ✅ Начать разработку за 30 секунд
- ✅ Не беспокоиться о настройке сервера БД
- ✅ Легко тестировать миграции
- ✅ Переключиться на MySQL когда нужно

**Happy coding!** 🚀

