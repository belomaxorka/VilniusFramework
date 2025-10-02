<?php declare(strict_types=1);

use Core\Cache\CacheManager;
use Core\Cache\Drivers\ArrayDriver;
use Core\Cache\Drivers\FileDriver;
use Core\Cache\Exceptions\CacheException;

describe('CacheManager', function () {
    beforeEach(function () {
        $this->config = [
            'default' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array',
                    'prefix' => 'test_',
                ],
                'file' => [
                    'driver' => 'file',
                    'path' => CACHE_DIR . '/test_manager',
                    'prefix' => 'test_',
                ],
            ],
        ];
        
        $this->manager = new CacheManager($this->config);
    });

    afterEach(function () {
        $this->manager->purge();
    });

    test('returns default driver', function () {
        $driver = $this->manager->driver();
        expect($driver)->toBeInstanceOf(ArrayDriver::class);
    });

    test('returns specific driver', function () {
        $driver = $this->manager->driver('file');
        expect($driver)->toBeInstanceOf(FileDriver::class);
    });

    test('throws exception for unknown driver', function () {
        expect(fn() => $this->manager->driver('unknown'))
            ->toThrow(CacheException::class);
    });

    test('proxies calls to default driver', function () {
        $this->manager->set('key', 'value');
        expect($this->manager->get('key'))->toBe('value');
    });

    test('purges all drivers', function () {
        $this->manager->driver('array')->set('key1', 'value1');
        $this->manager->driver('file')->set('key2', 'value2');
        
        $this->manager->purge();
        
        expect($this->manager->driver('array')->get('key1'))->toBeNull();
        expect($this->manager->driver('file')->get('key2'))->toBeNull();
    });

    test('purges specific driver', function () {
        $this->manager->driver('array')->set('key1', 'value1');
        $this->manager->driver('file')->set('key2', 'value2');
        
        $this->manager->purge('array');
        
        expect($this->manager->driver('array')->get('key1'))->toBeNull();
        expect($this->manager->driver('file')->get('key2'))->toBe('value2');
    });

    test('can extend with custom driver', function () {
        $this->manager->extend('custom', ArrayDriver::class);
        
        $this->config['stores']['custom'] = [
            'driver' => 'custom',
            'prefix' => 'custom_',
        ];
        
        $this->manager = new CacheManager($this->config);
        $driver = $this->manager->driver('custom');
        
        expect($driver)->toBeInstanceOf(ArrayDriver::class);
    });

    test('gets driver config', function () {
        $config = $this->manager->getDriverConfig('array');
        expect($config)->toHaveKey('driver');
        expect($config['driver'])->toBe('array');
    });

    test('gets and sets default driver', function () {
        expect($this->manager->getDefaultDriver())->toBe('array');
        
        $this->manager->setDefaultDriver('file');
        expect($this->manager->getDefaultDriver())->toBe('file');
    });
});

