<?php declare(strict_types=1);

use Core\Cache\Drivers\FileDriver;

describe('FileDriver', function () {
    beforeEach(function () {
        $this->cachePath = CACHE_DIR . '/test_file_cache';
        $this->driver = new FileDriver([
            'path' => $this->cachePath,
            'prefix' => 'test_',
        ]);
    });

    afterEach(function () {
        // Clean up
        $this->driver->clear();
        if (is_dir($this->cachePath)) {
            rmdir($this->cachePath);
        }
    });

    test('set and get value', function () {
        expect($this->driver->set('key', 'value'))->toBeTrue();
        expect($this->driver->get('key'))->toBe('value');
    });

    test('get with default value', function () {
        expect($this->driver->get('nonexistent', 'default'))->toBe('default');
    });

    test('delete value', function () {
        $this->driver->set('key', 'value');
        expect($this->driver->delete('key'))->toBeTrue();
        expect($this->driver->get('key'))->toBeNull();
    });

    test('has checks existence', function () {
        $this->driver->set('key', 'value');
        expect($this->driver->has('key'))->toBeTrue();
        expect($this->driver->has('nonexistent'))->toBeFalse();
    });

    test('clear removes all', function () {
        $this->driver->set('key1', 'value1');
        $this->driver->set('key2', 'value2');
        expect($this->driver->clear())->toBeTrue();
        expect($this->driver->get('key1'))->toBeNull();
        expect($this->driver->get('key2'))->toBeNull();
    });

    test('increment value', function () {
        expect($this->driver->increment('counter', 5))->toBe(5);
        expect($this->driver->increment('counter', 3))->toBe(8);
        expect($this->driver->get('counter'))->toBe(8);
    });

    test('stores different types', function () {
        $this->driver->set('array', ['a', 'b']);
        $this->driver->set('object', (object)['key' => 'value']);

        expect($this->driver->get('array'))->toBe(['a', 'b']);
        expect($this->driver->get('object'))->toEqual((object)['key' => 'value']);
    });

    test('ttl expiration', function () {
        $this->driver->set('key', 'value', 1);
        expect($this->driver->get('key'))->toBe('value');
        
        sleep(2);
        expect($this->driver->get('key'))->toBeNull();
    });

    test('gc cleans expired files', function () {
        $this->driver->set('expired', 'value', 1);
        $this->driver->set('valid', 'value', 3600);
        
        sleep(2);
        
        $deleted = $this->driver->gc();
        expect($deleted)->toBeGreaterThanOrEqual(1);
        expect($this->driver->get('expired'))->toBeNull();
        expect($this->driver->get('valid'))->toBe('value');
    });
});

