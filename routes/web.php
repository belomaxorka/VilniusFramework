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

// 6. Опциональные параметры
//
// Простой опциональный параметр
// $router->get('blog/{category?}', [BlogController::class, 'index'])
//     ->defaults(['category' => 'all']);
//
// Несколько опциональных параметров (порядок важен!)
// $router->get('products/{category?}/{subcategory?}', [ProductController::class, 'index'])
//     ->defaults(['category' => 'all', 'subcategory' => null])
//     ->where([
//         'category' => ['type' => 'string', 'rules' => ['alpha']],
//         'subcategory' => ['type' => 'string', 'rules' => ['alphanumeric'], 'optional' => true]
//     ]);
//
// Опциональная страница для пагинации
// $router->get('posts/page/{page?}', [PostController::class, 'index'])
//     ->defaults(['page' => 1])
//     ->whereNumber('page');
//
// Опциональный формат вывода (json, xml, html)
// $router->get('api/users/{id:\d+}/{format?}', [ApiController::class, 'user'])
//     ->defaults(['format' => 'json'])
//     ->whereIn('format', ['json', 'xml', 'html']);
//
// Комплексный пример с опциональными параметрами
// $router->get('docs/{section?}/{page?}', [DocsController::class, 'show'])
//     ->name('docs')
//     ->defaults(['section' => 'introduction', 'page' => 'getting-started'])
//     ->where([
//         'section' => ['type' => 'string', 'rules' => ['alphanumeric', 'min:2'], 'optional' => true],
//         'page' => ['type' => 'string', 'rules' => ['alphanumeric', 'min:2'], 'optional' => true]
//     ]);

// 7. Ресурсные контроллеры (Resource Controllers)
//
// Полный ресурсный контроллер - создает 7 роутов для CRUD операций
// $router->resource('posts', \App\Controllers\PostController::class);
// Создаст роуты:
// GET    /posts           -> index   (posts.index)
// GET    /posts/create    -> create  (posts.create)
// POST   /posts           -> store   (posts.store)
// GET    /posts/{id}      -> show    (posts.show)
// GET    /posts/{id}/edit -> edit    (posts.edit)
// PUT    /posts/{id}      -> update  (posts.update)
// DELETE /posts/{id}      -> destroy (posts.destroy)

// Ресурс с ограничениями (only/except)
// $router->resource('comments', \App\Controllers\CommentController::class, [
//     'only' => ['index', 'show', 'store', 'destroy'] // Только эти действия
// ]);
//
// $router->resource('tags', \App\Controllers\TagController::class, [
//     'except' => ['create', 'edit'] // Все кроме этих
// ]);

// API ресурсный контроллер (без create и edit форм)
// $router->apiResource('api/users', \App\Controllers\Api\UserController::class);
// Создаст только: index, store, show, update, destroy

// Ресурс с middleware
// $router->resource('admin/articles', \App\Controllers\Admin\ArticleController::class, [
//     'middleware' => ['auth', 'admin']
// ]);

// Ресурс с кастомными именами
// $router->resource('photos', \App\Controllers\PhotoController::class, [
//     'names' => [
//         'index' => 'gallery.index',
//         'show' => 'gallery.photo'
//     ]
// ]);

// Ресурс с кастомным именем параметра
// $router->resource('users', \App\Controllers\UserController::class, [
//     'parameter' => 'user_id' // Вместо {id} будет {user_id}
// ]);

// Вложенные ресурсы
// $router->group(['prefix' => 'posts/{post_id:\d+}'], function($router) {
//     $router->resource('comments', \App\Controllers\CommentController::class, [
//         'only' => ['index', 'store', 'destroy']
//     ]);
// });
// Создаст: /posts/1/comments, /posts/1/comments/{id}, etc.

// 8. Subdomain Routing (Роуты для поддоменов)
//
// API поддомен
// $router->domain('api.example.com', function($router) {
//     $router->get('users', [\App\Controllers\Api\UserController::class, 'index']);
//     $router->get('posts', [\App\Controllers\Api\PostController::class, 'index']);
// });

// Админ панель на поддомене
// $router->domain('admin.example.com', function($router) {
//     $router->group(['middleware' => 'auth'], function($router) {
//         $router->get('dashboard', [\App\Controllers\Admin\DashboardController::class, 'index']);
//         $router->resource('users', \App\Controllers\Admin\UserController::class);
//     });
// });

// Динамический поддомен (например: john.example.com, alice.example.com)
// $router->domain('{account}.example.com', function($router) {
//     // Параметр {account} будет доступен в контроллере
//     $router->get('', [\App\Controllers\AccountController::class, 'dashboard']);
//     $router->get('settings', [\App\Controllers\AccountController::class, 'settings']);
// });
// Примечание: параметры из домена передаются в контроллер вместе с URI параметрами

// Комбинация домена с префиксом
// $router->domain('api.example.com', function($router) {
//     $router->group(['prefix' => 'v1'], function($router) {
//         $router->apiResource('users', \App\Controllers\Api\V1\UserController::class);
//         $router->apiResource('posts', \App\Controllers\Api\V1\PostController::class);
//     });
//
//     $router->group(['prefix' => 'v2'], function($router) {
//         $router->apiResource('users', \App\Controllers\Api\V2\UserController::class);
//     });
// });

// Мультитенантность (multi-tenant приложение)
// $router->domain('{tenant}.example.com', function($router) {
//     $router->get('', [\App\Controllers\TenantController::class, 'home']);
//     $router->get('products', [\App\Controllers\TenantController::class, 'products']);
//     $router->get('about', [\App\Controllers\TenantController::class, 'about']);
// });

// Wildcard поддомены с валидацией
// $router->domain('{subdomain:[a-z0-9-]+}.example.com', function($router) {
//     $router->get('', [\App\Controllers\SubdomainController::class, 'index']);
// });

// Группа с доменом через group()
// $router->group(['domain' => 'blog.example.com'], function($router) {
//     $router->get('', [\App\Controllers\BlogController::class, 'index']);
//     $router->get('post/{slug}', [\App\Controllers\BlogController::class, 'show']);
// });
