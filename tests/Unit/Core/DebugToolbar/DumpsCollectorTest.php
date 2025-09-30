<?php declare(strict_types=1);

use Core\DebugToolbar\Collectors\DumpsCollector;
use Core\Debug;

beforeEach(function () {
    Debug::clearOutput();
    $this->collector = new DumpsCollector();
});

afterEach(function () {
    Debug::clearOutput();
});

describe('DumpsCollector Configuration', function () {
    test('has correct name', function () {
        expect($this->collector->getName())->toBe('dumps');
    });

    test('has correct title', function () {
        expect($this->collector->getTitle())->toBe('Dumps');
    });

    test('has correct icon', function () {
        expect($this->collector->getIcon())->toBe('🔍');
    });

    test('has priority 90', function () {
        expect($this->collector->getPriority())->toBe(90);
    });

    test('is enabled when Debug class exists', function () {
        expect($this->collector->isEnabled())->toBeTrue();
    });
});

describe('DumpsCollector Data Collection', function () {
    test('collects empty array when no dumps', function () {
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data)->toHaveKey('dumps');
        expect($data['dumps'])->toBe([]);
    });

    test('collects single dump', function () {
        dump('test value');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['dumps'])->toHaveCount(1);
    });

    test('collects multiple dumps', function () {
        dump('first');
        dump('second');
        dump('third');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['dumps'])->toHaveCount(3);
    });

    test('collected dumps contain output', function () {
        dump('test data');
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['dumps'][0])->toHaveKey('output');
    });

    test('collects dumps with different data types', function () {
        dump('string');
        dump(123);
        dump(['array' => 'value']);
        dump((object)['obj' => 'value']);
        
        $this->collector->collect();
        $data = $this->collector->getData();
        
        expect($data['dumps'])->toHaveCount(4);
    });
});

describe('DumpsCollector Badge', function () {
    test('returns null badge when no dumps', function () {
        $this->collector->collect();
        
        expect($this->collector->getBadge())->toBeNull();
    });

    test('returns count as badge when dumps exist', function () {
        dump('test1');
        dump('test2');
        
        $this->collector->collect();
        
        expect($this->collector->getBadge())->toBe('2');
    });

    test('badge updates with more dumps', function () {
        dump('test1');
        $this->collector->collect();
        expect($this->collector->getBadge())->toBe('1');
        
        dump('test2');
        dump('test3');
        Debug::clearOutput();
        
        dump('test1');
        dump('test2');
        dump('test3');
        $this->collector->collect();
        expect($this->collector->getBadge())->toBe('3');
    });

    test('badge is string type', function () {
        dump('test');
        $this->collector->collect();
        
        expect($this->collector->getBadge())->toBeString();
    });
});

describe('DumpsCollector Rendering', function () {
    test('renders empty state when no dumps', function () {
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('No dumps collected');
    });

    test('renders dump output', function () {
        dump('test data');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->not->toContain('No dumps collected');
        expect($html)->toBeString();
        expect($html)->not->toBeEmpty();
    });

    test('renders multiple dumps', function () {
        dump('first');
        dump('second');
        dump('third');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        // Should contain all dumps
        expect($html)->toBeString();
        // Each dump should be in a separate div
        expect(substr_count($html, 'margin-bottom: 10px'))->toBe(3);
    });

    test('renders scrollable container', function () {
        dump('test');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('overflow-y: auto');
        expect($html)->toContain('max-height: 400px');
    });

    test('handles HTML in dump output', function () {
        dump('<script>alert("test")</script>');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        // Should render without errors
        expect($html)->toBeString();
        expect($html)->not->toBeEmpty();
    });

    test('renders complex data structures', function () {
        dump([
            'nested' => [
                'array' => [1, 2, 3],
                'object' => (object)['prop' => 'value']
            ]
        ]);
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toBeString();
        expect($html)->not->toBeEmpty();
    });
});

describe('DumpsCollector Header Stats', function () {
    test('returns empty array when no dumps', function () {
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats)->toBe([]);
    });

    test('returns stats array when dumps exist', function () {
        dump('test');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats)->toBeArray();
        expect($stats)->toHaveCount(1);
    });

    test('header stat has correct structure', function () {
        dump('test1');
        dump('test2');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0])->toHaveKey('icon');
        expect($stats[0])->toHaveKey('value');
        expect($stats[0])->toHaveKey('color');
    });

    test('header stat icon is correct', function () {
        dump('test');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['icon'])->toBe('🔍');
    });

    test('header stat shows count', function () {
        dump('test1');
        dump('test2');
        dump('test3');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['value'])->toBe('3 dumps');
    });

    test('header stat color is green', function () {
        dump('test');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['color'])->toBe('#66bb6a');
    });

    test('handles single dump correctly in label', function () {
        dump('test');
        
        $this->collector->collect();
        $stats = $this->collector->getHeaderStats();
        
        expect($stats[0]['value'])->toBe('1 dumps');
    });
});

describe('DumpsCollector Integration', function () {
    test('works with dump helper function', function () {
        dump('test');
        
        $this->collector->collect();
        
        expect($this->collector->getData()['dumps'])->toHaveCount(1);
        expect($this->collector->getBadge())->toBe('1');
    });

    test('collects dumps with labels', function () {
        dump(['key' => 'value'], 'My Label');
        
        $this->collector->collect();
        $html = $this->collector->render();
        
        expect($html)->toContain('My Label');
    });

    test('handles rapid consecutive dumps', function () {
        for ($i = 0; $i < 10; $i++) {
            dump("dump $i");
        }
        
        $this->collector->collect();
        
        expect($this->collector->getData()['dumps'])->toHaveCount(10);
        expect($this->collector->getBadge())->toBe('10');
    });

    test('can be set to disabled state', function () {
        $this->collector->setEnabled(false);
        
        // Note: isEnabled() for DumpsCollector checks if Debug class exists,
        // not the enabled property. The enabled property affects rendering in toolbar.
        expect($this->collector->isEnabled())->toBeTrue(); // Still true because Debug class exists
        
        dump('test');
        $this->collector->collect();
        
        // Should still collect data
        expect($this->collector->getData())->toHaveKey('dumps');
    });

    test('maintains state between collect calls', function () {
        dump('first');
        $this->collector->collect();
        
        $firstCount = count($this->collector->getData()['dumps']);
        
        // Clear and add more
        Debug::clearOutput();
        dump('second');
        dump('third');
        $this->collector->collect();
        
        $secondCount = count($this->collector->getData()['dumps']);
        
        expect($firstCount)->toBe(1);
        expect($secondCount)->toBe(2);
    });
});
