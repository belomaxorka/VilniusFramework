<?php

/*
|--------------------------------------------------------------------------
| Load Helper Functions
|--------------------------------------------------------------------------
|
| Load all helper functions needed for testing
|
*/

// Load bootstrap file
require_once __DIR__ . '/../core/bootstrap.php';

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createTempConfigDir(array $files): string
{
    $base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
    $dir = $base . DIRECTORY_SEPARATOR . 'cfg_' . uniqid('', true);
    if (!mkdir($dir) && !is_dir($dir)) {
        throw new RuntimeException('Failed to create temp directory');
    }

    foreach ($files as $name => $data) {
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        $php = '<?php return ' . var_export($data, true) . ';';
        file_put_contents($path, $php);
    }

    return $dir;
}

function deleteDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = scandir($dir) ?: [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteDir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}
