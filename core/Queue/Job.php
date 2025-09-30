<?php declare(strict_types=1);

namespace Core\Queue;

/**
 * Базовый класс для задач в очереди
 */
abstract class Job
{
    protected string $id = '';
    protected int $attempts = 0;
    protected int $maxAttempts = 3;
    protected array $data = [];

    /**
     * Выполняет задачу
     *
     * @return void
     */
    abstract public function handle(): void;

    /**
     * Устанавливает ID задачи
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Получает ID задачи
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Увеличивает счетчик попыток
     */
    public function incrementAttempts(): self
    {
        $this->attempts++;
        return $this;
    }

    /**
     * Получает количество попыток
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * Устанавливает количество попыток
     */
    public function setAttempts(int $attempts): self
    {
        $this->attempts = $attempts;
        return $this;
    }

    /**
     * Проверяет, превышено ли максимальное количество попыток
     */
    public function maxAttemptsExceeded(): bool
    {
        return $this->attempts >= $this->maxAttempts;
    }

    /**
     * Получает максимальное количество попыток
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Устанавливает данные задачи
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Получает данные задачи
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Сериализует задачу для хранения в очереди
     */
    public function serialize(): string
    {
        return json_encode([
            'class' => get_class($this),
            'id' => $this->id,
            'attempts' => $this->attempts,
            'maxAttempts' => $this->maxAttempts,
            'data' => $this->data,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Десериализует задачу из хранилища
     */
    public static function unserialize(string $payload): self
    {
        $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        
        $class = $data['class'];
        if (!class_exists($class) || !is_subclass_of($class, self::class)) {
            throw new \InvalidArgumentException("Invalid job class: {$class}");
        }

        // Если у класса есть метод fromData, используем его
        if (method_exists($class, 'fromData')) {
            /** @var Job $job */
            $job = $class::fromData($data['data'] ?? []);
        } else {
            /** @var Job $job */
            $job = new $class();
        }
        
        $job->setId($data['id'] ?? '')
            ->setAttempts($data['attempts'] ?? 0)
            ->setData($data['data'] ?? []);
        
        if (isset($data['maxAttempts'])) {
            $job->maxAttempts = $data['maxAttempts'];
        }

        return $job;
    }
}
