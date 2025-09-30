<?php

use Core\Logger;
use Core\Config;
use Core\Logger\FileHandler;

beforeEach(function () {
    Logger::clearHandlers();
});

afterEach(function () {
    Logger::clearHandlers();
});

test('Logger initializes from configuration', function () {
    // Создаем временную конфигурацию
    $tempDir = createTempConfigDir([
        'logging.php' => [
            'default' => 'file',
            'min_level' => 'debug',
            'channels' => 'file',
            'drivers' => [
                'file' => [
                    'driver' => 'file',
                    'path' => sys_get_temp_dir() . '/test.log',
                ]
            ]
        ]
    ]);

    Config::load($tempDir, 'testing');

    Logger::init();

    $handlers = Logger::getHandlers();
    expect($handlers)->toHaveCount(1);
    expect($handlers[0])->toBeInstanceOf(FileHandler::class);

    deleteDir($tempDir);
});

test('Logger uses fallback if configuration is empty', function () {
    // Очищаем конфигурацию
    $tempDir = createTempConfigDir([]);
    Config::load($tempDir, 'testing');

    Logger::init();

    $handlers = Logger::getHandlers();
    expect($handlers)->toHaveCount(1);

    deleteDir($tempDir);
});

test('Logger can initialize multiple drivers', function () {
    $tempDir = createTempConfigDir([
        'logging.php' => [
            'default' => 'file',
            'min_level' => 'debug',
            'channels' => 'file,slack',
            'drivers' => [
                'file' => [
                    'driver' => 'file',
                    'path' => sys_get_temp_dir() . '/test.log',
                ],
                'slack' => [
                    'driver' => 'slack',
                    'webhook_url' => 'https://hooks.slack.com/test',
                    'channel' => '#logs',
                ]
            ]
        ]
    ]);

    Config::load($tempDir, 'testing');

    Logger::init();

    $handlers = Logger::getHandlers();
    expect($handlers)->toHaveCount(2);

    deleteDir($tempDir);
});

test('Logger skips drivers with invalid configuration', function () {
    $tempDir = createTempConfigDir([
        'logging.php' => [
            'default' => 'file',
            'min_level' => 'debug',
            'channels' => 'file,slack,telegram',
            'drivers' => [
                'file' => [
                    'driver' => 'file',
                    'path' => sys_get_temp_dir() . '/test.log',
                ],
                'slack' => [
                    'driver' => 'slack',
                    'webhook_url' => '', // Пустой webhook - будет пропущен
                ],
                'telegram' => [
                    'driver' => 'telegram',
                    'bot_token' => '', // Пустой токен - будет пропущен
                    'chat_id' => '',
                ]
            ]
        ]
    ]);

    Config::load($tempDir, 'testing');

    Logger::init();

    $handlers = Logger::getHandlers();
    expect($handlers)->toHaveCount(1); // Только file handler

    deleteDir($tempDir);
});

test('Logger does not initialize twice', function () {
    $tempDir = createTempConfigDir([
        'logging.php' => [
            'default' => 'file',
            'min_level' => 'debug',
            'channels' => 'file',
            'drivers' => [
                'file' => [
                    'driver' => 'file',
                    'path' => sys_get_temp_dir() . '/test.log',
                ]
            ]
        ]
    ]);

    Config::load($tempDir, 'testing');

    Logger::init();
    Logger::init(); // Повторная инициализация

    $handlers = Logger::getHandlers();
    expect($handlers)->toHaveCount(1); // Не должно быть дубликатов

    deleteDir($tempDir);
});

test('Logger parses channels string', function () {
    $tempDir = createTempConfigDir([
        'logging.php' => [
            'default' => 'file',
            'min_level' => 'debug',
            'channels' => 'file, slack, telegram', // Со пробелами
            'drivers' => [
                'file' => [
                    'driver' => 'file',
                    'path' => sys_get_temp_dir() . '/test.log',
                ],
                'slack' => [
                    'driver' => 'slack',
                    'webhook_url' => 'https://hooks.slack.com/test',
                ],
                'telegram' => [
                    'driver' => 'telegram',
                    'bot_token' => 'test_token',
                    'chat_id' => '123456',
                ]
            ]
        ]
    ]);

    Config::load($tempDir, 'testing');

    Logger::init();

    $handlers = Logger::getHandlers();
    expect($handlers)->toHaveCount(3);

    deleteDir($tempDir);
});

test('Logger sets minimum level from config', function () {
    $tempDir = createTempConfigDir([
        'logging.php' => [
            'default' => 'file',
            'min_level' => 'warning',
            'channels' => 'file',
            'drivers' => [
                'file' => [
                    'driver' => 'file',
                    'path' => sys_get_temp_dir() . '/test.log',
                ]
            ]
        ]
    ]);

    Config::load($tempDir, 'testing');

    Logger::init();

    expect(Logger::getMinLevel())->toBe('warning');

    deleteDir($tempDir);
});
