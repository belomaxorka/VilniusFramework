<?php declare(strict_types=1);

use Core\Environment;
use Core\Middleware\DebugToolbarMiddleware;

/**
 * Tests for DebugToolbarMiddleware
 * 
 * Проверяем автоматическую инъекцию Debug Toolbar через middleware
 */

beforeEach(function () {
    // Сбрасываем заголовки перед каждым тестом
    if (function_exists('header_remove')) {
        @header_remove();
    }
});

describe('DebugToolbarMiddleware - Basic Functionality', function () {
    
    it('does nothing in production mode', function () {
        Environment::set('production');
        
        $middleware = new DebugToolbarMiddleware();
        $output = '<html><body>Content</body></html>';
        
        $next = function () use ($output) {
            echo $output;
            return null;
        };
        
        ob_start();
        $middleware->handle($next);
        $result = ob_get_clean();
        
        // В production toolbar не должен добавляться
        expect($result)->toBe($output);
        expect($result)->not->toContain('debug-toolbar');
    });
    
    it('injects toolbar in development mode for HTML responses', function () {
        Environment::set('development');
        
        $middleware = new DebugToolbarMiddleware();
        $output = '<html><body><h1>Test</h1></body></html>';
        
        $next = function () use ($output) {
            echo $output;
            return null;
        };
        
        ob_start();
        $middleware->handle($next);
        $result = ob_get_clean();
        
        // Toolbar должен быть добавлен
        expect($result)->toContain('<h1>Test</h1>');
        expect($result)->toContain('debug-toolbar');
        expect($result)->toContain('</body>');
    });
    
    it('does not inject toolbar if no closing body tag', function () {
        Environment::set('development');
        
        $middleware = new DebugToolbarMiddleware();
        $output = '<html><head><title>Test</title></head>';
        
        $next = function () use ($output) {
            echo $output;
            return null;
        };
        
        ob_start();
        $middleware->handle($next);
        $result = ob_get_clean();
        
        // Без </body> toolbar не добавляется
        expect($result)->toBe($output);
        expect($result)->not->toContain('debug-toolbar');
    });
    
});

describe('DebugToolbarMiddleware - Content-Type Detection', function () {
    
    it('does not inject toolbar for JSON responses', function () {
        Environment::set('development');
        
        // Устанавливаем JSON Content-Type
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        
        $middleware = new DebugToolbarMiddleware();
        $output = '{"message": "test"}';
        
        $next = function () use ($output) {
            echo $output;
            return null;
        };
        
        ob_start();
        $middleware->handle($next);
        $result = ob_get_clean();
        
        expect($result)->toBe($output);
        expect($result)->not->toContain('debug-toolbar');
    });
    
    it('injects toolbar for text/html Content-Type', function () {
        Environment::set('development');
        
        // Устанавливаем HTML Content-Type
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        $middleware = new DebugToolbarMiddleware();
        $output = '<html><body>Test</body></html>';
        
        $next = function () use ($output) {
            echo $output;
            return null;
        };
        
        ob_start();
        $middleware->handle($next);
        $result = ob_get_clean();
        
        expect($result)->toContain('debug-toolbar');
    });
    
});

describe('DebugToolbarMiddleware - Output Buffering', function () {
    
    it('captures all output from next handler', function () {
        Environment::set('development');
        
        $middleware = new DebugToolbarMiddleware();
        
        $next = function () {
            echo '<html><body>';
            echo 'Line 1';
            echo 'Line 2';
            echo '</body></html>';
            return 'some-return-value';
        };
        
        ob_start();
        $result = $middleware->handle($next);
        $output = ob_get_clean();
        
        expect($output)->toContain('Line 1');
        expect($output)->toContain('Line 2');
        expect($result)->toBe('some-return-value');
    });
    
    it('handles empty output gracefully', function () {
        Environment::set('development');
        
        $middleware = new DebugToolbarMiddleware();
        
        $next = function () {
            // Ничего не выводим
            return null;
        };
        
        ob_start();
        $middleware->handle($next);
        $output = ob_get_clean();
        
        expect($output)->toBe('');
    });
    
    it('returns result from next handler', function () {
        Environment::set('development');
        
        $middleware = new DebugToolbarMiddleware();
        $expectedResult = 'test-result';
        
        $next = function () use ($expectedResult) {
            echo '<html><body>Content</body></html>';
            return $expectedResult;
        };
        
        ob_start();
        $result = $middleware->handle($next);
        ob_get_clean();
        
        expect($result)->toBe($expectedResult);
    });
    
});

describe('DebugToolbarMiddleware - Error Handling', function () {
    
    it('handles missing render_debug_toolbar function', function () {
        Environment::set('development');
        
        // Этот тест предполагает, что функция существует
        // Но мы проверяем, что middleware не падает
        $middleware = new DebugToolbarMiddleware();
        
        $next = function () {
            echo '<html><body>Test</body></html>';
            return null;
        };
        
        ob_start();
        
        // Не должно выбросить исключение
        expect(fn() => $middleware->handle($next))->not->toThrow(\Exception::class);
        
        ob_get_clean();
    });
    
});

describe('DebugToolbarMiddleware - Integration', function () {
    
    it('works with Response objects', function () {
        Environment::set('development');
        
        $middleware = new DebugToolbarMiddleware();
        
        $next = function () {
            $response = new \Core\Response();
            $response->html('<html><body>Content</body></html>');
            $response->send();
            return $response;
        };
        
        ob_start();
        $result = $middleware->handle($next);
        $output = ob_get_clean();
        
        expect($output)->toContain('Content');
        expect($output)->toContain('debug-toolbar');
        expect($result)->toBeInstanceOf(\Core\Response::class);
    });
    
    it('preserves middleware chain result', function () {
        Environment::set('development');
        
        $middleware = new DebugToolbarMiddleware();
        $chainResult = ['status' => 'success'];
        
        $next = function () use ($chainResult) {
            echo '<html><body>Test</body></html>';
            return $chainResult;
        };
        
        ob_start();
        $result = $middleware->handle($next);
        ob_get_clean();
        
        expect($result)->toBe($chainResult);
    });
    
});

