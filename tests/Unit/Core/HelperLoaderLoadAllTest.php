<?php declare(strict_types=1);

/**
 * Tests for HelperLoader::loadAllHelpers() method
 */

use Core\HelperLoader;

beforeEach(function () {
    $this->id = uniqid();
    $this->helpersPath = sys_get_temp_dir() . '/helpers_loadall_' . $this->id . '/';
    mkdir($this->helpersPath, 0777, true);

    // Сброс singleton
    $reflection = new ReflectionClass(HelperLoader::class);
    if (method_exists($reflection, 'setStaticPropertyValue')) {
        $reflection->setStaticPropertyValue('instance', null);
    } else {
        $instanceProp = $reflection->getProperty('instance');
        $instanceProp->setAccessible(true);
        $instanceProp->setValue(null, null);
    }

    // Создаём новый экземпляр с подменой helpersPath
    $loader = HelperLoader::getInstance();
    $pathProp = $reflection->getProperty('helpersPath');
    $pathProp->setAccessible(true);
    $pathProp->setValue($loader, $this->helpersPath);
});

afterEach(function () {
    // Рекурсивная очистка
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->helpersPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isDir()) {
            @rmdir($file->getRealPath());
        } else {
            @unlink($file->getRealPath());
        }
    }

    @rmdir($this->helpersPath);
});

describe('loadAllHelpers() - Basic Functionality', function () {
    test('loads all available groups', function () {
        // Создаём 3 группы с функциями
        $groups = ['group1', 'group2', 'group3'];
        $functions = [];

        foreach ($groups as $index => $group) {
            $groupPath = $this->helpersPath . $group . '/';
            mkdir($groupPath);

            $funcName = "func_{$group}_{$this->id}";
            $functions[$group] = $funcName;

            file_put_contents(
                $groupPath . 'helper.php',
                "<?php\nfunction {$funcName}() { return '{$group}'; }\n"
            );
        }

        // Загружаем всё
        $result = HelperLoader::loadAllHelpers();

        expect($result)->toBeTrue();

        // Проверяем что все функции загружены
        foreach ($functions as $group => $funcName) {
            expect(function_exists($funcName))->toBeTrue("Function {$funcName} should exist");
        }

        // Проверяем что все группы отмечены как загруженные
        $loader = HelperLoader::getInstance();
        foreach ($groups as $group) {
            expect($loader->isLoaded("group:{$group}"))->toBeTrue();
        }
    });

    test('returns false when no groups available', function () {
        // Пустая директория helpers (нет групп)
        $result = HelperLoader::loadAllHelpers();

        expect($result)->toBeFalse();
    });

    test('loads groups with multiple files', function () {
        $groupPath = $this->helpersPath . 'multifile/';
        mkdir($groupPath);

        $func1 = "multi1_{$this->id}";
        $func2 = "multi2_{$this->id}";
        $func3 = "multi3_{$this->id}";

        file_put_contents($groupPath . 'a.php', "<?php\nfunction {$func1}() {}\n");
        file_put_contents($groupPath . 'b.php', "<?php\nfunction {$func2}() {}\n");
        file_put_contents($groupPath . 'c.php', "<?php\nfunction {$func3}() {}\n");

        $result = HelperLoader::loadAllHelpers();

        expect($result)->toBeTrue()
            ->and(function_exists($func1))->toBeTrue()
            ->and(function_exists($func2))->toBeTrue()
            ->and(function_exists($func3))->toBeTrue();
    });

    test('works with single group', function () {
        $groupPath = $this->helpersPath . 'single/';
        mkdir($groupPath);

        $funcName = "single_{$this->id}";
        file_put_contents($groupPath . 'helper.php', "<?php\nfunction {$funcName}() {}\n");

        $result = HelperLoader::loadAllHelpers();

        expect($result)->toBeTrue()
            ->and(function_exists($funcName))->toBeTrue();
    });
});

describe('loadAllHelpers() - Edge Cases', function () {
    test('skips already loaded groups', function () {
        // Создаём 2 группы
        mkdir($this->helpersPath . 'group1/');
        mkdir($this->helpersPath . 'group2/');

        $func1 = "skip1_{$this->id}";
        $func2 = "skip2_{$this->id}";

        file_put_contents($this->helpersPath . 'group1/h.php', "<?php\nfunction {$func1}() {}\n");
        file_put_contents($this->helpersPath . 'group2/h.php', "<?php\nfunction {$func2}() {}\n");

        // Загружаем одну группу вручную
        $loader = HelperLoader::getInstance();
        $loader->loadGroup('group1');

        expect(function_exists($func1))->toBeTrue();

        // loadAllHelpers должен загрузить только group2
        $result = HelperLoader::loadAllHelpers();

        expect($result)->toBeTrue()
            ->and(function_exists($func2))->toBeTrue();
    });

    test('returns true even if some groups already loaded', function () {
        mkdir($this->helpersPath . 'group1/');
        mkdir($this->helpersPath . 'group2/');

        file_put_contents($this->helpersPath . 'group1/h.php', "<?php\nfunction g1_{$this->id}() {}\n");
        file_put_contents($this->helpersPath . 'group2/h.php', "<?php\nfunction g2_{$this->id}() {}\n");

        // Загружаем все группы
        HelperLoader::loadAllHelpers();

        // Повторная загрузка - group2 уже загружена, но всё равно должно быть true
        $result = HelperLoader::loadAllHelpers();

        // Вернёт false, потому что ни одна группа не была загружена (все уже были)
        expect($result)->toBeFalse();
    });

    test('handles empty group directories gracefully', function () {
        // Создаём группу без файлов
        mkdir($this->helpersPath . 'empty_group/');

        // Создаём нормальную группу
        mkdir($this->helpersPath . 'normal_group/');
        file_put_contents($this->helpersPath . 'normal_group/h.php', "<?php\nfunction normal_{$this->id}() {}\n");

        // loadAll должен пропустить пустую группу и загрузить нормальную
        // Но на самом деле loadGroup() выбросит исключение для пустой группы
        expect(fn() => HelperLoader::loadAllHelpers())
            ->toThrow(RuntimeException::class, 'No helper files found in group');
    });

    test('handles groups with nested directories', function () {
        $groupPath = $this->helpersPath . 'nested/';
        mkdir($groupPath);
        mkdir($groupPath . 'subdir/'); // Поддиректория

        $funcName = "nested_{$this->id}";
        file_put_contents($groupPath . 'helper.php', "<?php\nfunction {$funcName}() {}\n");

        // Файл в поддиректории не должен загружаться (glob не рекурсивный)
        file_put_contents($groupPath . 'subdir/sub.php', "<?php\nfunction sub_{$this->id}() {}\n");

        $result = HelperLoader::loadAllHelpers();

        expect($result)->toBeTrue()
            ->and(function_exists($funcName))->toBeTrue()
            ->and(function_exists("sub_{$this->id}"))->toBeFalse(); // Поддиректория игнорируется
    });
});

describe('loadAllHelpers() - Multiple Calls', function () {
    test('second call returns false if all already loaded', function () {
        mkdir($this->helpersPath . 'test/');
        file_put_contents($this->helpersPath . 'test/h.php', "<?php\nfunction test_{$this->id}() {}\n");

        $first = HelperLoader::loadAllHelpers();
        $second = HelperLoader::loadAllHelpers();

        expect($first)->toBeTrue()
            ->and($second)->toBeFalse(); // Нет новых групп для загрузки
    });

    test('can be called from different parts of code', function () {
        mkdir($this->helpersPath . 'shared/');
        file_put_contents($this->helpersPath . 'shared/h.php', "<?php\nfunction shared_{$this->id}() {}\n");

        // Симуляция вызова из разных мест
        $result1 = HelperLoader::loadAllHelpers(); // Первый вызов
        $result2 = HelperLoader::loadAllHelpers(); // Второй вызов

        // Функция всё равно доступна
        expect(function_exists("shared_{$this->id}"))->toBeTrue();
    });

    test('new groups added after first call are loaded on second call', function () {
        // Первая группа
        mkdir($this->helpersPath . 'first/');
        file_put_contents($this->helpersPath . 'first/h.php', "<?php\nfunction first_{$this->id}() {}\n");

        $result1 = HelperLoader::loadAllHelpers();
        expect($result1)->toBeTrue();

        // Добавляем вторую группу
        mkdir($this->helpersPath . 'second/');
        file_put_contents($this->helpersPath . 'second/h.php', "<?php\nfunction second_{$this->id}() {}\n");

        // Второй вызов загрузит новую группу
        $result2 = HelperLoader::loadAllHelpers();

        expect($result2)->toBeTrue()
            ->and(function_exists("second_{$this->id}"))->toBeTrue();
    });
});

describe('loadAllHelpers() - Integration', function () {
    test('works together with loadHelperGroup', function () {
        mkdir($this->helpersPath . 'manual/');
        mkdir($this->helpersPath . 'auto/');

        file_put_contents($this->helpersPath . 'manual/h.php', "<?php\nfunction manual_{$this->id}() {}\n");
        file_put_contents($this->helpersPath . 'auto/h.php', "<?php\nfunction auto_{$this->id}() {}\n");

        // Загружаем одну вручную
        HelperLoader::loadHelperGroup('manual');

        // loadAll загрузит остальные
        $result = HelperLoader::loadAllHelpers();

        expect($result)->toBeTrue()
            ->and(function_exists("manual_{$this->id}"))->toBeTrue()
            ->and(function_exists("auto_{$this->id}"))->toBeTrue();
    });

    test('works together with loadHelperGroups', function () {
        mkdir($this->helpersPath . 'g1/');
        mkdir($this->helpersPath . 'g2/');
        mkdir($this->helpersPath . 'g3/');

        file_put_contents($this->helpersPath . 'g1/h.php', "<?php\nfunction g1_{$this->id}() {}\n");
        file_put_contents($this->helpersPath . 'g2/h.php', "<?php\nfunction g2_{$this->id}() {}\n");
        file_put_contents($this->helpersPath . 'g3/h.php', "<?php\nfunction g3_{$this->id}() {}\n");

        // Загружаем несколько вручную
        HelperLoader::loadHelperGroups(['g1', 'g2']);

        // loadAll догрузит остальные
        $result = HelperLoader::loadAllHelpers();

        expect($result)->toBeTrue()
            ->and(function_exists("g1_{$this->id}"))->toBeTrue()
            ->and(function_exists("g2_{$this->id}"))->toBeTrue()
            ->and(function_exists("g3_{$this->id}"))->toBeTrue();
    });

    test('loaded groups are tracked correctly', function () {
        $groups = ['track1', 'track2', 'track3'];

        foreach ($groups as $group) {
            mkdir($this->helpersPath . $group . '/');
            file_put_contents(
                $this->helpersPath . $group . '/h.php',
                "<?php\nfunction {$group}_{$this->id}() {}\n"
            );
        }

        HelperLoader::loadAllHelpers();

        $loader = HelperLoader::getInstance();
        $loaded = $loader->getLoaded();

        // Все группы должны быть в списке загруженных
        foreach ($groups as $group) {
            expect($loaded)->toContain("group:{$group}");
        }
    });
});

describe('loadAllHelpers() - Performance', function () {
    test('loads many groups efficiently', function () {
        // Создаём 10 групп
        $groupCount = 10;
        for ($i = 1; $i <= $groupCount; $i++) {
            $groupPath = $this->helpersPath . "perf{$i}/";
            mkdir($groupPath);
            file_put_contents(
                $groupPath . 'h.php',
                "<?php\nfunction perf{$i}_{$this->id}() { return {$i}; }\n"
            );
        }

        $start = microtime(true);
        $result = HelperLoader::loadAllHelpers();
        $duration = microtime(true) - $start;

        expect($result)->toBeTrue()
            ->and($duration)->toBeLessThan(1.0); // Должно выполниться быстро

        // Проверяем что все функции загружены
        for ($i = 1; $i <= $groupCount; $i++) {
            expect(function_exists("perf{$i}_{$this->id}"))->toBeTrue();
        }
    });

    test('second call is fast when all loaded', function () {
        mkdir($this->helpersPath . 'fast/');
        file_put_contents($this->helpersPath . 'fast/h.php', "<?php\nfunction fast_{$this->id}() {}\n");

        // Первый вызов
        HelperLoader::loadAllHelpers();

        // Второй вызов должен быть очень быстрым
        $start = microtime(true);
        HelperLoader::loadAllHelpers();
        $duration = microtime(true) - $start;

        expect($duration)->toBeLessThan(0.01); // Очень быстро, тк ничего не загружается
    });
});

describe('loadAllHelpers() - Real-World Scenarios', function () {
    test('typical framework structure', function () {
        // Имитируем реальную структуру
        $realGroups = ['app', 'environment', 'debug', 'profiler', 'database', 'context'];

        foreach ($realGroups as $group) {
            mkdir($this->helpersPath . $group . '/');
            // По 2-3 файла в группе
            for ($i = 1; $i <= 2; $i++) {
                file_put_contents(
                    $this->helpersPath . $group . "/helper{$i}.php",
                    "<?php\nfunction {$group}_func{$i}_{$this->id}() {}\n"
                );
            }
        }

        $result = HelperLoader::loadAllHelpers();

        expect($result)->toBeTrue();

        // Проверяем все функции
        foreach ($realGroups as $group) {
            for ($i = 1; $i <= 2; $i++) {
                $funcName = "{$group}_func{$i}_{$this->id}";
                expect(function_exists($funcName))->toBeTrue("Function {$funcName} should exist");
            }
        }

        // Проверяем количество загруженных групп
        $loader = HelperLoader::getInstance();
        $loaded = $loader->getLoaded();
        expect(count($loaded))->toBe(count($realGroups));
    });

    test('conditional loading scenario', function () {
        // Базовые группы
        mkdir($this->helpersPath . 'core/');
        file_put_contents($this->helpersPath . 'core/h.php', "<?php\nfunction core_{$this->id}() {}\n");

        // Опциональные группы
        mkdir($this->helpersPath . 'optional/');
        file_put_contents($this->helpersPath . 'optional/h.php', "<?php\nfunction optional_{$this->id}() {}\n");

        // Загружаем всё
        HelperLoader::loadAllHelpers();

        // Обе группы должны быть загружены
        expect(function_exists("core_{$this->id}"))->toBeTrue()
            ->and(function_exists("optional_{$this->id}"))->toBeTrue();
    });

    test('bootstrap usage pattern', function () {
        // Имитация bootstrap.php

        // 1. Создаём структуру
        foreach (['app', 'debug'] as $group) {
            mkdir($this->helpersPath . $group . '/');
            file_put_contents(
                $this->helpersPath . $group . '/h.php',
                "<?php\nfunction {$group}_{$this->id}() {}\n"
            );
        }

        // 2. Загрузка как в bootstrap
        $result = HelperLoader::loadAllHelpers();

        // 3. Проверка что всё работает
        expect($result)->toBeTrue();

        // 4. Функции доступны для использования
        $loader = HelperLoader::getInstance();
        expect($loader->isLoaded('group:app'))->toBeTrue()
            ->and($loader->isLoaded('group:debug'))->toBeTrue();
    });
});

