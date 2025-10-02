<?php declare(strict_types=1);

use Core\Cache\Drivers\ArrayDriver;

describe('ArrayDriver', function () {
    beforeEach(function () {
        $this->driver = new ArrayDriver(['prefix' => 'test_']);
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

    test('decrement value', function () {
        $this->driver->set('counter', 10);
        expect($this->driver->decrement('counter', 3))->toBe(7);
        expect($this->driver->get('counter'))->toBe(7);
    });

    test('pull gets and deletes', function () {
        $this->driver->set('key', 'value');
        expect($this->driver->pull('key'))->toBe('value');
        expect($this->driver->has('key'))->toBeFalse();
    });

    test('add only if not exists', function () {
        expect($this->driver->add('key', 'value1'))->toBeTrue();
        expect($this->driver->add('key', 'value2'))->toBeFalse();
        expect($this->driver->get('key'))->toBe('value1');
    });

    test('forever sets permanently', function () {
        expect($this->driver->forever('key', 'value'))->toBeTrue();
        expect($this->driver->get('key'))->toBe('value');
    });

    test('remember executes callback if not cached', function () {
        $called = false;
        $callback = function () use (&$called) {
            $called = true;
            return 'computed';
        };

        $result = $this->driver->remember('key', 3600, $callback);
        expect($result)->toBe('computed');
        expect($called)->toBeTrue();

        // Second call should use cache
        $called = false;
        $result = $this->driver->remember('key', 3600, $callback);
        expect($result)->toBe('computed');
        expect($called)->toBeFalse();
    });

    test('multiple operations', function () {
        $values = ['key1' => 'value1', 'key2' => 'value2'];
        
        expect($this->driver->setMultiple($values))->toBeTrue();
        
        $results = $this->driver->getMultiple(['key1', 'key2', 'key3'], 'default');
        expect($results)->toBe([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'default',
        ]);
        
        expect($this->driver->deleteMultiple(['key1', 'key2']))->toBeTrue();
        expect($this->driver->has('key1'))->toBeFalse();
    });

    test('ttl expiration', function () {
        $this->driver->set('key', 'value', 1);
        expect($this->driver->get('key'))->toBe('value');
        
        sleep(2);
        expect($this->driver->get('key'))->toBeNull();
    });

    test('stores different types', function () {
        $this->driver->set('string', 'value');
        $this->driver->set('int', 42);
        $this->driver->set('float', 3.14);
        $this->driver->set('bool', true);
        $this->driver->set('array', ['a', 'b']);
        $this->driver->set('object', (object)['key' => 'value']);

        expect($this->driver->get('string'))->toBe('value');
        expect($this->driver->get('int'))->toBe(42);
        expect($this->driver->get('float'))->toBe(3.14);
        expect($this->driver->get('bool'))->toBe(true);
        expect($this->driver->get('array'))->toBe(['a', 'b']);
        expect($this->driver->get('object'))->toEqual((object)['key' => 'value']);
    });
});

