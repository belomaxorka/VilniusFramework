<?php declare(strict_types=1);

use Core\Environment;

test('environment defaults to production when APP_ENV is not set', function () {
    // Очищаем кеш окружения
    Environment::clearCache();
    
    // Устанавливаем пустое значение
    \Core\Env::set('APP_ENV', '');
    
    expect(Environment::get())->toBe('production');
    expect(Environment::isProduction())->toBeTrue();
    expect(Environment::isDevelopment())->toBeFalse();
});

test('environment accepts valid values', function () {
    // Тестируем development
    \Core\Env::set('APP_ENV', 'development');
    expect(Environment::get())->toBe('development');
    expect(Environment::isDevelopment())->toBeTrue();
    expect(Environment::isProduction())->toBeFalse();
    
    // Тестируем production
    \Core\Env::set('APP_ENV', 'production');
    expect(Environment::get())->toBe('production');
    expect(Environment::isProduction())->toBeTrue();
    expect(Environment::isDevelopment())->toBeFalse();
    
    // Тестируем testing
    \Core\Env::set('APP_ENV', 'testing');
    expect(Environment::get())->toBe('testing');
    expect(Environment::isTesting())->toBeTrue();
});

test('environment defaults to production for invalid values', function () {
    \Core\Env::set('APP_ENV', 'invalid');
    
    expect(Environment::get())->toBe('production');
    expect(Environment::isProduction())->toBeTrue();
});
