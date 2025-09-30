<?php declare(strict_types=1);

use Core\QueryDebugger;
use Core\Debug;
use Core\Environment;

beforeEach(function () {
    Environment::set(Environment::DEVELOPMENT);
    QueryDebugger::clear();
    Debug::clearOutput();
    
    // Сброс настроек в значения по умолчанию
    QueryDebugger::setDetectDuplicates(true);
    QueryDebugger::setSlowQueryThreshold(100.0);
});

afterEach(function () {
    QueryDebugger::clear();
    Debug::clearOutput();
});

describe('QueryDebugger Basic Operations', function () {
    test('logs SQL queries', function () {
        QueryDebugger::log('SELECT * FROM users', [], 10.5, 100);
        
        $queries = QueryDebugger::getQueries();
        
        expect($queries)->toHaveCount(1);
        expect($queries[0]['sql'])->toBe('SELECT * FROM users');
        expect($queries[0]['time'])->toBe(10.5);
        expect($queries[0]['rows'])->toBe(100);
    });

    test('logs queries with bindings', function () {
        $bindings = ['id' => 1, 'name' => 'John'];
        
        QueryDebugger::log('SELECT * FROM users WHERE id = ? AND name = ?', $bindings, 5.2, 1);
        
        $queries = QueryDebugger::getQueries();
        
        expect($queries[0]['bindings'])->toBe($bindings);
    });

    test('captures caller information', function () {
        QueryDebugger::log('SELECT * FROM users');
        
        $queries = QueryDebugger::getQueries();
        
        expect($queries[0]['caller'])->toHaveKey('file');
        expect($queries[0]['caller'])->toHaveKey('line');
    });

    test('can be enabled/disabled', function () {
        QueryDebugger::enable(false);
        QueryDebugger::log('SELECT * FROM users');
        
        expect(QueryDebugger::getQueries())->toHaveCount(0);
        
        QueryDebugger::enable(true);
        QueryDebugger::log('SELECT * FROM users');
        
        expect(QueryDebugger::getQueries())->toHaveCount(1);
    });
});

describe('Slow Query Detection', function () {
    test('detects slow queries', function () {
        QueryDebugger::setSlowQueryThreshold(50.0);
        
        QueryDebugger::log('SELECT * FROM users', [], 30.0, 10); // fast
        QueryDebugger::log('SELECT * FROM posts', [], 75.0, 100); // slow
        QueryDebugger::log('SELECT * FROM comments', [], 120.0, 500); // slow
        
        $slowQueries = QueryDebugger::getSlowQueries();
        
        expect($slowQueries)->toHaveCount(2);
    });

    test('marks slow queries', function () {
        QueryDebugger::setSlowQueryThreshold(100.0);
        
        QueryDebugger::log('SELECT * FROM users', [], 150.0);
        
        $queries = QueryDebugger::getQueries();
        
        expect($queries[0]['is_slow'])->toBeTrue();
    });

    test('custom threshold works', function () {
        QueryDebugger::setSlowQueryThreshold(10.0);
        
        QueryDebugger::log('SLOW QUERY', [], 15.0);
        QueryDebugger::log('FAST QUERY', [], 5.0);
        
        expect(QueryDebugger::getSlowQueries())->toHaveCount(1);
    });
});

describe('Duplicate Detection', function () {
    test('detects duplicate queries', function () {
        QueryDebugger::log('SELECT * FROM users WHERE id = 1');
        QueryDebugger::log('SELECT * FROM posts');
        QueryDebugger::log('SELECT * FROM users WHERE id = 2'); // duplicate pattern
        QueryDebugger::log('SELECT * FROM users WHERE id = 3'); // duplicate pattern
        
        $duplicates = QueryDebugger::getDuplicates();
        
        expect($duplicates)->toHaveCount(1);
        expect($duplicates[0]['count'])->toBe(3); // 3 similar queries
    });

    test('normalizes queries for comparison', function () {
        QueryDebugger::log("SELECT * FROM users WHERE id = '1'");
        QueryDebugger::log("SELECT * FROM users WHERE id = '2'");
        QueryDebugger::log("SELECT * FROM users WHERE id = '999'");
        
        $duplicates = QueryDebugger::getDuplicates();
        
        expect($duplicates)->toHaveCount(1);
        expect($duplicates[0]['count'])->toBe(3);
    });

    test('can disable duplicate detection', function () {
        QueryDebugger::setDetectDuplicates(false);
        
        QueryDebugger::log('SELECT * FROM users WHERE id = 1');
        QueryDebugger::log('SELECT * FROM users WHERE id = 2');
        
        $duplicates = QueryDebugger::getDuplicates();
        
        expect($duplicates)->toHaveCount(0);
    });
});

describe('Statistics', function () {
    test('calculates statistics correctly', function () {
        QueryDebugger::log('SELECT * FROM users', [], 10.0, 100);
        QueryDebugger::log('SELECT * FROM posts', [], 20.0, 50);
        QueryDebugger::log('SELECT * FROM comments', [], 30.0, 200);
        
        $stats = QueryDebugger::getStats();
        
        expect($stats['total'])->toBe(3);
        expect($stats['total_time'])->toBe(60.0);
        expect($stats['avg_time'])->toBe(20.0);
        expect($stats['total_rows'])->toBe(350);
    });

    test('stats include slow queries count', function () {
        QueryDebugger::setSlowQueryThreshold(50.0);
        
        QueryDebugger::log('FAST', [], 30.0);
        QueryDebugger::log('SLOW', [], 60.0);
        QueryDebugger::log('SLOW', [], 70.0);
        
        $stats = QueryDebugger::getStats();
        
        expect($stats['slow'])->toBe(2);
    });

    test('stats include duplicates count', function () {
        QueryDebugger::log('SELECT * FROM users WHERE id = 1');
        QueryDebugger::log('SELECT * FROM users WHERE id = 2');
        QueryDebugger::log('SELECT * FROM posts');
        
        $stats = QueryDebugger::getStats();
        
        expect($stats['duplicates'])->toBe(1);
    });

    test('empty stats when no queries', function () {
        $stats = QueryDebugger::getStats();
        
        expect($stats['total'])->toBe(0);
        expect($stats['avg_time'])->toBe(0);
    });
});

describe('Query Dump', function () {
    test('dumps queries with stats', function () {
        QueryDebugger::log('SELECT * FROM users', [], 25.5, 100);
        QueryDebugger::log('SELECT * FROM posts', [], 30.2, 50);
        
        QueryDebugger::dump();
        
        expect(Debug::hasOutput())->toBeTrue();
        $output = Debug::getOutput();
        
        expect($output)->toContain('SQL Query Debugger');
        expect($output)->toContain('Total Queries:');
        // SQL подсвечивается, поэтому проверяем части
        expect($output)->toContain('users');
        expect($output)->toContain('posts');
        expect($output)->toContain('100 rows');
        expect($output)->toContain('50 rows');
    });

    test('shows warnings for slow queries', function () {
        QueryDebugger::setSlowQueryThreshold(10.0);
        QueryDebugger::log('SLOW QUERY', [], 50.0);
        
        QueryDebugger::dump();
        
        $output = Debug::getOutput();
        
        expect($output)->toContain('Issues Detected');
        expect($output)->toContain('slow queries');
    });

    test('shows warnings for duplicates', function () {
        QueryDebugger::log('SELECT * FROM users WHERE id = 1');
        QueryDebugger::log('SELECT * FROM users WHERE id = 2');
        
        QueryDebugger::dump();
        
        $output = Debug::getOutput();
        
        expect($output)->toContain('duplicate queries');
    });

    test('highlights SQL syntax', function () {
        QueryDebugger::log('SELECT * FROM users WHERE id = 123', [], 10.0);
        
        QueryDebugger::dump();
        
        $output = Debug::getOutput();
        
        // Проверяем наличие подсветки синтаксиса
        expect($output)->toContain('SELECT');
        expect($output)->toContain('FROM');
        expect($output)->toContain('WHERE');
    });
});

describe('Query Measure', function () {
    test('measures query execution time', function () {
        $result = QueryDebugger::measure(function() {
            usleep(1000); // 1ms
            return 'result';
        }, 'Test Query');
        
        expect($result)->toBe('result');
        
        $queries = QueryDebugger::getQueries();
        expect($queries)->toHaveCount(1);
        expect($queries[0]['sql'])->toBe('Test Query');
        expect($queries[0]['time'])->toBeGreaterThan(0);
    });

    test('logs error queries', function () {
        try {
            QueryDebugger::measure(function() {
                throw new Exception('Query error');
            }, 'Failing Query');
        } catch (Exception $e) {
            // expected
        }
        
        $queries = QueryDebugger::getQueries();
        expect($queries[0]['sql'])->toContain('[ERROR]');
    });

    test('works without label', function () {
        $result = QueryDebugger::measure(fn() => 'test');
        
        expect($result)->toBe('test');
    });
});

describe('Helper Functions', function () {
    test('query_log helper works', function () {
        query_log('SELECT * FROM users', [], 10.0, 50);
        
        expect(QueryDebugger::getQueries())->toHaveCount(1);
    });

    test('query_dump helper works', function () {
        query_log('SELECT * FROM users');
        query_dump();
        
        expect(Debug::hasOutput())->toBeTrue();
    });

    test('query_stats helper works', function () {
        query_log('SELECT * FROM users', [], 10.0);
        
        $stats = query_stats();
        
        expect($stats['total'])->toBe(1);
    });

    test('query_slow helper works', function () {
        QueryDebugger::setSlowQueryThreshold(10.0);
        query_log('SLOW', [], 50.0);
        query_log('FAST', [], 5.0);
        
        $slow = query_slow();
        
        expect($slow)->toHaveCount(1);
    });

    test('query_duplicates helper works', function () {
        query_log('SELECT * FROM users WHERE id = 1');
        query_log('SELECT * FROM users WHERE id = 2');
        
        $duplicates = query_duplicates();
        
        expect($duplicates)->toHaveCount(1);
    });

    test('query_clear helper works', function () {
        query_log('SELECT * FROM users');
        query_clear();
        
        expect(QueryDebugger::getQueries())->toHaveCount(0);
    });

    test('query_measure helper works', function () {
        $result = query_measure(fn() => 'test', 'Test');
        
        expect($result)->toBe('test');
        expect(QueryDebugger::getQueries())->toHaveCount(1);
    });
});

describe('Production Mode', function () {
    test('query debugger disabled in production', function () {
        Environment::set(Environment::PRODUCTION);
        QueryDebugger::clear(); // Очищаем после переключения окружения
        
        QueryDebugger::log('SELECT * FROM users', [], 10.0);
        
        expect(QueryDebugger::getQueries())->toHaveCount(0);
    });

    test('measure still works in production without logging', function () {
        Environment::set(Environment::PRODUCTION);
        QueryDebugger::clear(); // Очищаем после переключения окружения
        
        $result = QueryDebugger::measure(fn() => 'result', 'Test');
        
        expect($result)->toBe('result');
        expect(QueryDebugger::getQueries())->toHaveCount(0);
    });
});

describe('Real Usage Scenarios', function () {
    test('detects N+1 problem', function () {
        // Simulate N+1: one query + N queries in loop
        query_log('SELECT * FROM posts', [], 15.0, 10);
        
        for ($i = 1; $i <= 10; $i++) {
            query_log("SELECT * FROM users WHERE id = {$i}", [], 5.0, 1);
        }
        
        $duplicates = query_duplicates();
        
        expect($duplicates)->toHaveCount(1);
        expect($duplicates[0]['count'])->toBe(10);
    });

    test('tracks complex query flow', function () {
        query_log('SELECT * FROM users', [], 25.0, 100);
        query_log('SELECT * FROM posts WHERE user_id IN (...)', [], 45.0, 500);
        query_log('SELECT * FROM comments WHERE post_id IN (...)', [], 35.0, 1000);
        
        $stats = query_stats();
        
        expect($stats['total'])->toBe(3);
        expect($stats['total_time'])->toBe(105.0);
        expect($stats['total_rows'])->toBe(1600);
    });
});
