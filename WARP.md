# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

This is **belomaxorka/framework**, a lightweight PHP CMS Framework built from scratch with PHP 8.3+ requirements. It follows MVC architecture patterns with a custom routing system, ORM-like database abstraction, and comprehensive testing suite.

## Architecture

### Core Components

- **Core System** (`core/`): The framework's foundation
  - `Core.php`: Application bootstrapper that initializes environment, debug system, config, language, and database
  - `Router.php`: Custom routing system with parameter extraction (`{param:regex}` syntax)
  - `Database.php`: Database abstraction layer with Query Builder pattern
  - `Config.php`: Configuration management with dot notation support (`config.database.host`)
  - `LanguageManager.php` & `Lang.php`: Internationalization system
  - `ErrorHandler.php` & `Logger.php`: Error handling and logging infrastructure

- **Application Layer** (`app/`):
  - `Controllers/`: MVC controllers (example: `HomeController`)
  - `Models/`: Database models extending `BaseModel` with ORM-like functionality

- **Configuration** (`config/`): Environment-specific settings loaded automatically by `Core\Config`

- **Database Architecture**:
  - Multi-driver support (MySQL, PostgreSQL, SQLite) via `core/Database/Drivers/`
  - Query Builder pattern for fluent SQL construction
  - Transaction management with nested transaction support
  - Custom exception hierarchy for proper error handling

### Entry Point

The application boots through `public/index.php`:
1. Defines path constants (ROOT, CONFIG_DIR, LANG_DIR, etc.)
2. Loads Composer autoloader
3. Initializes Core system via `Core\Core::init()`
4. Sets up routing and dispatches requests

### Key Patterns

- **Dependency Injection**: Services are accessed via static facades (`Database::table()`, `Config::get()`)
- **Helper Functions**: Global helpers available (`config()`, `__()` for translations, `env()`)
- **PSR-4 Autoloading**: `App\` maps to `app/`, `Core\` maps to `core/`
- **Environment Configuration**: `.env` file support with `Env::get()` functionality

## Development Commands

### Dependencies & Setup
```bash
# Install dependencies
composer install

# Initialize project (copies .env.example to .env)
composer run init-project
```

### Testing
```bash
# Run all tests
composer test
# OR
./vendor/bin/pest

# Run with coverage
composer run test-coverage

# Run with HTML coverage report
composer run test-coverage-html

# Run specific test suite
./vendor/bin/pest tests/Unit/
./vendor/bin/pest tests/Feature/

# Run specific test file
./vendor/bin/pest tests/Unit/DatabaseManagerTest.php

# Run with verbose output
./vendor/bin/pest --verbose
```

### Database Testing
The framework includes comprehensive database tests covering:
- Unit tests for DatabaseManager, drivers, Query Builder, exceptions, transactions
- Integration tests with real database scenarios
- Uses in-memory SQLite for fast test execution
- Supports MySQL/PostgreSQL for driver-specific testing

### Development Server
Since this is a pure PHP framework, use PHP's built-in server:
```bash
# Start development server
php -S localhost:8000 -t public/

# With specific host/port
php -S 0.0.0.0:8080 -t public/
```

## Code Organization

### Adding New Routes
Routes are defined in `public/index.php`:
```php
$router->get('path/{param:regex}', [ControllerClass::class, 'method']);
$router->post('api/users', [UserController::class, 'store']);
```

### Creating Controllers
Controllers go in `app/Controllers/` and should follow the pattern:
```php
<?php declare(strict_types=1);
namespace App\Controllers;

class ExampleController
{
    public function index(): void
    {
        // Controller logic
    }
}
```

### Database Models
Extend `BaseModel` for ORM-like functionality:
```php
<?php declare(strict_types=1);
namespace App\Models;

class User extends BaseModel
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email'];
    protected array $hidden = ['password'];
}
```

### Configuration Files
Add new config files in `config/` directory. They're automatically loaded and accessible via:
```php
Config::get('filename.key');
// or using helper
config('database.host');
```

### Database Queries
Use either the Database facade or Query Builder:
```php
// Direct queries
Database::select('SELECT * FROM users WHERE id = ?', [$id]);

// Query Builder
Database::table('users')->where('active', '=', 1)->get();

// Through models
$user = new User();
$users = $user->where('status', '=', 'active');
```

## Key Files for Understanding

- `core/Core.php`: Application initialization sequence
- `core/Router.php`: Routing mechanism and parameter extraction
- `core/Database.php`: Database facade and connection management
- `app/Models/BaseModel.php`: ORM-like functionality for models
- `tests/README.md`: Comprehensive testing documentation and examples
- `composer.json`: Dependencies, autoloading, and available scripts

## Testing Philosophy

The framework emphasizes comprehensive testing with:
- Unit tests for all core components
- Integration tests for database operations
- Real-world scenarios (money transfers, complex queries)
- Error condition testing
- Performance testing with large datasets

Tests are well-documented in `tests/README.md` with specific examples for each component.