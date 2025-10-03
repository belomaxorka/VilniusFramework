<?php declare(strict_types=1);

/**
 * Web Routes
 *
 * Здесь регистрируются все веб-роуты вашего приложения.
 * Эти роуты загружаются RouterServiceProvider и все они
 * будут иметь middleware группу "web".
 */

// Home page - Dashboard
$router->get('', [\App\Controllers\HomeController::class, 'index'])->name('home');

// API endpoints
$router->get('/api/users', [\App\Controllers\HomeController::class, 'getUsers'])->name('api.users.index');
$router->post('/api/users', [\App\Controllers\HomeController::class, 'createUser'])->name('api.users.create');
$router->put('/api/users/{id}', [\App\Controllers\HomeController::class, 'updateUser'])->name('api.users.update');
$router->delete('/api/users/{id}', [\App\Controllers\HomeController::class, 'deleteUser'])->name('api.users.delete');
