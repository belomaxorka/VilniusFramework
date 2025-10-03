# Vilnius Framework - Console (CLI)

## Обзор

Vilnius Framework включает в себя мощную консольную утилиту (аналог Laravel Artisan), которая позволяет выполнять различные задачи через командную строку.

Консольное приложение автоматически берет название фреймворка и версию из конфигурации `config/framework.php`, что обеспечивает единообразие во всех частях приложения.

## Использование

```bash
php vilnius <command> [options] [arguments]
```

### Просмотр версии

```bash
php vilnius --version
# или короткая форма
php vilnius -V
```

Выводит красивый баннер с названием и версией фреймворка из конфига.

### Список всех команд

```bash
php vilnius list
# или просто
php vilnius
```

### Помощь

```bash
php vilnius --help
# или короткая форма
php vilnius -h
```

### Помощь по конкретной команде

```bash
php vilnius <command> --help
```

---

## Миграции

### Создать новую миграцию

```bash
php vilnius make:migration create_users_table
```

**Умные шаблоны:**

```bash
# Создание таблицы
php vilnius make:migration create_posts_table

# Добавление колонок
php vilnius make:migration add_email_to_users_table

# Удаление таблицы
php vilnius make:migration drop_posts_table
```

### Выполнить миграции

```bash
php vilnius migrate
```

Эта команда выполнит все pending миграции.

### Откатить последнюю миграцию

```bash
php vilnius migrate:rollback
```

Откатить несколько шагов:

```bash
php vilnius migrate:rollback --step=3
```

### Откатить все миграции

```bash
php vilnius migrate:reset
```

**⚠️ Внимание:** Эта команда удалит все таблицы!

### Откатить и выполнить заново все миграции

```bash
php vilnius migrate:refresh
```

**⚠️ Внимание:** Эта команда пересоздаст все таблицы!

### Статус миграций

```bash
php vilnius migrate:status
```

Показывает список всех миграций и их статус (выполнена или нет).

---

## Schema Builder

### Основы

```php
use Core\Database\Schema\Schema;

Schema::create('users', function ($table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});
```

### Типы колонок

#### Строки

```php
$table->string('name', 255);        // VARCHAR(255)
$table->string('email')->unique();  // VARCHAR(255) UNIQUE
$table->text('description');        // TEXT
$table->char('code', 10);          // CHAR(10)
```

#### Числа

```php
$table->integer('votes');          // INT
$table->bigInteger('amount');      // BIGINT
$table->tinyInteger('status');     // TINYINT
$table->smallInteger('count');     // SMALLINT
$table->decimal('price', 8, 2);   // DECIMAL(8,2)
$table->float('ratio', 8, 2);     // FLOAT(8,2)
$table->double('amount', 15, 8);  // DOUBLE(15,8)
$table->boolean('is_active');      // TINYINT(1)
```

#### ID и Auto-increment

```php
$table->id();                      // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
$table->bigIncrements('id');       // то же самое
$table->increments('id');          // INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
```

#### Даты и время

```php
$table->date('birth_date');           // DATE
$table->dateTime('created_at');       // DATETIME
$table->timestamp('updated_at');      // TIMESTAMP
$table->timestamps();                 // created_at и updated_at (nullable)
```

#### Специальные типы

```php
$table->json('options');              // JSON
$table->uuid('identifier');           // CHAR(36) для UUID
$table->enum('role', ['admin', 'user']); // ENUM
```

### Модификаторы колонок

```php
$table->string('email')->nullable();           // NULL
$table->string('name')->default('Guest');      // DEFAULT 'Guest'
$table->integer('votes')->unsigned();          // UNSIGNED
$table->string('phone')->unique();             // UNIQUE
$table->text('bio')->comment('User biography'); // COMMENT
```

### Внешние ключи (Foreign Keys)

```php
Schema::create('posts', function ($table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->timestamps();
});

// Или более подробно:
$table->foreignId('user_id')
    ->references('id')
    ->on('users')
    ->onDelete('CASCADE')
    ->onUpdate('CASCADE');

// Или короткая версия:
$table->foreign('user_id')
    ->references('id')
    ->on('users')
    ->cascadeOnDelete();
```

**Действия при удалении:**

```php
->cascadeOnDelete()      // ON DELETE CASCADE
->nullOnDelete()         // ON DELETE SET NULL
->restrictOnDelete()     // ON DELETE RESTRICT
->onDelete('NO ACTION')  // ON DELETE NO ACTION
```

### Индексы

```php
$table->index('email');                    // INDEX
$table->unique('email');                   // UNIQUE INDEX
$table->index(['email', 'name']);          // Composite index
$table->unique(['email', 'username']);     // Composite unique
```

### Изменение таблиц

```php
Schema::table('users', function ($table) {
    // Добавить колонку
    $table->string('phone')->nullable();
    
    // Добавить индекс
    $table->index('phone');
    
    // Добавить foreign key
    $table->foreign('role_id')->references('id')->on('roles');
});
```

### Удаление элементов

```php
Schema::table('users', function ($table) {
    // Удалить колонку
    $table->dropColumn('phone');
    
    // Удалить несколько колонок
    $table->dropColumn(['phone', 'address']);
    
    // Удалить индекс
    $table->dropIndex('users_email_index');
    
    // Удалить foreign key
    $table->dropForeign('users_role_id_foreign');
});
```

### Переименование

```php
// Переименовать таблицу
Schema::rename('users', 'customers');

// Переименовать колонку
Schema::table('users', function ($table) {
    $table->renameColumn('email', 'email_address');
});
```

### Удаление таблиц

```php
// Удалить таблицу
Schema::drop('users');

// Удалить таблицу, если существует
Schema::dropIfExists('users');
```

### Проверка существования

```php
if (Schema::hasTable('users')) {
    // Таблица существует
}

if (Schema::hasColumn('users', 'email')) {
    // Колонка существует
}
```

---

## Примеры миграций

### Создание таблицы пользователей

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
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
```

### Создание таблицы с foreign keys

```php
<?php

use Core\Database\Migrations\Migration;
use Core\Database\Schema\Schema;

class CreatePostsTable extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function ($table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('category_id')
                ->constrained()
                ->nullOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('slug')->unique();
            $table->enum('status', ['draft', 'published', 'archived'])
                ->default('draft');
            $table->integer('views')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index('status');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
}
```

### Добавление колонок в существующую таблицу

```php
<?php

use Core\Database\Migrations\Migration;
use Core\Database\Schema\Schema;

class AddPhoneToUsersTable extends Migration
{
    public function up(): void
    {
        Schema::table('users', function ($table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('avatar')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function ($table) {
            $table->dropColumn(['phone', 'avatar']);
        });
    }
}
```

### Pivot таблица (many-to-many)

```php
<?php

use Core\Database\Migrations\Migration;
use Core\Database\Schema\Schema;

class CreatePostTagTable extends Migration
{
    public function up(): void
    {
        Schema::create('post_tag', function ($table) {
            $table->id();
            $table->foreignId('post_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('tag_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();
            
            // Уникальная комбинация
            $table->unique(['post_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_tag');
    }
}
```

---

## Создание собственных команд

### Структура команды

```php
<?php

namespace App\Console\Commands;

use Core\Console\Command;

class MyCustomCommand extends Command
{
    protected string $signature = 'my:command';
    protected string $description = 'My custom command description';

    public function handle(): int
    {
        $this->info('Command started!');
        
        // Ваша логика
        
        $this->success('Command completed!');
        
        return 0; // 0 = success, 1 = error
    }
}
```

### Output методы

```php
// Информация (синий)
$this->info('Information message');

// Успех (зеленый)
$this->success('Success message');

// Ошибка (красный)
$this->error('Error message');

// Предупреждение (желтый)
$this->warning('Warning message');

// Обычный текст
$this->line('Regular text');

// Новая строка
$this->newLine();
$this->newLine(3); // 3 новые строки
```

### Input методы

```php
// Получить аргумент
$name = $this->argument(0);

// Получить опцию
$force = $this->option('force');

// Запросить ввод
$name = $this->ask('What is your name?');
$name = $this->ask('What is your name?', 'Default');

// Подтверждение (yes/no)
if ($this->confirm('Do you want to continue?')) {
    // yes
}

// Выбор из вариантов
$choice = $this->choice('Select environment', ['local', 'staging', 'production'], 0);

// Секретный ввод (пароль)
$password = $this->input->secret('Enter password');
```

### Таблицы

```php
$this->table(
    ['ID', 'Name', 'Email'],
    [
        [1, 'John Doe', 'john@example.com'],
        [2, 'Jane Doe', 'jane@example.com'],
    ]
);
```

### Прогресс-бар

```php
$items = range(1, 100);

$this->progressStart(count($items));

foreach ($items as $item) {
    // Обработка
    sleep(1);
    
    $this->progressAdvance();
}

$this->progressFinish();
```

### Регистрация команды

В файле `vilnius`:

```php
$app->registerCommands([
    \App\Console\Commands\MyCustomCommand::class,
]);
```

---

## Best Practices

### 1. Именование миграций

✅ **Правильно:**
```bash
2025_10_03_120000_create_users_table.php
2025_10_03_120100_add_email_to_users_table.php
2025_10_03_120200_create_posts_table.php
```

❌ **Неправильно:**
```bash
migration1.php
users.php
```

### 2. Всегда реализуйте down()

```php
public function down(): void
{
    Schema::dropIfExists('users');
    // или
    Schema::table('users', function ($table) {
        $table->dropColumn('email');
    });
}
```

### 3. Используйте foreign keys

```php
// ✅ Правильно - с foreign key
$table->foreignId('user_id')->constrained()->cascadeOnDelete();

// ❌ Неправильно - без foreign key
$table->bigInteger('user_id');
```

### 4. Не изменяйте старые миграции

После выполнения миграции в production **никогда не изменяйте её**!  
Вместо этого создайте новую миграцию для изменений.

### 5. Используйте timestamps()

```php
$table->timestamps(); // created_at, updated_at
$table->softDeletes(); // deleted_at
```

### 6. Индексируйте foreign keys

```php
$table->foreignId('user_id')->constrained();
$table->index('user_id'); // уже создан автоматически
$table->index('status'); // но этот нужно добавить вручную
```

---

## Troubleshooting

### Ошибка: "Migration table not found"

```bash
# Создайте таблицу миграций вручную
php vilnius migrate
```

### Ошибка: "SQLSTATE[42S01]: Base table or view already exists"

Таблица уже существует. Откатите миграцию или удалите таблицу:

```bash
php vilnius migrate:rollback
```

### Ошибка: "Class not found"

Убедитесь, что имя класса соответствует имени файла:

```php
// Файл: 2025_10_03_120000_create_users_table.php
// Класс: CreateUsersTable
```

### Откатить конкретную миграцию

К сожалению, откат конкретной миграции не поддерживается напрямую.  
Вы можете:

1. Откатить все миграции до нужной
2. Или вручную выполнить SQL для отката

---

## Changelog

### v1.0.0 (2025-10-03)
- ✅ Базовая система миграций
- ✅ Schema Builder
- ✅ Команды: migrate, rollback, reset, refresh, status
- ✅ Команда make:migration
- ✅ Foreign keys
- ✅ Индексы
- ✅ Soft deletes

---

## Roadmap

### v1.1.0 (Planned)
- [ ] Команда migrate:fresh (drop all + migrate)
- [ ] Команда migrate:install (create migrations table)
- [ ] Команда db:seed (seeders)
- [ ] Команда make:seeder
- [ ] Database factories

### v1.2.0 (Planned)
- [ ] PostgreSQL support
- [ ] SQLite support
- [ ] Blueprint::after() positioning
- [ ] Blueprint::change() для изменения колонок
- [ ] Blueprint::morphs() для polymorphic relations

---

Поздравляю! Теперь у вас есть полноценная система миграций! 🎉

