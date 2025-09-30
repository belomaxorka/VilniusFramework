<?php declare(strict_types=1);

use Core\Queue\Worker;
use Core\Queue\Job;
use Core\Queue\QueueInterface;

// Мок драйвер для тестирования Worker
class WorkerMockDriver implements QueueInterface
{
    public array $jobs = [];
    public array $acknowledged = [];
    public array $deleted = [];
    public array $released = [];

    public function push(Job $job, string $queue = 'default'): string
    {
        $id = uniqid('worker_', true);
        $job->setId($id);
        $this->jobs[$queue][] = $job;
        return $id;
    }

    public function pop(string $queue = 'default'): ?Job
    {
        if (!isset($this->jobs[$queue]) || empty($this->jobs[$queue])) {
            return null;
        }
        return array_shift($this->jobs[$queue]);
    }

    public function acknowledge(Job $job): void
    {
        $this->acknowledged[] = $job->getId();
    }

    public function release(Job $job, int $delay = 0): void
    {
        $this->released[] = ['job' => $job->getId(), 'delay' => $delay];
    }

    public function delete(Job $job): void
    {
        $this->deleted[] = $job->getId();
    }

    public function size(string $queue = 'default'): int
    {
        return count($this->jobs[$queue] ?? []);
    }

    public function clear(string $queue = 'default'): void
    {
        $this->jobs[$queue] = [];
    }
}

// Успешная задача
class SuccessfulJob extends Job
{
    public static $executed = false;

    public function handle(): void
    {
        self::$executed = true;
    }
}

// Задача с ошибкой
class FailingJob extends Job
{
    public function handle(): void
    {
        throw new \Exception('Job failed!');
    }
}

beforeEach(function () {
    SuccessfulJob::$executed = false;
});

test('worker processes successful job', function () {
    $driver = new WorkerMockDriver();
    $worker = new Worker($driver);
    
    $job = new SuccessfulJob();
    $driver->push($job);
    
    expect(SuccessfulJob::$executed)->toBeFalse();
    
    // Обрабатываем одну задачу
    $worker->work('default', 1);
    
    expect(SuccessfulJob::$executed)->toBeTrue();
    expect($driver->acknowledged)->toHaveCount(1);
});

test('worker handles failing job with retries', function () {
    $driver = new WorkerMockDriver();
    $worker = new Worker($driver);
    
    $job = new FailingJob();
    $driver->push($job);
    
    // Обрабатываем одну задачу
    $worker->work('default', 1);
    
    // Задача должна быть возвращена в очередь (release), а не удалена
    expect($driver->released)->toHaveCount(1);
    expect($driver->deleted)->toHaveCount(0);
});

test('worker deletes job after max attempts', function () {
    $driver = new WorkerMockDriver();
    $worker = new Worker($driver);
    
    $job = new FailingJob();
    $job->setAttempts(3); // Уже максимум попыток
    $driver->push($job);
    
    $worker->work('default', 1);
    
    // Задача должна быть удалена
    expect($driver->deleted)->toHaveCount(1);
    expect($driver->released)->toHaveCount(0);
});

test('worker respects max jobs limit', function () {
    $driver = new WorkerMockDriver();
    $worker = new Worker($driver);
    
    // Добавляем 5 задач
    for ($i = 0; $i < 5; $i++) {
        $driver->push(new SuccessfulJob());
    }
    
    // Обрабатываем только 3
    $worker->work('default', 3);
    
    $stats = $worker->getStats();
    expect($stats['processed_jobs'])->toBe(3);
    expect($driver->size())->toBe(2);
});
