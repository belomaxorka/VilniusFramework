<?php declare(strict_types=1);

/**
 * Web Routes
 *
 * Здесь регистрируются все веб-роуты вашего приложения.
 * Эти роуты загружаются RouterServiceProvider и все они
 * будут иметь middleware группу "web".
 */

// Home page
$router->get('', [\App\Controllers\HomeController::class, 'index'])->name('home');

// Vue example page
$router->get('/vue-example', function() {
    return \Core\Response::view('vue-example', [
        'title' => 'Vue 3 Example',
        'description' => 'Vue 3 Integration Example',
    ]);
})->name('vue.example');
