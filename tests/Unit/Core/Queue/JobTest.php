<?php declare(strict_types=1);

use Core\Queue\Job;

// Тестовая задача
class TestJob extends Job
{
    public $executed = false;

    public function handle(): void
    {
        $this->executed = true;
    }
}

// Задача с данными
class DataJob extends Job
{
    public function __construct(
        public string $name = '',
        public int $value = 0
    ) {
    }

    public function handle(): void
    {
        // Ничего не делаем в тесте
    }

    public static function fromData(array $data): self
    {
        return new self(
            $data['name'] ?? '',
            $data['value'] ?? 0
        );
    }

    public function serialize(): string
    {
        return json_encode([
            'class' => get_class($this),
            'id' => $this->id,
            'attempts' => $this->attempts,
            'maxAttempts' => $this->maxAttempts,
            'data' => [
                'name' => $this->name,
                'value' => $this->value,
            ],
        ], JSON_THROW_ON_ERROR);
    }
}

test('job can be executed', function () {
    $job = new TestJob();
    
    expect($job->executed)->toBeFalse();
    
    $job->handle();
    
    expect($job->executed)->toBeTrue();
});

test('job can set and get id', function () {
    $job = new TestJob();
    $job->setId('test-id-123');
    
    expect($job->getId())->toBe('test-id-123');
});

test('job tracks attempts', function () {
    $job = new TestJob();
    
    expect($job->getAttempts())->toBe(0);
    
    $job->incrementAttempts();
    expect($job->getAttempts())->toBe(1);
    
    $job->incrementAttempts();
    expect($job->getAttempts())->toBe(2);
});

test('job detects max attempts exceeded', function () {
    $job = new TestJob();
    
    expect($job->maxAttemptsExceeded())->toBeFalse();
    
    $job->setAttempts(3);
    expect($job->maxAttemptsExceeded())->toBeTrue();
});

test('job can be serialized and unserialized', function () {
    $job = new TestJob();
    $job->setId('test-123')
        ->setAttempts(2)
        ->setData(['key' => 'value']);
    
    $serialized = $job->serialize();
    
    expect($serialized)->toBeString();
    
    $unserialized = Job::unserialize($serialized);
    
    expect($unserialized)->toBeInstanceOf(TestJob::class);
    expect($unserialized->getId())->toBe('test-123');
    expect($unserialized->getAttempts())->toBe(2);
    expect($unserialized->getData())->toBe(['key' => 'value']);
});

test('job with constructor parameters can be serialized', function () {
    $job = new DataJob('Test Name', 42);
    $job->setId('data-job-1');
    
    $serialized = $job->serialize();
    $unserialized = Job::unserialize($serialized);
    
    expect($unserialized)->toBeInstanceOf(DataJob::class);
    expect($unserialized->name)->toBe('Test Name');
    expect($unserialized->value)->toBe(42);
    expect($unserialized->getId())->toBe('data-job-1');
});
