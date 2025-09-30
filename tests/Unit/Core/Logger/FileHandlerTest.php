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
    expect($content)->toContain('[ERROR]');
    expect($content)->toContain('Test error message');
    
    @unlink($logFile);
});

test('FileHandler добавляет записи в существующий файл', function () {
    $logFile = sys_get_temp_dir() . '/test_' . uniqid() . '.log';
    $handler = new FileHandler($logFile);
    
    $handler->handle('info', 'First message');
    $handler->handle('warning', 'Second message');
    
    $content = file_get_contents($logFile);
    
    expect($content)->toContain('First message');
    expect($content)->toContain('Second message');
    
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
    
    expect($content)->toContain('[DEBUG]');
    expect($content)->toContain('[INFO]');
    expect($content)->toContain('[WARNING]');
    expect($content)->toContain('[ERROR]');
    expect($content)->toContain('[CRITICAL]');
    
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
