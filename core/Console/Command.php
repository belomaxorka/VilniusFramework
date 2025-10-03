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
     * Вывести информацию
     */
    protected function info(string $message): void
    {
        $this->output->info($message);
    }

    /**
     * Вывести успех
     */
    protected function success(string $message): void
    {
        $this->output->success($message);
    }

    /**
     * Вывести ошибку
     */
    protected function error(string $message): void
    {
        $this->output->error($message);
    }

    /**
     * Вывести предупреждение
     */
    protected function warning(string $message): void
    {
        $this->output->warning($message);
    }

    /**
     * Вывести обычный текст
     */
    protected function line(string $message = ''): void
    {
        $this->output->line($message);
    }

    /**
     * Вывести новую строку
     */
    protected function newLine(int $count = 1): void
    {
        $this->output->newLine($count);
    }

    /**
     * Запросить ввод пользователя
     */
    protected function ask(string $question, ?string $default = null): string
    {
        return $this->input->ask($question, $default);
    }

    /**
     * Запросить подтверждение
     */
    protected function confirm(string $question, bool $default = false): bool
    {
        return $this->input->confirm($question, $default);
    }

    /**
     * Запросить выбор из вариантов
     */
    protected function choice(string $question, array $choices, mixed $default = null): string
    {
        return $this->input->choice($question, $choices, $default);
    }

    /**
     * Получить аргумент
     */
    protected function argument(string $name): mixed
    {
        return $this->input->getArgument($name);
    }

    /**
     * Получить опцию
     */
    protected function option(string $name): mixed
    {
        return $this->input->getOption($name);
    }

    /**
     * Вывести таблицу
     */
    protected function table(array $headers, array $rows): void
    {
        $this->output->table($headers, $rows);
    }

    /**
     * Вывести прогресс-бар
     */
    protected function progressStart(int $max): void
    {
        $this->output->progressStart($max);
    }

    /**
     * Продвинуть прогресс-бар
     */
    protected function progressAdvance(int $step = 1): void
    {
        $this->output->progressAdvance($step);
    }

    /**
     * Завершить прогресс-бар
     */
    protected function progressFinish(): void
    {
        $this->output->progressFinish();
    }
}

