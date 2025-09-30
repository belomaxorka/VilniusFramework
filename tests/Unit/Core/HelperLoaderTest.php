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
    // Удаляем файлы в корне
    foreach (glob($this->helpersPath . '*.php') as $f) {
        @unlink($f);
    }
    
    // Удаляем файлы в подпапках (группах)
    foreach (glob($this->helpersPath . '*', GLOB_ONLYDIR) as $dir) {
        foreach (glob($dir . '/*.php') as $f) {
            @unlink($f);
        }
        @rmdir($dir);
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

// ============================================================================
// Group Loading Tests
// ============================================================================

describe('Group Loading', function () {
    test('load helper group successfully', function () {
        $groupName = 'testgroup_' . $this->id;
        $groupPath = $this->helpersPath . $groupName . '/';
        mkdir($groupPath);
        
        $fn1 = 'group_func1_' . $this->id;
        $fn2 = 'group_func2_' . $this->id;
        
        file_put_contents($groupPath . 'helper1.php', "<?php\nfunction {$fn1}() { return 'g1'; }\n");
        file_put_contents($groupPath . 'helper2.php', "<?php\nfunction {$fn2}() { return 'g2'; }\n");
        
        $loader = HelperLoader::getInstance();
        $result = $loader->loadGroup($groupName);
        
        expect($result)->toBeTrue()
            ->and(function_exists($fn1))->toBeTrue()
            ->and(function_exists($fn2))->toBeTrue()
            ->and($loader->isLoaded("group:{$groupName}"))->toBeTrue();
    });
    
    test('loading same group twice returns false', function () {
        $groupName = 'testgroup_' . $this->id;
        $groupPath = $this->helpersPath . $groupName . '/';
        mkdir($groupPath);
        
        file_put_contents($groupPath . 'helper.php', "<?php\nfunction test_func_" . $this->id . "() {}\n");
        
        $loader = HelperLoader::getInstance();
        $loader->loadGroup($groupName);
        $result = $loader->loadGroup($groupName);
        
        expect($result)->toBeFalse();
    });
    
    test('throws exception if group not found', function () {
        $loader = HelperLoader::getInstance();
        $loader->loadGroup('nonexistent_group');
    })->throws(RuntimeException::class, 'Helper group not found');
    
    test('throws exception if group is empty', function () {
        $groupName = 'emptygroup_' . $this->id;
        mkdir($this->helpersPath . $groupName);
        
        $loader = HelperLoader::getInstance();
        $loader->loadGroup($groupName);
    })->throws(RuntimeException::class, 'No helper files found in group');
    
    test('load multiple groups', function () {
        $group1 = 'group1_' . $this->id;
        $group2 = 'group2_' . $this->id;
        
        mkdir($this->helpersPath . $group1);
        mkdir($this->helpersPath . $group2);
        
        $fn1 = 'func1_' . $this->id;
        $fn2 = 'func2_' . $this->id;
        
        file_put_contents($this->helpersPath . $group1 . '/helper.php', "<?php\nfunction {$fn1}() {}\n");
        file_put_contents($this->helpersPath . $group2 . '/helper.php', "<?php\nfunction {$fn2}() {}\n");
        
        $loader = HelperLoader::getInstance();
        $result = $loader->loadGroups([$group1, $group2]);
        
        expect($result)->toBeTrue()
            ->and(function_exists($fn1))->toBeTrue()
            ->and(function_exists($fn2))->toBeTrue()
            ->and($loader->isLoaded("group:{$group1}"))->toBeTrue()
            ->and($loader->isLoaded("group:{$group2}"))->toBeTrue();
    });
    
    test('static group methods work', function () {
        $groupName = 'staticgroup_' . $this->id;
        $groupPath = $this->helpersPath . $groupName . '/';
        mkdir($groupPath);
        
        $fn = 'static_func_' . $this->id;
        file_put_contents($groupPath . 'helper.php', "<?php\nfunction {$fn}() {}\n");
        
        HelperLoader::loadHelperGroup($groupName);
        
        expect(function_exists($fn))->toBeTrue()
            ->and(HelperLoader::getInstance()->isLoaded("group:{$groupName}"))->toBeTrue();
    });
    
    test('loadHelperGroups static method works', function () {
        $group1 = 'static1_' . $this->id;
        $group2 = 'static2_' . $this->id;
        
        mkdir($this->helpersPath . $group1);
        mkdir($this->helpersPath . $group2);
        
        $fn1 = 'static_fn1_' . $this->id;
        $fn2 = 'static_fn2_' . $this->id;
        
        file_put_contents($this->helpersPath . $group1 . '/h.php', "<?php\nfunction {$fn1}() {}\n");
        file_put_contents($this->helpersPath . $group2 . '/h.php', "<?php\nfunction {$fn2}() {}\n");
        
        HelperLoader::loadHelperGroups([$group1, $group2]);
        
        expect(function_exists($fn1))->toBeTrue()
            ->and(function_exists($fn2))->toBeTrue();
    });
    
    test('group loads all files in directory', function () {
        $groupName = 'multifile_' . $this->id;
        $groupPath = $this->helpersPath . $groupName . '/';
        mkdir($groupPath);
        
        $fn1 = 'multi1_' . $this->id;
        $fn2 = 'multi2_' . $this->id;
        $fn3 = 'multi3_' . $this->id;
        
        file_put_contents($groupPath . 'a.php', "<?php\nfunction {$fn1}() {}\n");
        file_put_contents($groupPath . 'b.php', "<?php\nfunction {$fn2}() {}\n");
        file_put_contents($groupPath . 'c.php', "<?php\nfunction {$fn3}() {}\n");
        
        $loader = HelperLoader::getInstance();
        $loader->loadGroup($groupName);
        
        expect(function_exists($fn1))->toBeTrue()
            ->and(function_exists($fn2))->toBeTrue()
            ->and(function_exists($fn3))->toBeTrue();
    });
    
    test('getAvailableGroups returns list of group directories', function () {
        $group1 = 'available1_' . $this->id;
        $group2 = 'available2_' . $this->id;
        
        mkdir($this->helpersPath . $group1);
        mkdir($this->helpersPath . $group2);
        
        $loader = HelperLoader::getInstance();
        $available = $loader->getAvailableGroups();
        
        expect($available)->toContain($group1)
            ->and($available)->toContain($group2);
    });
    
    test('loadAll loads all available groups', function () {
        $group1 = 'loadall1_' . $this->id;
        $group2 = 'loadall2_' . $this->id;
        $group3 = 'loadall3_' . $this->id;
        
        mkdir($this->helpersPath . $group1);
        mkdir($this->helpersPath . $group2);
        mkdir($this->helpersPath . $group3);
        
        $fn1 = 'fn1_' . $this->id;
        $fn2 = 'fn2_' . $this->id;
        $fn3 = 'fn3_' . $this->id;
        
        file_put_contents($this->helpersPath . $group1 . '/h.php', "<?php\nfunction {$fn1}() {}\n");
        file_put_contents($this->helpersPath . $group2 . '/h.php', "<?php\nfunction {$fn2}() {}\n");
        file_put_contents($this->helpersPath . $group3 . '/h.php', "<?php\nfunction {$fn3}() {}\n");
        
        $loader = HelperLoader::getInstance();
        $result = $loader->loadAll();
        
        expect($result)->toBeTrue()
            ->and(function_exists($fn1))->toBeTrue()
            ->and(function_exists($fn2))->toBeTrue()
            ->and(function_exists($fn3))->toBeTrue()
            ->and($loader->isLoaded("group:{$group1}"))->toBeTrue()
            ->and($loader->isLoaded("group:{$group2}"))->toBeTrue()
            ->and($loader->isLoaded("group:{$group3}"))->toBeTrue();
    });
    
    test('loadAll returns false if no groups available', function () {
        // Пустая директория helpers
        $loader = HelperLoader::getInstance();
        $result = $loader->loadAll();
        
        expect($result)->toBeFalse();
    });
    
    test('loadAll skips already loaded groups', function () {
        $group1 = 'skip1_' . $this->id;
        $group2 = 'skip2_' . $this->id;
        
        mkdir($this->helpersPath . $group1);
        mkdir($this->helpersPath . $group2);
        
        file_put_contents($this->helpersPath . $group1 . '/h.php', "<?php\nfunction skip1_{$this->id}() {}\n");
        file_put_contents($this->helpersPath . $group2 . '/h.php', "<?php\nfunction skip2_{$this->id}() {}\n");
        
        $loader = HelperLoader::getInstance();
        
        // Загружаем одну группу вручную
        $loader->loadGroup($group1);
        
        // loadAll должен загрузить только оставшиеся
        $result = $loader->loadAll();
        
        expect($result)->toBeTrue()
            ->and($loader->isLoaded("group:{$group1}"))->toBeTrue()
            ->and($loader->isLoaded("group:{$group2}"))->toBeTrue();
    });
    
    test('static loadAllHelpers method works', function () {
        $group = 'static_all_' . $this->id;
        mkdir($this->helpersPath . $group);
        
        $fn = 'static_all_fn_' . $this->id;
        file_put_contents($this->helpersPath . $group . '/h.php', "<?php\nfunction {$fn}() {}\n");
        
        $result = HelperLoader::loadAllHelpers();
        
        expect($result)->toBeTrue()
            ->and(function_exists($fn))->toBeTrue();
    });
});
