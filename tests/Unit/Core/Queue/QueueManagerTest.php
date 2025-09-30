<?php declare(strict_types=1);

use Core\Queue\QueueManager;
use Core\Queue\Job;
use Core\Queue\QueueInterface;

// Мок драйвер для тестирования
class MockQueueDriver implements QueueInterface
{
    public array $jobs = [];
    public array $pushedJobs = [];

    public function push(Job $job, string $queue = 'default'): string
    {
        $id = uniqid('mock_', true);
        $job->setId($id);
        $this->pushedJobs[] = $job;
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
        // Mock
    }

    public function release(Job $job, int $delay = 0): void
    {
        // Mock
    }

    public function delete(Job $job): void
    {
        // Mock
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

// Тестовая задача
class ManagerTestJob extends Job
{
    public function handle(): void
    {
        // Ничего не делаем
    }
}

beforeEach(function () {
    // Очищаем драйвер перед каждым тестом
    $mockDriver = new MockQueueDriver();
    QueueManager::setDriver($mockDriver);
});

test('queue manager can push jobs', function () {
    $job = new ManagerTestJob();
    
    $id = QueueManager::push($job);
    
    expect($id)->toBeString();
    expect($job->getId())->toBe($id);
});

test('queue manager can pop jobs', function () {
    $job = new ManagerTestJob();
    QueueManager::push($job);
    
    $poppedJob = QueueManager::pop();
    
    expect($poppedJob)->toBeInstanceOf(ManagerTestJob::class);
});

test('queue manager can get queue size', function () {
    expect(QueueManager::size())->toBe(0);
    
    QueueManager::push(new ManagerTestJob());
    expect(QueueManager::size())->toBe(1);
    
    QueueManager::push(new ManagerTestJob());
    expect(QueueManager::size())->toBe(2);
});

test('queue manager can clear queue', function () {
    QueueManager::push(new ManagerTestJob());
    QueueManager::push(new ManagerTestJob());
    
    expect(QueueManager::size())->toBe(2);
    
    QueueManager::clear();
    
    expect(QueueManager::size())->toBe(0);
});
