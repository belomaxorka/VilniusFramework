# Migrations Quick Start

## За 5 минут - от нуля до работающих миграций! ⚡

### 1. Проверьте установку

```bash
php vilnius list
```

Вы должны увидеть список доступных команд.

### 2. Создайте первую миграцию

```bash
php vilnius make:migration create_users_table
```

**Создан файл:** `database/migrations/2025_10_03_HHMMSS_create_users_table.php`

### 3. Отредактируйте миграцию

```php
<?php

use Core\Database\Migrations\Migration;
use Core\Database\Schema\Schema;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
```

### 4. Выполните миграцию

```bash
php vilnius migrate
```

**Готово!** Таблица `users` создана в вашей базе данных. ✅

---

## Часто используемые команды

```bash
# Создать миграцию
php vilnius make:migration create_posts_table

# Выполнить все pending миграции
php vilnius migrate

# Откатить последнюю миграцию
php vilnius migrate:rollback

# Посмотреть статус
php vilnius migrate:status

# Откатить все и выполнить заново
php vilnius migrate:refresh
```

---

## Примеры миграций

### Таблица с foreign key

```bash
php vilnius make:migration create_posts_table
```

```php
Schema::create('posts', function ($table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('content');
    $table->timestamps();
});
```

### Добавление колонки

```bash
php vilnius make:migration add_avatar_to_users_table
```

```php
Schema::table('users', function ($table) {
    $table->string('avatar')->nullable();
});
```

### Pivot таблица

```bash
php vilnius make:migration create_post_tag_table
```

```php
Schema::create('post_tag', function ($table) {
    $table->foreignId('post_id')->constrained()->cascadeOnDelete();
    $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
    $table->unique(['post_id', 'tag_id']);
});
```

---

## Типы колонок - Cheat Sheet

```php
// ID
$table->id();                           // BIGINT AUTO_INCREMENT PRIMARY

// Strings
$table->string('name', 100);            // VARCHAR(100)
$table->text('description');            // TEXT

// Numbers
$table->integer('votes');               // INT
$table->bigInteger('amount');           // BIGINT
$table->decimal('price', 8, 2);         // DECIMAL(8,2)
$table->boolean('is_active');           // TINYINT(1)

// Dates
$table->date('birth_date');             // DATE
$table->dateTime('published_at');       // DATETIME
$table->timestamp('created_at');        // TIMESTAMP
$table->timestamps();                   // created_at + updated_at

// Special
$table->json('metadata');               // JSON
$table->enum('status', ['active', 'inactive']); // ENUM
$table->uuid('identifier');             // CHAR(36)

// Foreign Keys
$table->foreignId('user_id')
    ->constrained()
    ->cascadeOnDelete();
```

## Модификаторы

```php
->nullable()                // NULL
->default('value')          // DEFAULT 'value'
->unsigned()                // UNSIGNED
->unique()                  // UNIQUE
->comment('Description')    // COMMENT
->after('column')          // AFTER `column`
```

---

## Workflow

### Development

```bash
# 1. Создаем миграцию
php vilnius make:migration create_products_table

# 2. Редактируем файл
# database/migrations/2025_10_03_120000_create_products_table.php

# 3. Выполняем
php vilnius migrate

# 4. Если нужно внести изменения
php vilnius migrate:rollback

# 5. Редактируем и снова выполняем
php vilnius migrate
```

### Production

```bash
# 1. Создаем миграцию в dev
php vilnius make:migration add_column_to_table

# 2. Тестируем локально
php vilnius migrate

# 3. Коммитим в git
git add database/migrations/
git commit -m "Add migration"

# 4. На production
git pull
php vilnius migrate
```

---

## Troubleshooting

### "Migration table not found"

```bash
php vilnius migrate  # Создаст таблицу автоматически
```

### "Table already exists"

```bash
# Откатите или удалите таблицу вручную
php vilnius migrate:rollback
```

### Нужно откатить всё

```bash
php vilnius migrate:reset
php vilnius migrate
```

---

## Best Practices ⭐

1. **Никогда не изменяйте выполненные миграции** в production
2. **Всегда** реализуйте метод `down()`
3. **Используйте** foreign keys для связей
4. **Добавляйте** индексы на часто используемые колонки
5. **Именуйте** миграции понятно: `create_users_table`, не `migration1`

---

## Что дальше?

📚 [Полная документация по Console](Console.md)  
📚 [Schema Builder Reference](Console.md#schema-builder)  
📚 [Примеры миграций](Console.md#примеры-миграций)

---

**Happy migrating!** 🚀

