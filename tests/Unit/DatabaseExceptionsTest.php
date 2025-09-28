<?php declare(strict_types=1);

use Core\Database\Exceptions\DatabaseException;
use Core\Database\Exceptions\ConnectionException;
use Core\Database\Exceptions\QueryException;

describe('DatabaseException', function (): void {
    it('extends Exception', function (): void {
        $exception = new DatabaseException('Test message');
        expect($exception)->toBeInstanceOf(Exception::class);
    });

    it('can be instantiated with message', function (): void {
        $message = 'Database error occurred';
        $exception = new DatabaseException($message);
        
        expect($exception->getMessage())->toBe($message);
    });

    it('can be instantiated with message and code', function (): void {
        $message = 'Database error occurred';
        $code = 500;
        $exception = new DatabaseException($message, $code);
        
        expect($exception->getMessage())->toBe($message);
        expect($exception->getCode())->toBe($code);
    });

    it('can be instantiated with message, code and previous exception', function (): void {
        $previousException = new Exception('Previous error');
        $message = 'Database error occurred';
        $code = 500;
        $exception = new DatabaseException($message, $code, $previousException);
        
        expect($exception->getMessage())->toBe($message);
        expect($exception->getCode())->toBe($code);
        expect($exception->getPrevious())->toBe($previousException);
    });

    it('can be thrown and caught', function (): void {
        expect(fn() => throw new DatabaseException('Test error'))
            ->toThrow(DatabaseException::class, 'Test error');
    });

    it('can be caught as generic Exception', function (): void {
        expect(fn() => throw new DatabaseException('Test error'))
            ->toThrow(Exception::class, 'Test error');
    });
});

describe('ConnectionException', function (): void {
    it('extends DatabaseException', function (): void {
        $exception = new ConnectionException('Connection error');
        expect($exception)->toBeInstanceOf(DatabaseException::class);
    });

    it('can be instantiated with message', function (): void {
        $message = 'Connection failed';
        $exception = new ConnectionException($message);
        
        expect($exception->getMessage())->toBe($message);
    });

    it('can be instantiated with message and code', function (): void {
        $message = 'Connection failed';
        $code = 1001;
        $exception = new ConnectionException($message, $code);
        
        expect($exception->getMessage())->toBe($message);
        expect($exception->getCode())->toBe($code);
    });

    it('can be instantiated with message, code and previous exception', function (): void {
        $previousException = new Exception('Network error');
        $message = 'Connection failed';
        $code = 1001;
        $exception = new ConnectionException($message, $code, $previousException);
        
        expect($exception->getMessage())->toBe($message);
        expect($exception->getCode())->toBe($code);
        expect($exception->getPrevious())->toBe($previousException);
    });

    it('can be thrown and caught', function (): void {
        expect(fn() => throw new ConnectionException('Connection failed'))
            ->toThrow(ConnectionException::class, 'Connection failed');
    });

    it('can be caught as DatabaseException', function (): void {
        expect(fn() => throw new ConnectionException('Connection failed'))
            ->toThrow(DatabaseException::class, 'Connection failed');
    });

    it('can be caught as generic Exception', function (): void {
        expect(fn() => throw new ConnectionException('Connection failed'))
            ->toThrow(Exception::class, 'Connection failed');
    });

    it('can be used in try-catch blocks', function (): void {
        try {
            throw new ConnectionException('Connection timeout');
        } catch (ConnectionException $e) {
            expect($e->getMessage())->toBe('Connection timeout');
        }
    });

    it('can be used with different exception types in catch blocks', function (): void {
        $caught = false;
        
        try {
            throw new ConnectionException('Connection error');
        } catch (DatabaseException $e) {
            $caught = true;
            expect($e->getMessage())->toBe('Connection error');
        }
        
        expect($caught)->toBeTrue();
    });
});

describe('QueryException', function (): void {
    it('extends DatabaseException', function (): void {
        $exception = new QueryException('Query error');
        expect($exception)->toBeInstanceOf(DatabaseException::class);
    });

    it('can be instantiated with message', function (): void {
        $message = 'Query execution failed';
        $exception = new QueryException($message);
        
        expect($exception->getMessage())->toBe($message);
    });

    it('can be instantiated with message and code', function (): void {
        $message = 'Query execution failed';
        $code = 2001;
        $exception = new QueryException($message, $code);
        
        expect($exception->getMessage())->toBe($message);
        expect($exception->getCode())->toBe($code);
    });

    it('can be instantiated with message, code and previous exception', function (): void {
        $previousException = new Exception('SQL syntax error');
        $message = 'Query execution failed';
        $code = 2001;
        $exception = new QueryException($message, $code, $previousException);
        
        expect($exception->getMessage())->toBe($message);
        expect($exception->getCode())->toBe($code);
        expect($exception->getPrevious())->toBe($previousException);
    });

    it('can be thrown and caught', function (): void {
        expect(fn() => throw new QueryException('Query failed'))
            ->toThrow(QueryException::class, 'Query failed');
    });

    it('can be caught as DatabaseException', function (): void {
        expect(fn() => throw new QueryException('Query failed'))
            ->toThrow(DatabaseException::class, 'Query failed');
    });

    it('can be caught as generic Exception', function (): void {
        expect(fn() => throw new QueryException('Query failed'))
            ->toThrow(Exception::class, 'Query failed');
    });

    it('can be used in try-catch blocks', function (): void {
        try {
            throw new QueryException('Invalid SQL syntax');
        } catch (QueryException $e) {
            expect($e->getMessage())->toBe('Invalid SQL syntax');
        }
    });

    it('can be used with different exception types in catch blocks', function (): void {
        $caught = false;
        
        try {
            throw new QueryException('Query error');
        } catch (DatabaseException $e) {
            $caught = true;
            expect($e->getMessage())->toBe('Query error');
        }
        
        expect($caught)->toBeTrue();
    });
});

describe('Exception hierarchy', function (): void {
    it('maintains proper inheritance chain', function (): void {
        $connectionException = new ConnectionException('Connection error');
        $queryException = new QueryException('Query error');
        
        // ConnectionException should be instanceof DatabaseException and Exception
        expect($connectionException)->toBeInstanceOf(DatabaseException::class);
        expect($connectionException)->toBeInstanceOf(Exception::class);
        
        // QueryException should be instanceof DatabaseException and Exception
        expect($queryException)->toBeInstanceOf(DatabaseException::class);
        expect($queryException)->toBeInstanceOf(Exception::class);
    });

    it('allows catching specific exceptions', function (): void {
        $exceptions = [
            new ConnectionException('Connection failed'),
            new QueryException('Query failed'),
        ];
        
        foreach ($exceptions as $exception) {
            $caught = false;
            
            try {
                throw $exception;
            } catch (DatabaseException $e) {
                $caught = true;
                expect($e)->toBeInstanceOf(DatabaseException::class);
            }
            
            expect($caught)->toBeTrue();
        }
    });

    it('allows catching generic exceptions', function (): void {
        $exceptions = [
            new DatabaseException('Database error'),
            new ConnectionException('Connection error'),
            new QueryException('Query error'),
        ];
        
        foreach ($exceptions as $exception) {
            $caught = false;
            
            try {
                throw $exception;
            } catch (Exception $e) {
                $caught = true;
                expect($e)->toBeInstanceOf(Exception::class);
            }
            
            expect($caught)->toBeTrue();
        }
    });
});

describe('Exception messages and context', function (): void {
    it('preserves exception messages', function (): void {
        $messages = [
            'Database connection failed',
            'Query execution timeout',
            'Invalid SQL syntax',
            'Table does not exist',
            'Access denied',
        ];
        
        foreach ($messages as $message) {
            $exception = new DatabaseException($message);
            expect($exception->getMessage())->toBe($message);
        }
    });

    it('preserves exception codes', function (): void {
        $codes = [100, 200, 300, 404, 500];
        
        foreach ($codes as $code) {
            $exception = new DatabaseException('Test error', $code);
            expect($exception->getCode())->toBe($code);
        }
    });

    it('preserves previous exceptions', function (): void {
        $originalException = new Exception('Original error');
        $databaseException = new DatabaseException('Database error', 0, $originalException);
        
        expect($databaseException->getPrevious())->toBe($originalException);
        expect($databaseException->getPrevious()->getMessage())->toBe('Original error');
    });

    it('can be chained with multiple exceptions', function (): void {
        $originalException = new Exception('Original error');
        $connectionException = new ConnectionException('Connection error', 0, $originalException);
        $queryException = new QueryException('Query error', 0, $connectionException);
        
        expect($queryException->getPrevious())->toBe($connectionException);
        expect($queryException->getPrevious()->getPrevious())->toBe($originalException);
    });
});
