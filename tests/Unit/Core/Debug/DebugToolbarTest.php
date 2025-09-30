<?php declare(strict_types=1);

use Core\DebugToolbar;
use Core\Debug;
use Core\Environment;
use Core\QueryDebugger;
use Core\DebugContext;

beforeEach(function () {
    Environment::set(Environment::DEVELOPMENT);
    Debug::clearOutput();
    QueryDebugger::clear();
    DebugContext::clear();
    DebugToolbar::enable(true);
});

afterEach(function () {
    Debug::clearOutput();
    QueryDebugger::clear();
    DebugContext::clear();
});

describe('DebugToolbar Basic Operations', function () {
    test('renders toolbar HTML', function () {
        $html = DebugToolbar::render();
        
        expect($html)->toBeString();
        expect($html)->toContain('debug-toolbar');
        expect($html)->toContain('Debug Toolbar');
    });

    test('can be enabled/disabled', function () {
        DebugToolbar::enable(false);
        
        expect(DebugToolbar::render())->toBe('');
        
        DebugToolbar::enable(true);
        
        expect(DebugToolbar::render())->not->toBe('');
    });

    test('includes JavaScript', function () {
        $html = DebugToolbar::render();
        
        expect($html)->toContain('<script>');
        expect($html)->toContain('debugToolbarToggle');
        expect($html)->toContain('debugToolbarSwitchTab');
    });

    test('includes CSS styles', function () {
        $html = DebugToolbar::render();
        
        expect($html)->toContain('<style>');
        expect($html)->toContain('.debug-tab.active');
    });
});

describe('Toolbar Statistics', function () {
    test('shows memory stats', function () {
        $html = DebugToolbar::render();
        
        expect($html)->toContain('💾');
    });

    test('shows query stats when queries exist', function () {
        QueryDebugger::log('SELECT * FROM users', [], 10.0, 5);
        
        $html = DebugToolbar::render();
        
        expect($html)->toContain('🗄️');
        expect($html)->toContain('queries');
    });

    test('shows context stats when contexts exist', function () {
        DebugContext::start('test');
        
        $html = DebugToolbar::render();
        
        expect($html)->toContain('contexts');
    });

    test('shows dump stats when dumps exist', function () {
        dump('test data');
        
        $html = DebugToolbar::render();
        
        expect($html)->toContain('dumps');
    });
});

describe('Toolbar Tabs', function () {
    test('has overview tab', function () {
        $html = DebugToolbar::render();
        
        expect($html)->toContain('Overview');
        expect($html)->toContain('📊');
    });

    test('has dumps tab', function () {
        $html = DebugToolbar::render();
        
        expect($html)->toContain('Dumps');
        expect($html)->toContain('🔍');
    });

    test('has queries tab', function () {
        $html = DebugToolbar::render();
        
        expect($html)->toContain('Queries');
        expect($html)->toContain('🗄️');
    });

    test('has timers tab', function () {
        $html = DebugToolbar::render();
        
        expect($html)->toContain('Timers');
        expect($html)->toContain('⏱️');
    });

    test('has memory tab', function () {
        $html = DebugToolbar::render();
        
        expect($html)->toContain('Memory');
        expect($html)->toContain('💾');
    });

    test('has contexts tab', function () {
        $html = DebugToolbar::render();
        
        expect($html)->toContain('Contexts');
        expect($html)->toContain('📁');
    });

    test('shows badge on tabs with data', function () {
        dump('test');
        QueryDebugger::log('SELECT * FROM users');
        
        $html = DebugToolbar::render();
        
        expect($html)->toContain('class="badge"');
    });
});

describe('Tab Content', function () {
    test('overview shows performance metrics', function () {
        $html = DebugToolbar::render();
        
        expect($html)->toContain('Request Overview');
        expect($html)->toContain('Performance');
        expect($html)->toContain('Memory');
    });

    test('dumps tab shows dump output', function () {
        dump(['key' => 'value'], 'Test Data');
        
        $html = DebugToolbar::render();
        
        expect($html)->toContain('Test Data');
    });

    test('queries tab shows SQL queries', function () {
        QueryDebugger::log('SELECT * FROM users', [], 15.5, 10);
        
        $html = DebugToolbar::render();
        
        expect($html)->toContain('SELECT * FROM users');
        expect($html)->toContain('15.5');
    });

    test('contexts tab shows contexts', function () {
        DebugContext::start('database');
        
        $html = DebugToolbar::render();
        
        expect($html)->toContain('Database');
    });

    test('shows empty state when no data', function () {
        $html = DebugToolbar::render();
        
        expect($html)->toContain('No dumps collected');
        expect($html)->toContain('No queries executed');
        expect($html)->toContain('No contexts created');
    });
});

describe('Visual Indicators', function () {
    test('highlights slow queries', function () {
        QueryDebugger::setSlowQueryThreshold(10.0);
        QueryDebugger::log('SLOW QUERY', [], 50.0);
        
        $html = DebugToolbar::render();
        
        expect($html)->toContain('#ef5350'); // red color for slow
    });

    test('shows warning for slow queries in header', function () {
        QueryDebugger::setSlowQueryThreshold(10.0);
        QueryDebugger::log('SLOW', [], 50.0);
        
        $html = DebugToolbar::render();
        
        expect($html)->toContain('slow)');
    });
});

describe('Helper Function', function () {
    test('render_debug_toolbar works', function () {
        $html = render_debug_toolbar();
        
        expect($html)->toBeString();
        expect($html)->toContain('debug-toolbar');
    });

    test('returns empty string when disabled', function () {
        DebugToolbar::enable(false);
        
        expect(render_debug_toolbar())->toBe('');
    });
});

describe('Production Mode', function () {
    test('toolbar disabled in production', function () {
        Environment::set(Environment::PRODUCTION);
        
        expect(DebugToolbar::render())->toBe('');
    });
});

describe('Integration', function () {
    test('collects data from all debug tools', function () {
        // Add various debug data
        dump('Variable dump');
        
        QueryDebugger::log('SELECT * FROM users', [], 25.0, 100);
        QueryDebugger::log('SELECT * FROM posts', [], 15.0, 50);
        
        DebugContext::start('api');
        DebugContext::add('request', 'GET /api/users');
        
        $html = DebugToolbar::render();
        
        // Check all sections are present
        expect($html)->toContain('Variable dump');
        expect($html)->toContain('SELECT * FROM users');
        expect($html)->toContain('api');
        expect($html)->toContain('2 queries');
    });

    test('shows comprehensive overview', function () {
        dump('test');
        QueryDebugger::log('SELECT 1', [], 5.0);
        DebugContext::start('test');
        
        $html = DebugToolbar::render();
        
        expect($html)->toContain('Dumps: 1');
        expect($html)->toContain('Queries: 1');
        expect($html)->toContain('Contexts: 1');
    });
});
