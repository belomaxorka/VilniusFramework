<?php

use Core\HelperLoader;

beforeEach(function () {
    // уникальный id для этого теста — поможет делать уникальные имена функций и папок
    $this->id = uniqid();

    // временная директория для helpers
    $this->helpersPath = sys_get_temp_dir() . '/helpers_' . $this->id . '/';
    mkdir($this->helpersPath, 0777, true);

    // уникальные имена функций, чтобы не было "Cannot redeclare ..."
    $this->fn1 = 'test1_' . $this->id;
    $this->fn2 = 'test2_' . $this->id;

    // создаём файлы-хелперы
    file_put_contents(
        $this->helpersPath . 'test1.php',
        "<?php\nfunction {$this->fn1}() { return 'ok1'; }\n"
    );
    file_put_contents(
        $this->helpersPath . 'test2.php',
        "<?php\nfunction {$this->fn2}() { return 'ok2'; }\n"
    );

    // Сброс статического singleton-а (безопасно, с fallback)
    $reflection = new ReflectionClass(HelperLoader::class);
    if (method_exists($reflection, 'setStaticPropertyValue')) {
        $reflection->setStaticPropertyValue('instance', null);
    } else {
        // fallback на старые версии PHP
        $instanceProp = $reflection->getProperty('instance');
        $instanceProp->setAccessible(true);
        // для статического свойства second arg = value
        $instanceProp->setValue(null, null);
    }

    // Создаём новый экземпляр и подменяем приватный helpersPath
    $loader = HelperLoader::getInstance();
    $pathProp = $reflection->getProperty('helpersPath');
    $pathProp->setAccessible(true);
    $pathProp->setValue($loader, $this->helpersPath);
});

afterEach(function () {
    foreach (glob($this->helpersPath . '*.php') as $f) {
        @unlink($f);
    }
    @rmdir($this->helpersPath);
});

test('getInstance returns singleton', function () {
    $a = HelperLoader::getInstance();
    $b = HelperLoader::getInstance();

    expect($a)->toBeInstanceOf(HelperLoader::class)
        ->and($a)->toBe($b);
});

test('load helper successfully', function () {
    $loader = HelperLoader::getInstance();

    $result = $loader->load('test1');

    expect($result)->toBeTrue()
        ->and(function_exists($this->fn1))->toBeTrue()
        ->and($loader->isLoaded('test1'))->toBeTrue();
});

test('loading same helper twice returns false', function () {
    $loader = HelperLoader::getInstance();

    $loader->load('test1');
    $result = $loader->load('test1');

    expect($result)->toBeFalse();
});

test('throws exception if helper not found', function () {
    $loader = HelperLoader::getInstance();
    $loader->load('does_not_exist');
})->throws(RuntimeException::class);

test('load multiple helpers', function () {
    $loader = HelperLoader::getInstance();

    $result = $loader->loadMultiple(['test1', 'test2']);

    expect($result)->toBeTrue()
        ->and($loader->isLoaded('test1'))->toBeTrue()
        ->and($loader->isLoaded('test2'))->toBeTrue()
        ->and(function_exists($this->fn1))->toBeTrue()
        ->and(function_exists($this->fn2))->toBeTrue();
});

test('get loaded helpers list', function () {
    $loader = HelperLoader::getInstance();

    $loader->load('test1');
    $loaded = $loader->getLoaded();

    expect($loaded)->toContain('test1')
        ->not->toContain('test2');
});

test('get available helpers', function () {
    $loader = HelperLoader::getInstance();

    $available = $loader->getAvailable();

    expect($available)->toContain('test1')
        ->toContain('test2');
});

test('reload helper', function () {
    $loader = HelperLoader::getInstance();

    $loader->load('test1');
    $reloaded = $loader->reload('test1');

    // note: reload использует require_once — файл не будет переинклудён повторно в том же процессе,
    // но метод должен вернуть true и отметить helper как загруженный
    expect($reloaded)->toBeTrue()
        ->and($loader->isLoaded('test1'))->toBeTrue();
});

test('reset clears loaded helpers', function () {
    $loader = HelperLoader::getInstance();

    $loader->load('test1');
    $loader->reset();

    expect($loader->getLoaded())->toBeEmpty();
});

test('static methods work as expected', function () {
    HelperLoader::loadHelper('test1');
    expect(HelperLoader::isHelperLoaded('test1'))->toBeTrue();

    HelperLoader::loadHelpers(['test2']);
    expect(HelperLoader::isHelperLoaded('test2'))->toBeTrue();
});

test('__wakeup throws exception on unserialize', function () {
    $loader = HelperLoader::getInstance();

    $serialized = serialize($loader);

    unserialize($serialized);
})->throws(Exception::class, 'Cannot unserialize singleton');
