<?php declare(strict_types=1);

use Core\Request;
use Core\Response;

describe('Request & Response System', function () {
    
    describe('Response', function () {
        
        it('creates JSON response', function () {
            $response = new Response();
            $result = $response->json(['message' => 'Hello']);
            
            expect($result)->toBeInstanceOf(Response::class);
            expect($result->getStatusCode())->toBe(200);
            expect($result->getHeaders())->toHaveKey('Content-Type');
            expect($result->getContent())->toBe('{"message":"Hello"}');
        });
        
        it('creates HTML response', function () {
            $response = new Response();
            $result = $response->html('<h1>Hello</h1>');
            
            expect($result->getStatusCode())->toBe(200);
            expect($result->getHeaders()['Content-Type'])->toContain('text/html');
            expect($result->getContent())->toBe('<h1>Hello</h1>');
        });
        
        it('creates redirect response', function () {
            $response = new Response();
            $result = $response->redirect('/home', 302);
            
            expect($result->getStatusCode())->toBe(302);
            expect($result->getHeaders()['Location'])->toBe('/home');
        });
        
        it('sets custom status code', function () {
            $response = new Response();
            $result = $response->status(404);
            
            expect($result->getStatusCode())->toBe(404);
        });
        
        it('supports fluent interface', function () {
            $response = new Response();
            $result = $response
                ->status(201)
                ->header('X-Custom', 'Value')
                ->json(['data' => 'test']);
            
            expect($result->getStatusCode())->toBe(201);
            expect($result->getHeaders()['X-Custom'])->toBe('Value');
        });
        
        it('creates response with make()', function () {
            $response = Response::make('Hello World', 200, ['X-Test' => 'Value']);
            
            expect($response->getStatusCode())->toBe(200);
            expect($response->getContent())->toBe('Hello World');
            expect($response->getHeaders()['X-Test'])->toBe('Value');
        });
        
        it('creates JSON response with static method', function () {
            $response = Response::jsonResponse(['test' => 'data'], 201);
            
            expect($response->getStatusCode())->toBe(201);
            expect($response->getContent())->toContain('test');
        });
        
        it('sets multiple headers', function () {
            $response = new Response();
            $result = $response->withHeaders([
                'X-Custom-1' => 'Value1',
                'X-Custom-2' => 'Value2',
            ]);
            
            expect($result->getHeaders()['X-Custom-1'])->toBe('Value1');
            expect($result->getHeaders()['X-Custom-2'])->toBe('Value2');
        });
        
        it('creates no content response', function () {
            $response = new Response();
            $result = $response->noContent();
            
            expect($result->getStatusCode())->toBe(204);
            expect($result->getContent())->toBe('');
        });
        
        it('uses predefined status constants', function () {
            expect(Response::HTTP_OK)->toBe(200);
            expect(Response::HTTP_CREATED)->toBe(201);
            expect(Response::HTTP_NO_CONTENT)->toBe(204);
            expect(Response::HTTP_NOT_FOUND)->toBe(404);
            expect(Response::HTTP_UNAUTHORIZED)->toBe(401);
            expect(Response::HTTP_FORBIDDEN)->toBe(403);
            expect(Response::HTTP_UNPROCESSABLE_ENTITY)->toBe(422);
            expect(Response::HTTP_TOO_MANY_REQUESTS)->toBe(429);
            expect(Response::HTTP_INTERNAL_SERVER_ERROR)->toBe(500);
        });
    });
    
    describe('Request', function () {
        
        beforeEach(function () {
            // Очищаем глобальные переменные
            $_GET = [];
            $_POST = [];
            $_SERVER = [];
            $_COOKIE = [];
            $_FILES = [];
        });
        
        it('gets input from GET', function () {
            $_GET['name'] = 'John';
            
            $request = new Request();
            expect($request->query('name'))->toBe('John');
        });
        
        it('gets input from POST', function () {
            $_POST['email'] = 'test@example.com';
            
            $request = new Request();
            expect($request->post('email'))->toBe('test@example.com');
        });
        
        it('checks if parameter exists', function () {
            $_GET['name'] = 'John';
            
            $request = new Request();
            expect($request->has('name'))->toBeTrue();
            expect($request->has('email'))->toBeFalse();
        });
        
        it('gets all query params', function () {
            $_GET = ['name' => 'John', 'age' => '30'];
            
            $request = new Request();
            $all = $request->query();
            
            expect($all)->toBe(['name' => 'John', 'age' => '30']);
        });
        
        it('gets only specified keys', function () {
            $_POST = ['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret'];
            
            $request = new Request();
            $only = $request->only(['name', 'email']);
            
            expect($only)->toHaveKeys(['name', 'email']);
            expect($only)->not->toHaveKey('password');
        });
        
        it('gets all except specified keys', function () {
            $_POST = ['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret'];
            
            $request = new Request();
            $except = $request->except(['password']);
            
            expect($except)->toHaveKeys(['name', 'email']);
            expect($except)->not->toHaveKey('password');
        });
        
        it('checks if has all parameters', function () {
            $_POST = ['name' => 'John', 'email' => 'john@example.com'];
            
            $request = new Request();
            expect($request->hasAll(['name', 'email']))->toBeTrue();
            expect($request->hasAll(['name', 'phone']))->toBeFalse();
        });
        
        it('checks if has any parameter', function () {
            $_POST = ['name' => 'John'];
            
            $request = new Request();
            expect($request->hasAny(['name', 'email']))->toBeTrue();
            expect($request->hasAny(['phone', 'address']))->toBeFalse();
        });
        
        it('gets cookie value', function () {
            $_COOKIE['session'] = 'abc123';
            
            $request = new Request();
            expect($request->cookie('session'))->toBe('abc123');
        });
        
        it('gets all cookies', function () {
            $_COOKIE = ['session' => 'abc123', 'user' => 'john'];
            
            $request = new Request();
            expect($request->cookies())->toBe($_COOKIE);
        });
        
        it('uses magic get', function () {
            $_GET['name'] = 'John';
            $_POST['email'] = 'john@example.com';
            
            $request = new Request();
            expect($request->name)->toBe('John');
        });
        
        it('uses magic isset', function () {
            $_GET['name'] = 'John';
            
            $request = new Request();
            expect(isset($request->name))->toBeTrue();
            expect(isset($request->email))->toBeFalse();
        });
    });
    
    describe('Integration', function () {
        
        it('Request and Response work together', function () {
            $_POST['name'] = 'John';
            
            $request = new Request();
            $name = $request->post('name');
            
            $response = new Response();
            $result = $response->json(['greeting' => "Hello, {$name}"]);
            
            expect($result->getContent())->toBe('{"greeting":"Hello, John"}');
        });
        
    });
});

