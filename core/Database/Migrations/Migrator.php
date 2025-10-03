<?php declare(strict_types=1);

namespace Core\Database\Migrations;

use RuntimeException;

/**
 * Migrator
 * 
 * Управление выполнением миграций
 */
class Migrator
{
    /**
     * Migration repository
     */
    private MigrationRepository $repository;

    /**
     * Путь к миграциям
     */
    private string $path;

    /**
     * Output callback
     */
    private ?\Closure $output = null;

    public function __construct(string $path)
    {
        $this->repository = new MigrationRepository();
        $this->path = $path;
    }

    /**
     * Установить output callback
     */
    public function setOutput(\Closure $callback): void
    {
        $this->output = $callback;
    }

    /**
     * Вывести сообщение
     */
    private function note(string $message): void
    {
        if ($this->output) {
            ($this->output)($message);
        }
    }

    /**
     * Выполнить все pending миграции
     */
    public function run(): array
    {
        // Создаем таблицу миграций, если её нет
        $this->repository->createRepository();

        // Получаем список файлов миграций
        $files = $this->getMigrationFiles();

        // Получаем уже выполненные миграции
        $ran = $this->repository->getRan();

        // Фильтруем только те, которые еще не выполнены
        $pending = array_diff($files, $ran);

        if (empty($pending)) {
            $this->note('Nothing to migrate.');
            return [];
        }

        // Выполняем миграции
        $batch = $this->repository->getNextBatchNumber();
        $migrations = [];

        foreach ($pending as $migration) {
            $this->runUp($migration, $batch);
            $migrations[] = $migration;
        }

        return $migrations;
    }

    /**
     * Откатить последний batch миграций
     */
    public function rollback(int $steps = 1): array
    {
        if (!$this->repository->repositoryExists()) {
            $this->note('Migration table not found.');
            return [];
        }

        $migrations = [];

        for ($i = 0; $i < $steps; $i++) {
            $batch = $this->repository->getLast();

            if (empty($batch)) {
                $this->note('Nothing to rollback.');
                break;
            }

            foreach ($batch as $migration) {
                $this->runDown($migration);
                $migrations[] = $migration;
            }
        }

        return $migrations;
    }

    /**
     * Откатить все миграции
     */
    public function reset(): array
    {
        if (!$this->repository->repositoryExists()) {
            $this->note('Migration table not found.');
            return [];
        }

        $migrations = array_reverse($this->repository->getRan());
        $rolled = [];

        if (empty($migrations)) {
            $this->note('Nothing to reset.');
            return [];
        }

        foreach ($migrations as $migration) {
            $this->runDown($migration);
            $rolled[] = $migration;
        }

        return $rolled;
    }

    /**
     * Откатить все миграции и выполнить их заново
     */
    public function refresh(): array
    {
        $this->reset();
        return $this->run();
    }

    /**
     * Получить статус миграций
     */
    public function status(): array
    {
        if (!$this->repository->repositoryExists()) {
            return [];
        }

        $ran = $this->repository->getMigrations();
        $files = $this->getMigrationFiles();

        $status = [];

        foreach ($files as $file) {
            $migration = array_values(array_filter($ran, fn($r) => $r['migration'] === $file));
            
            $status[] = [
                'migration' => $file,
                'batch' => $migration[0]['batch'] ?? null,
                'ran' => !empty($migration),
            ];
        }

        return $status;
    }

    /**
     * Выполнить миграцию up
     */
    private function runUp(string $migration, int $batch): void
    {
        $this->note("Migrating: {$migration}");

        $instance = $this->resolve($migration);
        $instance->up();

        $this->repository->log($migration, $batch);

        $this->note("Migrated: {$migration}");
    }

    /**
     * Выполнить миграцию down
     */
    private function runDown(string $migration): void
    {
        $this->note("Rolling back: {$migration}");

        $instance = $this->resolve($migration);
        $instance->down();

        $this->repository->delete($migration);

        $this->note("Rolled back: {$migration}");
    }

    /**
     * Получить экземпляр миграции
     */
    private function resolve(string $migration): Migration
    {
        $file = $this->path . '/' . $migration . '.php';

        if (!file_exists($file)) {
            throw new RuntimeException("Migration file not found: {$file}");
        }

        require_once $file;

        // Извлекаем имя класса из имени файла
        $className = $this->getClassName($migration);

        if (!class_exists($className)) {
            throw new RuntimeException("Migration class not found: {$className}");
        }

        return new $className();
    }

    /**
     * Получить имя класса из имени файла миграции
     */
    private function getClassName(string $migration): string
    {
        // Удаляем timestamp и подчеркивания
        $parts = explode('_', $migration);
        $parts = array_slice($parts, 4); // Пропускаем YYYY_MM_DD_HHMMSS
        
        // Преобразуем в CamelCase
        $className = implode('', array_map('ucfirst', $parts));

        return $className;
    }

    /**
     * Получить список файлов миграций
     */
    private function getMigrationFiles(): array
    {
        if (!is_dir($this->path)) {
            return [];
        }

        $files = glob($this->path . '/*.php');
        $migrations = [];

        foreach ($files as $file) {
            $migration = basename($file, '.php');
            $migrations[] = $migration;
        }

        sort($migrations);

        return $migrations;
    }

    /**
     * Получить repository
     */
    public function getRepository(): MigrationRepository
    {
        return $this->repository;
    }
}

