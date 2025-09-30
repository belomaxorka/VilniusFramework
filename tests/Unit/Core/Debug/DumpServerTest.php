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
