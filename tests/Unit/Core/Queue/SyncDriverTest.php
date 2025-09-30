<?php declare(strict_types=1);

use Core\Queue\Job;
use Core\Queue\Drivers\SyncDriver;

// Тестовая задача
class SyncTestJob extends Job
{
    public static $executionCount = 0;

    public function handle(): void
    {
        self::$executionCount++;
    }
}

beforeEach(function () {
    SyncTestJob::$executionCount = 0;
});

test('sync driver executes job immediately', function () {
    $driver = new SyncDriver();
    $job = new SyncTestJob();
    
    expect(SyncTestJob::$executionCount)->toBe(0);
    
    $id = $driver->push($job);
    
    expect($id)->toBeString();
    expect(SyncTestJob::$executionCount)->toBe(1);
});

test('sync driver returns null on pop', function () {
    $driver = new SyncDriver();
    
    $job = $driver->pop();
    
    expect($job)->toBeNull();
});

test('sync driver size is always zero', function () {
    $driver = new SyncDriver();
    
    expect($driver->size())->toBe(0);
    
    $driver->push(new SyncTestJob());
    
    expect($driver->size())->toBe(0);
});
