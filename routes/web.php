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

// API Routes
$router->group(['prefix' => 'api'], function ($router) {
    // Users API
    $router->delete('users/{id}', [\App\Controllers\Api\UserController::class, 'delete'])->name('api.users.delete');
});
