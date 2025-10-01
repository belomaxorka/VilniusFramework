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

// User profile
$router->get('user/{name:[a-zA-Z]+}', [\App\Controllers\HomeController::class, 'name'])->name('user.profile');

// Примеры использования различных возможностей роутера:

// 1. Роуты с middleware
// $router->post('/profile', [ProfileController::class, 'update'])
//     ->middleware(['auth', 'csrf'])
//     ->name('profile.update');

// 2. Группы роутов с префиксом
// $router->group(['prefix' => 'admin', 'middleware' => 'auth'], function($router) {
//     $router->get('dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
//     $router->get('users', [AdminController::class, 'users'])->name('admin.users');
//     $router->post('users', [AdminController::class, 'storeUser'])->name('admin.users.store');
// });

// 3. API роуты с группировкой
// $router->group(['prefix' => 'api/v1'], function($router) {
//     $router->get('posts', [ApiController::class, 'posts'])->name('api.posts');
//     $router->get('posts/{id:\d+}', [ApiController::class, 'post'])->name('api.post');
//     $router->post('posts', [ApiController::class, 'createPost'])->middleware('auth')->name('api.post.create');
// });

// 4. RESTful примеры
// $router->get('posts', [PostController::class, 'index'])->name('posts.index');
// $router->get('posts/{id:\d+}', [PostController::class, 'show'])->name('posts.show');
// $router->post('posts', [PostController::class, 'store'])->middleware('csrf')->name('posts.store');
// $router->put('posts/{id:\d+}', [PostController::class, 'update'])->middleware('csrf')->name('posts.update');
// $router->delete('posts/{id:\d+}', [PostController::class, 'destroy'])->middleware('csrf')->name('posts.destroy');

