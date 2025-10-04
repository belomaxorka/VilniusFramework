<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;

/**
 * Make Migration Command
 * 
 * Создать новый файл миграции
 */
class MakeMigrationCommand extends BaseMakeCommand
{
    protected string $signature = 'make:migration';
    protected string $description = 'Create a new migration file';

    public function handle(): int
    {
        $name = $this->getRequiredArgument('Migration', 'php vilnius make:migration create_users_table');
        
        if (!$name) {
            return 1;
        }

        // Генерируем имя файла с timestamp
        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}.php";

        // Определяем тип миграции и создаем нужный stub
        $stub = $this->getStub($name);
        $className = $this->getClassName($name);

        // Заменяем placeholder на название класса
        $content = str_replace('{{CLASS_NAME}}', $className, $stub);

        // Создаем файл
        return $this->createFile(
            'Migration',
            ROOT . '/database/migrations',
            $fileName,
            $content,
            $fileName
        );
    }

    /**
     * Получить stub для миграции
     */
    private function getStub(string $name): string
    {
        // Проверяем паттерн для create table
        if (preg_match('/^create_(\w+)_table$/', $name, $matches)) {
            return $this->getCreateTableStub($matches[1]);
        }

        // Проверяем паттерн для add column
        if (preg_match('/^add_(\w+)_to_(\w+)_table$/', $name, $matches)) {
            return $this->getAddColumnStub($matches[2], $matches[1]);
        }

        // Проверяем паттерн для drop table
        if (preg_match('/^drop_(\w+)_table$/', $name, $matches)) {
            return $this->getDropTableStub($matches[1]);
        }

        // По умолчанию - пустой stub
        return $this->getDefaultStub();
    }

    /**
     * Stub для создания таблицы
     */
    private function getCreateTableStub(string $table): string
    {
        return <<<PHP
<?php

use Core\Database\Migrations\Migration;
use Core\Database\Schema\Schema;

class {{CLASS_NAME}} extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$table}', function (\$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
}

PHP;
    }

    /**
     * Stub для добавления колонок
     */
    private function getAddColumnStub(string $table, string $column): string
    {
        return <<<PHP
<?php

use Core\Database\Migrations\Migration;
use Core\Database\Schema\Schema;

class {{CLASS_NAME}} extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('{$table}', function (\$table) {
            // \$table->string('{$column}');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('{$table}', function (\$table) {
            // \$table->dropColumn('{$column}');
        });
    }
}

PHP;
    }

    /**
     * Stub для удаления таблицы
     */
    private function getDropTableStub(string $table): string
    {
        return <<<PHP
<?php

use Core\Database\Migrations\Migration;
use Core\Database\Schema\Schema;

class {{CLASS_NAME}} extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('{$table}');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate table if needed
    }
}

PHP;
    }

    /**
     * Stub по умолчанию
     */
    private function getDefaultStub(): string
    {
        return <<<'PHP'
<?php

use Core\Database\Migrations\Migration;
use Core\Database\Schema\Schema;

class {{CLASS_NAME}} extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
}

PHP;
    }

    /**
     * Получить имя класса из имени миграции
     */
    private function getClassName(string $name): string
    {
        $parts = explode('_', $name);
        return implode('', array_map('ucfirst', $parts));
    }
}

