## Router (HTTP Routing)

The `Core\Router` class is a lightweight HTTP router that maps incoming requests to handlers. It supports GET and POST routes, URI parameters with optional regex constraints, and simple controller resolution by class name.

### Key Features
- Define routes for GET and POST methods
- URI parameters with optional regex: `{id:\\d+}`
- Named parameter extraction via PCRE named capture groups
- Controller/action arrays or plain callables as route handlers
- Automatic controller namespace fallback to `App\\Controllers`
- Clean 404 handling with basic message output

### Basic Usage

```php
use Core\Router;

$router = new Router();

// 1) Simple GET route with a closure
$router->get('/', function () {
    echo 'Hello, world!';
});

// 2) Route with a named parameter (string by default)
$router->get('/users/{username}', function (string $username) {
    echo "Profile: {$username}";
});

// 3) Route with a constrained parameter (digits only)
$router->get('/posts/{id:\\d+}', function (int $id) {
    echo "Post #{$id}";
});

// 4) Route to a controller action (array form)
$router->post('/login', ['AuthController', 'store']);

// 5) Dispatch using the current request
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
```

### Route Patterns and Parameters

- Define dynamic segments using `{name}`. By default, `{name}` matches `[^/]+` (no slashes).
- Add a regex constraint with `{name:regex}`. Example: `{id:\\d+}` for digits, `{slug:[a-z0-9-]+}` for slugs.
- Internally, patterns are converted to named capture groups: `(?P<name>regex)` and wrapped with start/end anchors.
- Extracted params are passed to your handler in the order they appear in the route.

Examples:

```php
$router->get('/files/{path:.+}', function (string $path) { /* ... */ });
$router->get('/tags/{slug:[a-z0-9-]+}', function (string $slug) { /* ... */ });
```

### Controllers and Actions

- For array actions `['ControllerClass', 'method']`:
  - If `ControllerClass` does not exist, `App\\Controllers\\ControllerClass` is attempted.
  - The router instantiates the controller with `new` and invokes the method, spreading route params.
- For callable actions, the callable is invoked directly with the params.

### URI Normalization

- The router trims leading/trailing slashes and strips an optional `index.php` prefix from the path.
- Query strings are ignored (`parse_url($uri, PHP_URL_PATH)`).

### 404 Handling

- If no route matches, the router sets `http_response_code(404)` and echoes `"404 Not Found: [<uri>]"`.

### API Reference

#### Router::get(string $uri, callable|array $action): void
- Registers a GET route.
- `$action` may be a callable or an array `[ControllerClass, method]`.

#### Router::post(string $uri, callable|array $action): void
- Registers a POST route.
- `$action` may be a callable or an array `[ControllerClass, method]`.

#### Router::dispatch(string $method, string $uri): void
- Normalizes the `$uri` and attempts to match against registered routes for `$method`.
- On match: extracts named parameters and calls the handler.
- On no match: returns a 404 response with a message.

#### (Protected) Router::addRoute(string $method, string $uri, callable|array $action): void
- Converts `{param}` and `{param:regex}` to a PCRE with named groups and stores the route.
- Stored route shape: `['pattern' => '#^...$#', 'action' => $action]`.

### Examples

Controller example (`App\\Controllers\\AuthController`):
```php
<?php
namespace App\Controllers;

class AuthController
{
    public function store(): void
    {
        echo 'Logged in';
    }
}
```

Registering and dispatching:
```php
use Core\Router;

$router = new Router();
$router->post('/login', ['AuthController', 'store']);

$router->dispatch('POST', '/login'); // outputs: Logged in
```

Parameterized route with controller:
```php
$router->get('/users/{id:\\d+}', ['UserController', 'show']);
// Will call App\\Controllers\\UserController->show($id)
```

### Best Practices
- Prefer explicit regex constraints for IDs and slugs to avoid ambiguous matches.
- Keep route URIs consistent (lowercase, hyphen‑separated) for readability.
- Extract common prefixes (e.g., `/api`) by convention in your route definitions.
- Validate and sanitize inside controllers/handlers; the router only matches patterns.

### Namespace and Location
- Class: `Core\\Router`
- File: `core/Router.php`
