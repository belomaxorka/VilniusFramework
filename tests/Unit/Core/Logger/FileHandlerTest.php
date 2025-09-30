<?php

use Core\Logger\FileHandler;

test('FileHandler создает лог файл', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    
    $handler->handle('info', 'Test message');
    
    expect(file_exists($logFile))->toBeTrue();
    
    @unlink($logFile);
});

test('FileHandler записывает корректный формат лога', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    
    $handler->handle('error', 'Test error message');
    
    $content = file_get_contents($logFile);
    
    // Проверяем формат: [YYYY-MM-DD HH:MM:SS] [LEVEL] Message
    expect($content)->toMatch('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/');
    expect(str_contains($content, '[ERROR]'))->toBeTrue();
    expect(str_contains($content, 'Test error message'))->toBeTrue();
    
    @unlink($logFile);
});

test('FileHandler добавляет записи в существующий файл', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    
    $handler->handle('info', 'First message');
    $handler->handle('warning', 'Second message');
    
    $content = file_get_contents($logFile);
    
    expect(str_contains($content, 'First message'))->toBeTrue();
    expect(str_contains($content, 'Second message'))->toBeTrue();
    
    @unlink($logFile);
});

test('FileHandler корректно обрабатывает разные уровни логирования', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    
    $handler->handle('debug', 'Debug message');
    $handler->handle('info', 'Info message');
    $handler->handle('warning', 'Warning message');
    $handler->handle('error', 'Error message');
    $handler->handle('critical', 'Critical message');
    
    $content = file_get_contents($logFile);
    
    expect(str_contains($content, '[DEBUG]'))->toBeTrue();
    expect(str_contains($content, '[INFO]'))->toBeTrue();
    expect(str_contains($content, '[WARNING]'))->toBeTrue();
    expect(str_contains($content, '[ERROR]'))->toBeTrue();
    expect(str_contains($content, '[CRITICAL]'))->toBeTrue();
    
    @unlink($logFile);
});

test('FileHandler создает директорию если не существует', function () {
    $dir = sys_get_temp_dir() . '/test_logs_' . uniqid();
    $logFile = $dir . '/app.log';
    
    expect(is_dir($dir))->toBeFalse();
    
    $handler = new FileHandler($logFile);
    $handler->handle('info', 'Test');
    
    expect(file_exists($logFile))->toBeTrue();
    
    @unlink($logFile);
    @rmdir($dir);
});
