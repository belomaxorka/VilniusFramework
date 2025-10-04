<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;

/**
 * Base Make Command
 * 
 * Базовый класс для команд генерации файлов, убирает дублирование кода
 */
abstract class BaseMakeCommand extends Command
{
    /**
     * Создать файл из stub
     * 
     * @param string $name Имя создаваемого элемента
     * @param string $path Путь к директории (без имени файла)
     * @param string $fileName Имя файла
     * @param string $stub Содержимое файла
     * @param string $displayPath Путь для отображения в сообщении
     * @return int Код возврата (0 = успех, 1 = ошибка)
     */
    protected function createFile(
        string $name, 
        string $path, 
        string $fileName, 
        string $stub, 
        string $displayPath
    ): int {
        // Создаем директорию, если её нет
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $filePath = "{$path}/{$fileName}";

        // Проверяем, не существует ли уже такой файл
        if (file_exists($filePath)) {
            $this->error("{$name} already exists: {$fileName}");
            return 1;
        }

        // Записываем файл
        file_put_contents($filePath, $stub);

        $this->success("{$name} created successfully!");
        $this->line("  {$displayPath}");

        return 0;
    }

    /**
     * Проверить, передан ли обязательный аргумент
     * 
     * @return string|null Имя или null, если не передано
     */
    protected function getRequiredArgument(string $type, string $usage): ?string
    {
        $name = $this->argument(0);

        if (!$name) {
            $this->error("{$type} name is required.");
            $this->line("Usage: {$usage}");
            return null;
        }

        return $name;
    }
}

