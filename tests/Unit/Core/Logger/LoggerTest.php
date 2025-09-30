<?php

use Core\Logger;
use Core\Logger\FileHandler;
use Core\Logger\LogHandlerInterface;

beforeEach(function () {
    // Полностью очищаем Logger перед каждым тестом
    Logger::clearHandlers();
    Logger::setMinLevel('debug');
});

test('можно добавить обработчик логов', function () {
    $handler = new FileHandler(sys_get_temp_dir() . '/test.log');
    Logger::addHandler($handler);

    $handlers = Logger::getHandlers();
    expect($handlers)->toHaveCount(1);
    expect($handlers[0])->toBeInstanceOf(LogHandlerInterface::class);
});

test('можно установить минимальный уровень логирования', function () {
    Logger::setMinLevel('error');
    expect(Logger::getMinLevel())->toBe('error');
});

test('логи ниже минимального уровня не записываются', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);
    Logger::setMinLevel('error');

    Logger::debug('Debug message');
    Logger::info('Info message');
    Logger::warning('Warning message');

    expect(file_exists($logFile))->toBeFalse();

    Logger::error('Error message');
    expect(file_exists($logFile))->toBeTrue();

    $content = file_get_contents($logFile);
    expect(str_contains($content, 'Error message'))->toBeTrue();
    expect(str_contains($content, 'Debug message'))->toBeFalse();

    @unlink($logFile);
});

test('метод debug() работает корректно', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);

    Logger::debug('Debug test');

    $content = file_get_contents($logFile);
    expect(str_contains($content, '[DEBUG]'))->toBeTrue();
    expect(str_contains($content, 'Debug test'))->toBeTrue();

    @unlink($logFile);
});

test('метод info() работает корректно', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);

    Logger::info('Info test');

    $content = file_get_contents($logFile);
    expect(str_contains($content, '[INFO]'))->toBeTrue();
    expect(str_contains($content, 'Info test'))->toBeTrue();

    @unlink($logFile);
});

test('метод warning() работает корректно', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);

    Logger::warning('Warning test');

    $content = file_get_contents($logFile);
    expect(str_contains($content, '[WARNING]'))->toBeTrue();
    expect(str_contains($content, 'Warning test'))->toBeTrue();

    @unlink($logFile);
});

test('метод error() работает корректно', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);

    Logger::error('Error test');

    $content = file_get_contents($logFile);
    expect(str_contains($content, '[ERROR]'))->toBeTrue();
    expect(str_contains($content, 'Error test'))->toBeTrue();

    @unlink($logFile);
});

test('метод critical() работает корректно', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);

    Logger::critical('Critical test');

    $content = file_get_contents($logFile);
    expect(str_contains($content, '[CRITICAL]'))->toBeTrue();
    expect(str_contains($content, 'Critical test'))->toBeTrue();

    @unlink($logFile);
});

test('контекстные данные интерполируются в сообщение', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';

    // Убедимся что файл не существует
    if (file_exists($logFile)) {
        @unlink($logFile);
    }

    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);

    // Убедимся что обработчик добавлен
    expect(Logger::getHandlers())->toHaveCount(1);

    Logger::info('User {username} logged in from {ip}', [
        'username' => 'John',
        'ip' => '127.0.0.1'
    ]);

    // Проверяем что файл создан
    expect(file_exists($logFile))->toBeTrue();

    $content = file_get_contents($logFile);

    // Проверяем что в логе есть нужный текст
    expect(str_contains($content, 'User John logged in from 127.0.0.1'))->toBeTrue();

    @unlink($logFile);
});

test('массивы в контексте преобразуются в JSON', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);

    Logger::info('Data: {data}', [
        'data' => ['key' => 'value', 'number' => 123]
    ]);

    $content = file_get_contents($logFile);
    expect(str_contains($content, '{"key":"value","number":123}'))->toBeTrue();

    @unlink($logFile);
});

test('можно использовать несколько обработчиков одновременно', function () {
    $logFile1 = sys_get_temp_dir() . '/test1_' . uniqid() . '.log';
    $logFile2 = sys_get_temp_dir() . '/test2_' . uniqid() . '.log';

    Logger::addHandler(new FileHandler($logFile1));
    Logger::addHandler(new FileHandler($logFile2));

    Logger::info('Test message');

    expect(file_exists($logFile1))->toBeTrue();
    expect(file_exists($logFile2))->toBeTrue();

    $content1 = file_get_contents($logFile1);
    $content2 = file_get_contents($logFile2);

    expect(str_contains($content1, 'Test message'))->toBeTrue();
    expect(str_contains($content2, 'Test message'))->toBeTrue();

    @unlink($logFile1);
    @unlink($logFile2);
});

test('уровни логирования соблюдают иерархию', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);
    Logger::setMinLevel('warning');

    Logger::debug('debug');
    Logger::info('info');
    Logger::warning('warning');
    Logger::error('error');
    Logger::critical('critical');

    $content = file_get_contents($logFile);

    expect(str_contains($content, 'debug'))->toBeFalse();
    expect(str_contains($content, 'info'))->toBeFalse();
    expect(str_contains($content, 'warning'))->toBeTrue();
    expect(str_contains($content, 'error'))->toBeTrue();
    expect(str_contains($content, 'critical'))->toBeTrue();

    @unlink($logFile);
});

test('clearHandlers() очищает все обработчики', function () {
    Logger::addHandler(new FileHandler(sys_get_temp_dir() . '/test.log'));
    Logger::addHandler(new FileHandler(sys_get_temp_dir() . '/test2.log'));

    expect(Logger::getHandlers())->toHaveCount(2);

    Logger::clearHandlers();

    expect(Logger::getHandlers())->toHaveCount(0);
});
