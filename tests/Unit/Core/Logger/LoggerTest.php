<?php

use Core\Logger;
use Core\Logger\FileHandler;
use Core\Logger\LogHandlerInterface;

beforeEach(function () {
    // Полностью очищаем Logger перед каждым тестом
    Logger::clearHandlers();
    Logger::setMinLevel('debug');
});

test('can add log handler', function () {
    $handler = new FileHandler(sys_get_temp_dir() . '/test.log');
    Logger::addHandler($handler);

    $handlers = Logger::getHandlers();
    expect($handlers)->toHaveCount(1);
    expect($handlers[0])->toBeInstanceOf(LogHandlerInterface::class);
});

test('can set minimum logging level', function () {
    Logger::setMinLevel('error');
    expect(Logger::getMinLevel())->toBe('error');
});

test('logs below minimum level are not written', function () {
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

test('debug() method works correctly', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);

    Logger::debug('Debug test');

    $content = file_get_contents($logFile);
    expect(str_contains($content, '[DEBUG]'))->toBeTrue();
    expect(str_contains($content, 'Debug test'))->toBeTrue();

    @unlink($logFile);
});

test('info() method works correctly', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);

    Logger::info('Info test');

    $content = file_get_contents($logFile);
    expect(str_contains($content, '[INFO]'))->toBeTrue();
    expect(str_contains($content, 'Info test'))->toBeTrue();

    @unlink($logFile);
});

test('warning() method works correctly', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);

    Logger::warning('Warning test');

    $content = file_get_contents($logFile);
    expect(str_contains($content, '[WARNING]'))->toBeTrue();
    expect(str_contains($content, 'Warning test'))->toBeTrue();

    @unlink($logFile);
});

test('error() method works correctly', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);

    Logger::error('Error test');

    $content = file_get_contents($logFile);
    expect(str_contains($content, '[ERROR]'))->toBeTrue();
    expect(str_contains($content, 'Error test'))->toBeTrue();

    @unlink($logFile);
});

test('critical() method works correctly', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    Logger::addHandler($handler);

    Logger::critical('Critical test');

    $content = file_get_contents($logFile);
    expect(str_contains($content, '[CRITICAL]'))->toBeTrue();
    expect(str_contains($content, 'Critical test'))->toBeTrue();

    @unlink($logFile);
});

test('context data is interpolated into message', function () {
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

test('arrays in context are converted to JSON', function () {
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

test('can use multiple handlers simultaneously', function () {
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

test('logging levels follow hierarchy', function () {
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

test('clearHandlers() clears all handlers', function () {
    Logger::addHandler(new FileHandler(sys_get_temp_dir() . '/test.log'));
    Logger::addHandler(new FileHandler(sys_get_temp_dir() . '/test2.log'));

    expect(Logger::getHandlers())->toHaveCount(2);

    Logger::clearHandlers();

    expect(Logger::getHandlers())->toHaveCount(0);
});

describe('Toolbar Message Feature', function () {
    test('uses _toolbar_message for debug toolbar', function () {
        Logger::info('Full message with {placeholder}', [
            'placeholder' => 'value',
            '_toolbar_message' => 'Short message',
        ]);
        
        $logs = Logger::getLogs();
        
        expect($logs)->toHaveCount(1);
        expect($logs[0]['message'])->toBe('Short message');
        expect($logs[0]['context'])->toHaveKey('placeholder');
        expect($logs[0]['context'])->not->toHaveKey('_toolbar_message');
    });
    
    test('uses full message when no _toolbar_message provided', function () {
        Logger::info('Regular message without toolbar override');
        
        $logs = Logger::getLogs();
        
        expect($logs)->toHaveCount(1);
        expect($logs[0]['message'])->toBe('Regular message without toolbar override');
    });
    
    test('interpolates message for file handlers but not toolbar', function () {
        $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
        $handler = new FileHandler($logFile);
        Logger::addHandler($handler);
        
        Logger::warning('Full: {key}', [
            'key' => 'value',
            '_toolbar_message' => 'Short message',
        ]);
        
        $logs = Logger::getLogs();
        $fileContent = file_get_contents($logFile);
        
        // Debug Toolbar: короткое сообщение
        expect($logs[0]['message'])->toBe('Short message');
        
        // Файловый лог: интерполированное сообщение
        expect($fileContent)->toContain('Full: value');
        expect($fileContent)->not->toContain('{key}');
        
        @unlink($logFile);
    });
    
    test('preserves context without _toolbar_message field', function () {
        Logger::warning('Message', [
            'label' => 'Test',
            'type' => 'array',
            'file' => 'test.php',
            '_toolbar_message' => 'Short',
        ]);
        
        $logs = Logger::getLogs();
        $context = $logs[0]['context'];
        
        expect($context)->toHaveKey('label');
        expect($context)->toHaveKey('type');
        expect($context)->toHaveKey('file');
        expect($context)->not->toHaveKey('_toolbar_message');
    });
    
    test('works with dump server unavailable scenario', function () {
        $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
        $handler = new FileHandler($logFile);
        Logger::addHandler($handler);
        
        // Симулируем что делает DumpClient
        Logger::warning(
            'Dump Server unavailable, data logged to file: label={label}, type={type}, file={file}:{line}, log={log_file}',
            [
                'label' => 'User Data',
                'type' => 'array',
                'file' => 'app/Controllers/HomeController.php',
                'line' => 25,
                'log_file' => 'storage/logs/dumps.log',
                '_toolbar_message' => 'Dump Server unavailable, data logged to file',
            ]
        );
        
        $logs = Logger::getLogs();
        $fileContent = file_get_contents($logFile);
        
        // Debug Toolbar: короткое без плейсхолдеров
        expect($logs[0]['message'])->toBe('Dump Server unavailable, data logged to file');
        expect($logs[0]['message'])->not->toContain('{label}');
        expect($logs[0]['context'])->toHaveKey('label');
        expect($logs[0]['context']['label'])->toBe('User Data');
        
        // Файловый лог: полное с интерполяцией
        expect($fileContent)->toContain('label=User Data');
        expect($fileContent)->toContain('type=array');
        expect($fileContent)->toContain('file=app/Controllers/HomeController.php:25');
        expect($fileContent)->not->toContain('{label}');
        expect($fileContent)->not->toContain('{type}');
        
        @unlink($logFile);
    });
});
