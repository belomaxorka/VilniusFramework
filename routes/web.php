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

// User profile with validation
$router->get('user/{name:[a-zA-Z]+}', [\App\Controllers\HomeController::class, 'name'])
    ->name('user.profile')
    ->where([
        'name' => [
            'type' => 'string',
            'rules' => ['alpha', 'min:2', 'max:50']
        ]
    ]);

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

// 4. RESTful примеры с валидацией
// $router->get('posts', [PostController::class, 'index'])->name('posts.index');
// 
// $router->get('posts/{id:\d+}', [PostController::class, 'show'])
//     ->name('posts.show')
//     ->whereNumber('id'); // Быстрая валидация для числовых параметров
// 
// $router->post('posts', [PostController::class, 'store'])
//     ->middleware('csrf')
//     ->name('posts.store');
// 
// $router->put('posts/{id:\d+}', [PostController::class, 'update'])
//     ->middleware('csrf')
//     ->name('posts.update')
//     ->where(['id' => ['type' => 'int', 'rules' => ['min:1']]]);
// 
// $router->delete('posts/{id:\d+}', [PostController::class, 'destroy'])
//     ->middleware('csrf')
//     ->name('posts.destroy')
//     ->whereNumber('id');

// 5. Примеры различных валидаций
// UUID параметр
// $router->get('items/{uuid}', [ItemController::class, 'show'])
//     ->whereUuid('uuid');
//
// Slug с кастомной валидацией
// $router->get('blog/{slug}', [BlogController::class, 'show'])
//     ->where(['slug' => ['type' => 'string', 'rules' => ['alphanumeric', 'min:3', 'max:100']]]);
//
// Enum значения
// $router->get('filter/{type}', [FilterController::class, 'index'])
//     ->whereIn('type', ['active', 'archived', 'draft']);
//
// Сложная валидация с несколькими параметрами
// $router->get('search/{category}/{term}', [SearchController::class, 'search'])
//     ->where([
//         'category' => ['type' => 'string', 'rules' => ['alpha', 'min:2']],
//         'term' => ['type' => 'string', 'rules' => ['min:3', 'max:100']]
//     ]);

