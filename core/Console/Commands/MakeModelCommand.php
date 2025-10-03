<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;

/**
 * Make Model Command
 * 
 * Создать новую модель
 */
class MakeModelCommand extends Command
{
    protected string $signature = 'make:model';
    protected string $description = 'Create a new model class';

    public function handle(): int
    {
        $name = $this->argument(0);

        if (!$name) {
            $this->error('Model name is required.');
            $this->line('Usage: php vilnius make:model User');
            return 1;
        }

        $path = ROOT . '/app/Models';

        // Создаем директорию, если её нет
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $filePath = "{$path}/{$name}.php";

        // Проверяем, не существует ли уже такой файл
        if (file_exists($filePath)) {
            $this->error("Model already exists: {$name}");
            return 1;
        }

        // Генерируем контент
        $stub = $this->getStub($name);

        // Записываем файл
        file_put_contents($filePath, $stub);

        $this->success("Model created successfully!");
        $this->line("  app/Models/{$name}.php");

        // Проверяем флаг --migration или -m
        if ($this->option('migration') || $this->option('m')) {
            $this->newLine();
            $this->info("Creating migration...");
            
            $tableName = $this->getTableName($name);
            $migrationName = "create_{$tableName}_table";
            
            // Создаем миграцию
            $this->input->replace([0 => $migrationName]);
            $makeMigration = new MakeMigrationCommand();
            $makeMigration->handle();
        }

        return 0;
    }

    /**
     * Получить stub для модели
     */
    private function getStub(string $name): string
    {
        $tableName = $this->getTableName($name);

        return <<<PHP
<?php declare(strict_types=1);

namespace App\Models;

class {$name} extends BaseModel
{
    /**
     * Название таблицы
     */
    protected string \$table = '{$tableName}';

    /**
     * Первичный ключ
     */
    protected string \$primaryKey = 'id';

    /**
     * Использовать timestamps
     */
    protected bool \$timestamps = true;

    /**
     * Заполняемые поля
     */
    protected array \$fillable = [
        // 'name',
        // 'email',
    ];

    /**
     * Скрытые поля (не включаются в JSON)
     */
    protected array \$hidden = [
        // 'password',
    ];
}

PHP;
    }

    /**
     * Получить имя таблицы из имени модели
     */
    private function getTableName(string $modelName): string
    {
        // User -> users
        // Post -> posts
        // Category -> categories
        
        $name = strtolower($modelName);
        
        // Простая плюрализация (можно улучшить)
        if (str_ends_with($name, 'y')) {
            return substr($name, 0, -1) . 'ies';
        }
        
        if (str_ends_with($name, 's')) {
            return $name . 'es';
        }
        
        return $name . 's';
    }
}

