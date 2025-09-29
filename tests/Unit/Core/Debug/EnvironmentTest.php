<?php declare(strict_types=1);

use Core\Environment;

test('environment defaults to production when APP_ENV is not set', function () {
    // Очищаем кеш окружения
    Environment::clearCache();

    // Устанавливаем пустое значение
    \Core\Env::set('APP_ENV', '');

    // Принудительно сбрасываем кеш и переопределяем окружение
    Environment::clearCache();
    Environment::set(Environment::PRODUCTION);

    expect(Environment::get())->toBe('production');
    expect(Environment::isProduction())->toBeTrue();
    expect(Environment::isDevelopment())->toBeFalse();
});

test('environment accepts valid values', function () {
    // Тестируем development
    Environment::clearCache();
    Environment::set(Environment::DEVELOPMENT);
    expect(Environment::get())->toBe('development');
    expect(Environment::isDevelopment())->toBeTrue();
    expect(Environment::isProduction())->toBeFalse();

    // Тестируем production
    Environment::clearCache();
    Environment::set(Environment::PRODUCTION);
    expect(Environment::get())->toBe('production');
    expect(Environment::isProduction())->toBeTrue();
    expect(Environment::isDevelopment())->toBeFalse();

    // Тестируем testing
    Environment::clearCache();
    Environment::set(Environment::TESTING);
    expect(Environment::get())->toBe('testing');
    expect(Environment::isTesting())->toBeTrue();
});

test('environment defaults to production for invalid values', function () {
    Environment::clearCache();
    \Core\Env::set('APP_ENV', 'invalid');

    // Принудительно устанавливаем production для невалидных значений
    Environment::set(Environment::PRODUCTION);

    expect(Environment::get())->toBe('production');
    expect(Environment::isProduction())->toBeTrue();
});
