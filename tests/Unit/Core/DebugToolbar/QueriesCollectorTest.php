<?php declare(strict_types=1);

use Core\DebugToolbar\Collectors\QueriesCollector;
use Core\QueryDebugger;

beforeEach(function () {
    QueryDebugger::clear();
    $this->collector = new QueriesCollector();
});

afterEach(function () {
    QueryDebugger::clear();
});

describe('QueriesCollector Configuration', function () {
    test('has correct name', function () {
        expect($this->collector->getName())->toBe('queries');
    });

    test('has correct title', function () {
        expect($this->collector->getTitle())->toBe('Queries');
    });

    test('has correct icon', function () {
        expect($this->collector->getIcon())->toBe('🗄️');
    });

    test('has priority 80', function () {
        expect($this->collector->getPriority())->toBe(80);
    });

    test('is enabled when QueryDebugger class exists', function () {
        expect($this->collector->isEnabled())->toBeTrue();
    });
});

describe('QueriesCollector Data Collection', function () {
    test('collects empty array when no queries', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data)->toHaveKey('queries');
        expect($data['queries'])->toBe([]);
    });

    test('collects single query', function () {
        QueryDebugger::log('SELECT * FROM users', [], 10.0, 5);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['queries'])->toHaveCount(1);
    });

    test('collects multiple queries', function () {
        QueryDebugger::log('SELECT * FROM users', [], 10.0);
        QueryDebugger::log('SELECT * FROM posts', [], 15.0);
        QueryDebugger::log('INSERT INTO logs', [], 5.0);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['queries'])->toHaveCount(3);
    });

    test('collects query stats', function () {
        QueryDebugger::log('SELECT 1', [], 5.0);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data)->toHaveKey('stats');
        expect($data['stats'])->toBeArray();
    });

    test('query data contains SQL', function () {
        QueryDebugger::log('SELECT * FROM users WHERE id = ?', [1], 10.0);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['queries'][0])->toHaveKey('sql');
        expect($data['queries'][0]['sql'])->toContain('SELECT * FROM users');
    });

    test('query data contains execution time', function () {
        QueryDebugger::log('SELECT 1', [], 25.5);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['queries'][0])->toHaveKey('time');
        expect($data['queries'][0]['time'])->toBe(25.5);
    });

    test('query data contains rows count', function () {
        QueryDebugger::log('SELECT * FROM users', [], 10.0, 42);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['queries'][0])->toHaveKey('rows');
        expect($data['queries'][0]['rows'])->toBe(42);
    });

    test('identifies slow queries', function () {
        QueryDebugger::setSlowQueryThreshold(10.0);
        QueryDebugger::log('FAST', [], 5.0);
        QueryDebugger::log('SLOW', [], 50.0);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['queries'][0]['is_slow'])->toBeFalse();
        expect($data['queries'][1]['is_slow'])->toBeTrue();
    });
});

describe('QueriesCollector Badge', function () {
    test('returns null badge when no queries', function () {
        $this->collector->collect();
        
        expect($this->collector->getBadge())->toBeNull();
    });

    test('returns count as badge when queries exist', function () {
        QueryDebugger::log('Q1', [], 5.0);
        QueryDebugger::log('Q2', [], 10.0);
        QueryDebugger::log('Q3', [], 15.0);
        
        $this->collector->collect();
        
        expect($this->collector->getBadge())->toBe('3');
    });

    test('badge is string type', function () {
        QueryDebugger::log('SELECT 1', [], 5.0);
        
        $this->collector->collect();
        
        expect($this->collector->getBadge())->toBeString();
    });
});

describe('QueriesCollector Rendering', function () {
    test('renders empty state when no queries', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('No queries executed');
    });

    test('renders query SQL', function () {
        QueryDebugger::log('SELECT * FROM users WHERE id = 1', [], 10.0);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('SELECT * FROM users');
    });

    test('renders query execution time', function () {
        QueryDebugger::log('SELECT 1', [], 25.5);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('25.5');
    });

    test('renders query rows count', function () {
        QueryDebugger::log('SELECT * FROM posts', [], 10.0, 42);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('42 rows');
    });

    test('highlights slow queries with red background', function () {
        QueryDebugger::setSlowQueryThreshold(10.0);
        QueryDebugger::log('SLOW QUERY', [], 100.0);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('#ffebee'); // red background
        expect($html)->toContain('#ef5350'); // red border
    });

    test('shows normal queries with white background', function () {
        QueryDebugger::log('FAST QUERY', [], 5.0);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('white'); // white background
        expect($html)->toContain('#e0e0e0'); // grey border
    });

    test('renders multiple queries with numbering', function () {
        QueryDebugger::log('Q1', [], 5.0);
        QueryDebugger::log('Q2', [], 10.0);
        QueryDebugger::log('Q3', [], 15.0);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('#1');
        expect($html)->toContain('#2');
        expect($html)->toContain('#3');
    });

    test('renders scrollable container', function () {
        QueryDebugger::log('SELECT 1', [], 5.0);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('overflow-y: auto');
        expect($html)->toContain('max-height: 400px');
    });

    test('escapes HTML in SQL', function () {
        QueryDebugger::log('SELECT * FROM users WHERE name = "<script>alert(1)</script>"', [], 5.0);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('&lt;script&gt;');
    });

    test('uses color coding for execution time', function () {
        QueryDebugger::log('FAST', [], 1.0);
        QueryDebugger::setSlowQueryThreshold(10.0);
        QueryDebugger::log('SLOW', [], 50.0);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        // Should have both green (fast) and red (slow) colors
        expect($html)->toContain('#66bb6a'); // green
        expect($html)->toContain('#ef5350'); // red
    });
});

describe('QueriesCollector Header Stats', function () {
    test('returns empty array when no queries', function () {
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats)->toBe([]);
    });

    test('returns stats array when queries exist', function () {
        QueryDebugger::log('SELECT 1', [], 5.0);
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats)->toBeArray();
        expect($stats)->toHaveCount(1);
    });

    test('header stat has correct structure', function () {
        QueryDebugger::log('SELECT 1', [], 5.0);
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0])->toHaveKey('icon');
        expect($stats[0])->toHaveKey('value');
        expect($stats[0])->toHaveKey('color');
    });

    test('header stat icon is correct', function () {
        QueryDebugger::log('SELECT 1', [], 5.0);
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['icon'])->toBe('🗄️');
    });

    test('header stat shows query count', function () {
        QueryDebugger::log('Q1', [], 5.0);
        QueryDebugger::log('Q2', [], 10.0);
        QueryDebugger::log('Q3', [], 15.0);
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['value'])->toContain('3 queries');
    });

    test('header stat color is green when no slow queries', function () {
        QueryDebugger::log('FAST', [], 5.0);
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['color'])->toBe('#66bb6a');
    });

    test('header stat color is red when slow queries exist', function () {
        QueryDebugger::setSlowQueryThreshold(10.0);
        QueryDebugger::log('FAST', [], 5.0);
        QueryDebugger::log('SLOW', [], 50.0);
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['color'])->toBe('#ef5350');
    });

    test('header stat shows slow query count', function () {
        QueryDebugger::setSlowQueryThreshold(10.0);
        QueryDebugger::log('FAST', [], 5.0);
        QueryDebugger::log('SLOW1', [], 50.0);
        QueryDebugger::log('SLOW2', [], 100.0);
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['value'])->toContain('(2 slow)');
    });

    test('header stat does not show slow count when zero', function () {
        QueryDebugger::log('FAST1', [], 5.0);
        QueryDebugger::log('FAST2', [], 3.0);
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['value'])->not->toContain('slow');
    });
});

describe('QueriesCollector Integration', function () {
    test('handles various SQL statement types', function () {
        QueryDebugger::log('SELECT * FROM users', [], 10.0);
        QueryDebugger::log('INSERT INTO logs VALUES (?)', [1], 5.0);
        QueryDebugger::log('UPDATE users SET name = ?', ['John'], 15.0);
        QueryDebugger::log('DELETE FROM temp', [], 3.0);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['queries'])->toHaveCount(4);
        
        $html = $this->collector->render();
        expect($html)->toContain('SELECT');
        expect($html)->toContain('INSERT');
        expect($html)->toContain('UPDATE');
        expect($html)->toContain('DELETE');
    });

    test('handles queries with parameters', function () {
        QueryDebugger::log('SELECT * FROM users WHERE id = ? AND status = ?', [1, 'active'], 10.0);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('SELECT * FROM users');
    });

    test('tracks total query time in stats', function () {
        QueryDebugger::log('Q1', [], 10.0);
        QueryDebugger::log('Q2', [], 15.0);
        QueryDebugger::log('Q3', [], 5.0);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['stats']['total_time'])->toBe(30.0);
    });

    test('handles long SQL queries', function () {
        $longQuery = 'SELECT ' . str_repeat('column' . rand(1, 100) . ', ', 50) . 'id FROM users';
        QueryDebugger::log($longQuery, [], 10.0);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toBeString();
        expect($html)->not->toBeEmpty();
    });

    test('maintains query order', function () {
        QueryDebugger::log('FIRST', [], 5.0);
        QueryDebugger::log('SECOND', [], 10.0);
        QueryDebugger::log('THIRD', [], 15.0);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['queries'][0]['sql'])->toContain('FIRST');
        expect($data['queries'][1]['sql'])->toContain('SECOND');
        expect($data['queries'][2]['sql'])->toContain('THIRD');
    });

    test('enabled property can be set', function () {
        $result = $this->collector->setEnabled(false);
        
        // Note: isEnabled() checks if QueryDebugger class exists, not the enabled property
        // The enabled property is checked by DebugToolbar before rendering
        expect($result)->toBe($this->collector);
        expect($this->collector->isEnabled())->toBeTrue(); // Still true because class exists
    });
});
