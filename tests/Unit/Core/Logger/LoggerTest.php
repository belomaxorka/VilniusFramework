<?php

use Core\Logger;
use Core\Logger\FileHandler;
use Core\Logger\LogHandlerInterface;

beforeEach(function () {
    // Очищаем обработчики перед каждым тестом
    Logger::clearHandlers();
});

afterEach(function () {
    // Очищаем после тестов
    Logger::clearHandlers();
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
    expect($content)->toContain('Error message');
    expect($content)->not->toContain('Debug message');
    
    @unlink($logFile);
});

test('метод debug() работает корректно', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);
    
    Logger::debug('Debug test');
    
    $content = file_get_contents($logFile);
    expect($content)->toContain('[DEBUG]');
    expect($content)->toContain('Debug test');
    
    @unlink($logFile);
});

test('метод info() работает корректно', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);
    
    Logger::info('Info test');
    
    $content = file_get_contents($logFile);
    expect($content)->toContain('[INFO]');
    expect($content)->toContain('Info test');
    
    @unlink($logFile);
});

test('метод warning() работает корректно', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);
    
    Logger::warning('Warning test');
    
    $content = file_get_contents($logFile);
    expect($content)->toContain('[WARNING]');
    expect($content)->toContain('Warning test');
    
    @unlink($logFile);
});

test('метод error() работает корректно', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);
    
    Logger::error('Error test');
    
    $content = file_get_contents($logFile);
    expect($content)->toContain('[ERROR]');
    expect($content)->toContain('Error test');
    
    @unlink($logFile);
});

test('метод critical() работает корректно', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);
    
    Logger::critical('Critical test');
    
    $content = file_get_contents($logFile);
    expect($content)->toContain('[CRITICAL]');
    expect($content)->toContain('Critical test');
    
    @unlink($logFile);
});

test('контекстные данные интерполируются в сообщение', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);
    
    Logger::info('User {username} logged in from {ip}', [
        'username' => 'John',
        'ip' => '127.0.0.1'
    ]);
    
    $content = file_get_contents($logFile);
    expect($content)->toContain('User John logged in from 127.0.0.1');
    
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
    expect($content)->toContain('{"key":"value","number":123}');
    
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
    
    expect($content1)->toContain('Test message');
    expect($content2)->toContain('Test message');
    
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
    
    expect($content)->not->toContain('debug');
    expect($content)->not->toContain('info');
    expect($content)->toContain('warning');
    expect($content)->toContain('error');
    expect($content)->toContain('critical');
    
    @unlink($logFile);
});

test('clearHandlers() очищает все обработчики', function () {
    Logger::addHandler(new FileHandler(sys_get_temp_dir() . '/test.log'));
    Logger::addHandler(new FileHandler(sys_get_temp_dir() . '/test2.log'));
    
    expect(Logger::getHandlers())->toHaveCount(2);
    
    Logger::clearHandlers();
    
    expect(Logger::getHandlers())->toHaveCount(0);
});
