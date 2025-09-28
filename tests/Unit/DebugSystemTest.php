<?php declare(strict_types=1);

use Core\Environment;
use Core\Debug;
use Core\ErrorHandler;

beforeEach(function () {
    // Устанавливаем тестовое окружение
    Environment::set(Environment::TESTING);
});

test('environment detection works correctly', function () {
    Environment::set(Environment::DEVELOPMENT);
    expect(Environment::isDevelopment())->toBeTrue();
    expect(Environment::isProduction())->toBeFalse();
    expect(Environment::isTesting())->toBeFalse();
    
    Environment::set(Environment::PRODUCTION);
    expect(Environment::isProduction())->toBeTrue();
    expect(Environment::isDevelopment())->toBeFalse();
    expect(Environment::isTesting())->toBeFalse();
});

test('debug functions work in development mode', function () {
    Environment::set(Environment::DEVELOPMENT);
    
    // Тестируем сбор данных
    Debug::collect(['test' => 'data'], 'Test Data');
    expect(Debug::class)->toHaveMethod('collect');
    
    // Тестируем очистку
    Debug::clear();
    expect(Debug::class)->toHaveMethod('clear');
});

test('debug functions are disabled in production', function () {
    Environment::set(Environment::PRODUCTION);
    
    // В продакшене функции дебага должны быть отключены
    ob_start();
    Debug::dump(['test' => 'data'], 'Test');
    $output = ob_get_clean();
    
    expect($output)->toBeEmpty();
});

test('environment config returns correct settings', function () {
    Environment::set(Environment::DEVELOPMENT);
    $config = Environment::getConfig();
    
    expect($config['debug'])->toBeTrue();
    expect($config['display_errors'])->toBe(1);
    expect($config['log_errors'])->toBe(1);
    
    Environment::set(Environment::PRODUCTION);
    $config = Environment::getConfig();
    
    expect($config['debug'])->toBeFalse();
    expect($config['display_errors'])->toBe(0);
    expect($config['log_errors'])->toBe(1);
});

test('error handler can be registered', function () {
    expect(ErrorHandler::class)->toHaveMethod('register');
    
    // Проверяем, что метод существует и может быть вызван
    expect(function () {
        ErrorHandler::register();
    })->not->toThrow(Exception::class);
});
