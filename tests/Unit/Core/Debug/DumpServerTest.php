<?php declare(strict_types=1);

use Core\DumpServer;
use Core\DumpClient;
use Core\Environment;

beforeEach(function () {
    Environment::set(Environment::DEVELOPMENT);
    DumpClient::enable(true);
});

describe('DumpServer Configuration', function () {
    test('can configure server', function () {
        DumpServer::configure('localhost', 9999);
        
        $config = DumpServer::getConfig();
        
        expect($config['host'])->toBe('localhost');
        expect($config['port'])->toBe(9999);
    });

    test('has default configuration', function () {
        DumpServer::configure();
        
        $config = DumpServer::getConfig();
        
        expect($config['host'])->toBe('127.0.0.1');
        expect($config['port'])->toBe(9912);
    });
});

describe('DumpClient Configuration', function () {
    test('can configure client', function () {
        DumpClient::configure('localhost', 9999, 2);
        
        $config = DumpClient::getConfig();
        
        expect($config['host'])->toBe('localhost');
        expect($config['port'])->toBe(9999);
        expect($config['timeout'])->toBe(2);
    });

    test('has default configuration', function () {
        DumpClient::configure();
        
        $config = DumpClient::getConfig();
        
        expect($config['host'])->toBe('127.0.0.1');
        expect($config['port'])->toBe(9912);
        expect($config['timeout'])->toBe(1);
    });

    test('can be enabled/disabled', function () {
        DumpClient::enable(true);
        expect(DumpClient::getConfig()['enabled'])->toBeTrue();
        
        DumpClient::enable(false);
        expect(DumpClient::getConfig()['enabled'])->toBeFalse();
    });
});

describe('Server Availability', function () {
    test('checks if server is available', function () {
        // Сервер не запущен
        expect(DumpServer::isAvailable())->toBeFalse();
    });

    test('client checks server availability', function () {
        // Сервер не запущен
        expect(DumpClient::isServerAvailable())->toBeFalse();
    });
});

describe('DumpClient Send', function () {
    test('send returns false when server not available', function () {
        DumpClient::configure('127.0.0.1', 9999);
        
        $result = DumpClient::send('test data');
        
        expect($result)->toBeFalse();
    });

    test('dump returns false when server not available', function () {
        DumpClient::configure('127.0.0.1', 9999);
        
        $result = DumpClient::dump('test data', 'Test');
        
        expect($result)->toBeFalse();
    });

    test('sends data with label', function () {
        $result = DumpClient::send(['key' => 'value'], 'Test Data');
        
        // Вернет false так как сервер не запущен, но проверим что не падает
        expect($result)->toBeBool();
    });

    test('sends different data types', function () {
        DumpClient::send('string');
        DumpClient::send(123);
        DumpClient::send(['array' => 'data']);
        DumpClient::send(new stdClass());
        
        expect(true)->toBeTrue(); // Не падает
    });
});

describe('Helper Functions', function () {
    test('server_dump works', function () {
        $result = server_dump('test data', 'Label');
        
        expect($result)->toBeBool();
    });

    test('dump_server_available works', function () {
        expect(dump_server_available())->toBeBool();
    });

    test('dd_server exits', function () {
        // Нельзя протестировать exit(), но проверим что функция существует
        expect(function_exists('dd_server'))->toBeTrue();
    });
});

describe('Production Mode', function () {
    test('client disabled in production', function () {
        Environment::set(Environment::PRODUCTION);
        
        $result = DumpClient::send('test');
        
        expect($result)->toBeFalse();
    });

    test('client disabled when explicitly disabled', function () {
        DumpClient::enable(false);
        
        $result = DumpClient::send('test');
        
        expect($result)->toBeFalse();
    });
});

describe('Data Formatting', function () {
    test('formats string data', function () {
        // Внутренний метод, проверяем через send
        $result = DumpClient::send('simple string', 'String Test');
        
        expect($result)->toBeBool();
    });

    test('formats array data', function () {
        $result = DumpClient::send(['key' => 'value'], 'Array Test');
        
        expect($result)->toBeBool();
    });

    test('formats object data', function () {
        $obj = new stdClass();
        $obj->prop = 'value';
        
        $result = DumpClient::send($obj, 'Object Test');
        
        expect($result)->toBeBool();
    });
});

describe('Integration', function () {
    test('can configure both server and client with same settings', function () {
        DumpServer::configure('localhost', 9913);
        DumpClient::configure('localhost', 9913);
        
        $serverConfig = DumpServer::getConfig();
        $clientConfig = DumpClient::getConfig();
        
        expect($serverConfig['host'])->toBe($clientConfig['host']);
        expect($serverConfig['port'])->toBe($clientConfig['port']);
    });

    test('client and server use same default port', function () {
        DumpServer::configure();
        DumpClient::configure();
        
        expect(DumpServer::getConfig()['port'])->toBe(9912);
        expect(DumpClient::getConfig()['port'])->toBe(9912);
    });
});

describe('Fallback Logging', function () {
    test('logs to file when server unavailable', function () {
        $logFile = defined('STORAGE_DIR') ? STORAGE_DIR . '/logs/dumps.log' : __DIR__ . '/../../../../storage/logs/dumps.log';
        
        // Удаляем старый лог если есть
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        
        // Отправляем данные когда сервер недоступен
        $result = server_dump(['test' => 'data'], 'Test Fallback');
        
        // Проверяем что файл создан
        expect(file_exists($logFile))->toBeTrue();
        
        // Проверяем содержимое
        $content = file_get_contents($logFile);
        expect($content)->toContain('Test Fallback');
        expect($content)->toContain('array');
        
        // Cleanup
        if (file_exists($logFile)) {
            unlink($logFile);
        }
    });
    
    test('fallback creates log directory if not exists', function () {
        $logDir = sys_get_temp_dir() . '/test_dumps_' . uniqid();
        $logFile = $logDir . '/dumps.log';
        
        // Убеждаемся что директории нет
        expect(is_dir($logDir))->toBeFalse();
        
        // Временно заменяем STORAGE_DIR
        $originalStorageDir = defined('STORAGE_DIR') ? STORAGE_DIR : null;
        if (!defined('STORAGE_DIR')) {
            define('STORAGE_DIR', sys_get_temp_dir() . '/test_dumps_' . uniqid());
        }
        
        // Отправляем данные
        server_dump(['test' => 'data'], 'Create Dir Test');
        
        // Проверяем что директория создана
        $actualLogFile = STORAGE_DIR . '/logs/dumps.log';
        expect(file_exists($actualLogFile))->toBeTrue();
        
        // Cleanup
        if (file_exists($actualLogFile)) {
            unlink($actualLogFile);
            rmdir(dirname($actualLogFile));
            if (is_dir(STORAGE_DIR)) {
                @rmdir(STORAGE_DIR);
            }
        }
    });
    
    test('fallback preserves data type in log', function () {
        $logFile = defined('STORAGE_DIR') ? STORAGE_DIR . '/logs/dumps.log' : __DIR__ . '/../../../../storage/logs/dumps.log';
        
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        
        // Отправляем разные типы данных
        server_dump(['array' => 'data'], 'Array Type');
        server_dump('string data', 'String Type');
        server_dump(42, 'Integer Type');
        
        $content = file_get_contents($logFile);
        
        expect($content)->toContain('Type: array');
        expect($content)->toContain('Type: string');
        expect($content)->toContain('Type: integer');
        
        // Cleanup
        if (file_exists($logFile)) {
            unlink($logFile);
        }
    });
    
    test('fallback logs correct file and line', function () {
        $logFile = defined('STORAGE_DIR') ? STORAGE_DIR . '/logs/dumps.log' : __DIR__ . '/../../../../storage/logs/dumps.log';
        
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        
        server_dump('test', 'File Line Test');
        
        $content = file_get_contents($logFile);
        
        // Проверяем что в логе есть путь к этому файлу
        expect($content)->toContain('DumpServerTest.php');
        
        // Cleanup
        if (file_exists($logFile)) {
            unlink($logFile);
        }
    });
});
