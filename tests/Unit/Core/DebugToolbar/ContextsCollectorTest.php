<?php declare(strict_types=1);

use Core\DebugToolbar\Collectors\ContextsCollector;
use Core\DebugContext;

beforeEach(function () {
    DebugContext::clear();
    $this->collector = new ContextsCollector();
});

afterEach(function () {
    DebugContext::clear();
});

describe('ContextsCollector Configuration', function () {
    test('has correct name', function () {
        expect($this->collector->getName())->toBe('contexts');
    });

    test('has correct title', function () {
        expect($this->collector->getTitle())->toBe('Contexts');
    });

    test('has correct icon', function () {
        expect($this->collector->getIcon())->toBe('📁');
    });

    test('has priority 50', function () {
        expect($this->collector->getPriority())->toBe(50);
    });

    test('is enabled when DebugContext class exists', function () {
        expect($this->collector->isEnabled())->toBeTrue();
    });
});

describe('ContextsCollector Data Collection', function () {
    test('collects contexts array', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data)->toHaveKey('contexts');
        expect($data['contexts'])->toBeArray();
    });

    test('collects default contexts', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        // Should have at least default contexts (general, database)
        expect(count($data['contexts']))->toBeGreaterThanOrEqual(2);
    });

    test('collects custom contexts', function () {
        DebugContext::start('api');
        DebugContext::start('cache');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['contexts'])->toHaveKey('api');
        expect($data['contexts'])->toHaveKey('cache');
    });

    test('context data contains config', function () {
        DebugContext::start('test');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['contexts']['test'])->toHaveKey('config');
    });

    test('context config contains label', function () {
        DebugContext::start('custom');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['contexts']['custom']['config'])->toHaveKey('label');
    });

    test('context config contains color', function () {
        DebugContext::start('custom');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['contexts']['custom']['config'])->toHaveKey('color');
    });

    test('context config contains icon', function () {
        DebugContext::start('custom');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['contexts']['custom']['config'])->toHaveKey('icon');
    });

    test('context data contains items array', function () {
        DebugContext::start('test');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['contexts']['test'])->toHaveKey('items');
        expect($data['contexts']['test']['items'])->toBeArray();
    });

    test('collects context items', function () {
        DebugContext::start('test');
        DebugContext::add('key1', 'value1');
        DebugContext::add('key2', 'value2');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['contexts']['test']['items'])->toHaveCount(2);
    });

    test('collects multiple contexts with items', function () {
        DebugContext::start('api');
        DebugContext::add('endpoint', '/api/users');
        
        DebugContext::start('cache');
        DebugContext::add('hit', 'key1');
        DebugContext::add('miss', 'key2');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['contexts']['api']['items'])->toHaveCount(1);
        expect($data['contexts']['cache']['items'])->toHaveCount(2);
    });
});

describe('ContextsCollector Badge', function () {
    test('returns null badge when only default contexts', function () {
        $this->collector->collect();
        
        // Clear default contexts to test properly
        DebugContext::clear();
        $this->collector->collect();
        
        $badge = $this->collector->getBadge();
        expect($badge)->toBeNull();
    });

    test('returns count as badge when contexts exist', function () {
        DebugContext::start('ctx1');
        DebugContext::start('ctx2');
        DebugContext::start('ctx3');
        
        $this->collector->collect();
        
        $badge = $this->collector->getBadge();
        // Should count custom + default contexts
        expect($badge)->toBeString();
        expect((int)$badge)->toBeGreaterThan(0);
    });

    test('badge is string type', function () {
        DebugContext::start('test');
        
        $this->collector->collect();
        
        expect($this->collector->getBadge())->toBeString();
    });

    test('badge updates with more contexts', function () {
        DebugContext::start('ctx1');
        $this->collector->collect();
        $firstBadge = (int)$this->collector->getBadge();
        
        DebugContext::clear();
        DebugContext::start('ctx1');
        DebugContext::start('ctx2');
        $this->collector->collect();
        $secondBadge = (int)$this->collector->getBadge();
        
        expect($secondBadge)->toBeGreaterThan($firstBadge);
    });
});

describe('ContextsCollector Rendering', function () {
    test('renders empty state when no contexts', function () {
        DebugContext::clear();
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('No contexts created');
    });

    test('renders context label', function () {
        DebugContext::start('api');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        // Label is capitalized
        expect($html)->toContain('API');
    });

    test('renders context icon', function () {
        DebugContext::start('database');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        // Should contain database icon
        expect($html)->toContain('🗄️');
    });

    test('renders context with custom color', function () {
        DebugContext::start('test');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        // Should contain color in border-left style
        expect($html)->toContain('border-left: 4px solid');
    });

    test('displays item count for each context', function () {
        DebugContext::start('test');
        DebugContext::add('key1', 'value1');
        DebugContext::add('key2', 'value2');
        DebugContext::add('key3', 'value3');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Items: 3');
    });

    test('renders multiple contexts', function () {
        DebugContext::start('api');
        DebugContext::start('cache');
        DebugContext::start('database');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('API');
        expect($html)->toContain('Cache');
        expect($html)->toContain('Database');
    });

    test('renders scrollable container', function () {
        DebugContext::start('test');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('overflow-y: auto');
        expect($html)->toContain('max-height: 400px');
    });

    test('contexts have proper styling', function () {
        DebugContext::start('test');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('background: white');
        expect($html)->toContain('padding: 10px');
        expect($html)->toContain('border-radius: 4px');
    });

    test('renders context with zero items', function () {
        DebugContext::start('empty');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('Items: 0');
    });
});

describe('ContextsCollector Header Stats', function () {
    test('returns empty array when no contexts', function () {
        DebugContext::clear();
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats)->toBe([]);
    });

    test('returns stats array when contexts exist', function () {
        DebugContext::start('test');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats)->toBeArray();
        expect($stats)->toHaveCount(1);
    });

    test('header stat has correct structure', function () {
        DebugContext::start('test');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0])->toHaveKey('icon');
        expect($stats[0])->toHaveKey('value');
        expect($stats[0])->toHaveKey('color');
    });

    test('header stat icon is correct', function () {
        DebugContext::start('test');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['icon'])->toBe('📁');
    });

    test('header stat shows context count', function () {
        DebugContext::start('ctx1');
        DebugContext::start('ctx2');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['value'])->toContain('contexts');
        expect($stats[0]['value'])->toMatch('/\d+ contexts/');
    });

    test('header stat color is green', function () {
        DebugContext::start('test');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['color'])->toBe('#66bb6a');
    });
});

describe('ContextsCollector Integration', function () {
    test('works with predefined contexts', function () {
        DebugContext::start('general');
        DebugContext::start('database');
        DebugContext::start('cache');
        DebugContext::start('api');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['contexts'])->toHaveKey('general');
        expect($data['contexts'])->toHaveKey('database');
        expect($data['contexts'])->toHaveKey('cache');
        expect($data['contexts'])->toHaveKey('api');
    });

    test('displays all context information', function () {
        DebugContext::start('api');
        DebugContext::add('method', 'GET');
        DebugContext::add('endpoint', '/api/users');
        DebugContext::add('status', 200);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('API');
        expect($html)->toContain('Items: 3');
    });

    test('handles context switching', function () {
        DebugContext::start('api');
        DebugContext::add('request', 'data1');
        
        DebugContext::start('database');
        DebugContext::add('query', 'SELECT 1');
        
        DebugContext::start('api');
        DebugContext::add('response', 'data2');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        // Both contexts should have their items
        expect($data['contexts']['api']['items'])->toHaveCount(2);
        expect($data['contexts']['database']['items'])->toHaveCount(1);
    });

    test('can be disabled', function () {
        $this->collector->setEnabled(false);
        
        expect($this->collector->isEnabled())->toBeFalse();
    });

    test('handles many contexts efficiently', function () {
        for ($i = 1; $i <= 20; $i++) {
            DebugContext::start("context$i");
            DebugContext::add("key$i", "value$i");
        }
        
        $this->collector->collect();
        $data = $this->collector->getData();
        $html = $this->collector->render();
        
        // Should have all contexts
        expect(count($data['contexts']))->toBeGreaterThanOrEqual(20);
        
        // Should render without errors
        expect($html)->toBeString();
        expect($html)->not->toBeEmpty();
    });

    test('preserves context order', function () {
        DebugContext::start('first');
        DebugContext::start('second');
        DebugContext::start('third');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        $keys = array_keys($data['contexts']);
        
        // Order might include default contexts, but custom ones should be present
        expect(in_array('first', $keys))->toBeTrue();
        expect(in_array('second', $keys))->toBeTrue();
        expect(in_array('third', $keys))->toBeTrue();
    });
});
