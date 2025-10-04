<?php declare(strict_types=1);

namespace Core\Console;

/**
 * Base Command Class
 * 
 * Базовый класс для всех консольных команд
 */
abstract class Command
{
    /**
     * Имя команды
     */
    protected string $signature = '';

    /**
     * Описание команды
     */
    protected string $description = '';

    /**
     * Output handler
     */
    protected Output $output;

    /**
     * Input handler
     */
    protected Input $input;

    public function __construct()
    {
        $this->output = new Output();
        $this->input = new Input();
    }

    /**
     * Выполнить команду
     */
    abstract public function handle(): int;

    /**
     * Запустить команду
     */
    public function execute(Input $input, Output $output): int
    {
        $this->input = $input;
        $this->output = $output;
        
        return $this->handle();
    }

    /**
     * Получить signature команды
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * Получить описание команды
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Магический метод для автоматической проксификации методов к Output и Input
     * 
     * Это избавляет от необходимости создавать методы-обертки для каждого метода
     */
    public function __call(string $method, array $arguments): mixed
    {
        // Методы Output
        if (method_exists($this->output, $method)) {
            return $this->output->$method(...$arguments);
        }
        
        // Методы Input
        if (method_exists($this->input, $method)) {
            return $this->input->$method(...$arguments);
        }
        
        // Алиасы для совместимости
        $aliases = [
            'warn' => 'warning',
            'argument' => 'getArgument',
            'option' => 'getOption',
        ];
        
        if (isset($aliases[$method])) {
            $realMethod = $aliases[$method];
            if (method_exists($this->output, $realMethod)) {
                return $this->output->$realMethod(...$arguments);
            }
            if (method_exists($this->input, $realMethod)) {
                return $this->input->$realMethod(...$arguments);
            }
        }
        
        throw new \BadMethodCallException("Method {$method} does not exist on " . static::class);
    }

    /**
     * Удалить файл кэша
     * 
     * @return bool true если файл был удален, false если файл не существовал
     */
    protected function deleteCacheFile(string $path): bool
    {
        if (file_exists($path)) {
            unlink($path);
            return true;
        }
        return false;
    }

    /**
     * Удалить файлы в директории по маске
     * 
     * @return int количество удаленных файлов
     */
    protected function deleteFiles(string $pattern): int
    {
        $files = glob($pattern);
        $count = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $count++;
            }
        }
        
        return $count;
    }
}

