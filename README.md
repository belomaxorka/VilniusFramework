# Vilnius Framework

A modern, lightweight PHP framework built for speed and developer experience.

## Features

- ⚡ **Lightning Fast** - Optimized routing and minimal overhead
- 🎨 **Modern UI** - Built-in Tailwind CSS support
- 🛠️ **Developer Tools** - Comprehensive debug toolbar and profiling
- 🗄️ **Database Ready** - Powerful query builder with multiple driver support
- 🔄 **Database Migrations** - Version control for your database schema
- 💾 **Advanced Caching** - Multiple drivers (File, APCu, Redis, Memcached, Array)
- 📦 **Dependency Injection** - Clean, testable code with DI container
- ⚙️ **Console (CLI)** - Powerful command-line interface for common tasks
- 🔒 **Secure** - CSRF protection, validation, and security best practices
- 📚 **Well Documented** - Extensive documentation and examples

## Requirements

- PHP 8.1 or higher
- Composer
- Node.js & NPM (for frontend assets)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/belomaxorka/VilniusFramework.git
cd torrentpier
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install Node dependencies:
```bash
npm install
```

4. Create environment file:
```bash
cp .env.example .env
```

5. Configure your database in `.env`

6. Build frontend assets:
```bash
# Development
npm run dev

# Production
npm run build
```

## Quick Start

### Create Your First Route

Edit `routes/web.php`:
```php
$router->get('/hello', function() {
    return 'Hello, World!';
});
```

### Create a Controller

```php
<?php
namespace App\Controllers;

use Core\Response;

class WelcomeController extends Controller
{
    public function index(): Response
    {
        return $this->view('welcome', [
            'title' => 'Welcome!'
        ]);
    }
}
```

### Database Query

```php
use Core\Database;

$users = Database::table('users')
    ->where('active', true)
    ->orderBy('created_at', 'DESC')
    ->get();
```

### Caching

```php
use Core\Cache;

// Simple cache
Cache::set('key', 'value', 3600);
$value = Cache::get('key');

// Remember pattern
$users = Cache::remember('users', 3600, function () {
    return Database::table('users')->get();
});

// Using helpers
$value = cache('key', 'default');
cache_remember('key', 3600, fn() => 'value');
```

### Migrations & Console

```bash
# Create a migration
php vilnius make:migration create_users_table

# Run migrations
php vilnius migrate

# Create a controller
php vilnius make:controller UserController

# Create a model with migration
php vilnius make:model Post -m

# Routes & Cache
php vilnius route:list      # List all routes
php vilnius route:cache     # Cache routes (production)
php vilnius route:clear     # Clear route cache
php vilnius cache:clear     # Clear all cache

# Debug
php vilnius dump-server     # Start dump server

# View all commands
php vilnius list
```

Migration example:
```php
use Core\Database\Schema\Schema;

Schema::create('users', function ($table) {
    $table->id();
    $table->string('email')->unique();
    $table->string('password');
    $table->timestamps();
});
```

## Development

Start the development server:
```bash
php -S localhost:8000 -t public
```

Watch for asset changes:
```bash
npm run dev
```

Run tests:
```bash
./vendor/bin/pest
```

## Project Structure

```
├── app/                    # Application code
│   ├── Controllers/        # HTTP controllers
│   └── Models/            # Data models
├── config/                # Configuration files
├── core/                  # Framework core
├── public/                # Public web directory
├── resources/             # Views, CSS, JS
│   ├── css/
│   ├── js/
│   └── views/
├── routes/                # Route definitions
├── storage/               # Logs, cache
└── tests/                 # Test suite
```

## Configuration

### Database

Edit `config/database.php` or set in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=torrentpier
DB_USERNAME=root
DB_PASSWORD=
```

### Debug Mode

Enable debug toolbar and detailed errors:
```env
APP_ENV=development
APP_DEBUG=true
```

## Documentation

Full documentation is available in the `docs/` directory:

- [Router](docs/Router.md) - Routing and URL generation
- [Database](docs/Database.md) - Query builder and connections
- [Migrations & Console](docs/Console.md) - Database migrations and CLI commands
- [Cache](docs/Cache.md) - Caching system with multiple drivers
- [Templates](docs/TemplateEngine.md) - Template system
- [Debug](docs/README_DEBUG.md) - Debug toolbar and profiling
- [Request/Response](docs/RequestResponse.md) - HTTP handling

### Quick Start Guides

- [Cache Quick Start](docs/CacheQuickStart.md) - Get started with caching in 5 minutes
- [Migrations Quick Start](docs/MigrationsQuickStart.md) - Database migrations in 5 minutes

## Testing

Run the test suite:
```bash
# All tests
./vendor/bin/pest

# Specific test file
./vendor/bin/pest tests/Unit/Core/RouterTest.php

# With coverage
./vendor/bin/pest --coverage
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is open-sourced software licensed under the MIT license.

## Credits

Built with ❤️ using:
- PHP 8.1+
- Tailwind CSS
- Vite
- Pest Testing Framework

---

**Happy Coding!** 🚀

