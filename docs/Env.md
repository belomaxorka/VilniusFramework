## Env (Environment Variables Helper)

The `Core\Env` class provides a small, dependency‑free helper to read, set, and cache environment variables in PHP. It supports parsing values into native PHP types (booleans, numbers, null, arrays/objects via JSON), reading from a `.env` file when explicitly requested, and synchronizing values with `$_ENV`, `$_SERVER`, and `putenv`.

### Key Features
- Automatic, fast in‑memory cache per request
- Optional `.env` file loading with quoted value handling
- Type parsing: booleans, numbers, null, JSON arrays/objects
- Safe default values and existence checks
- Synchronizes values across `$_ENV`, `$_SERVER`, and process environment

### Basic Usage

```php
use Core\Env;

// 1) If you need .env file loading, call it explicitly:
Env::load(__DIR__ . '/.env');

// 2) Read values with defaults, automatically parsed
$debug = Env::get('APP_DEBUG', false);      // true/false
$port  = Env::get('APP_PORT', 8080);        // int
$name  = Env::get('APP_NAME', 'MyApp');     // string

// 3) Set/update values at runtime
Env::set('APP_DEBUG', true);

// 4) Check existence (in $_ENV/$_SERVER)
if (Env::has('DATABASE_URL')) {
    // ...
}

// 5) Get merged view of environment
$all = Env::all(); // array_merge($_SERVER, $_ENV) — $_ENV wins on conflicts
```

### Installation and Bootstrapping
- The class does not auto‑discover `.env` files. To load a file, call `Env::load('/absolute/path/to/.env')` early in your bootstrap (e.g., front controller or framework boot file).
- When `Env::get`, `Env::has`, or `Env::all` are called for the first time, the class marks itself as "loaded". Without an explicit `load($path)`, no file is read; it will only use what is already present in `$_ENV`/`$_SERVER`/process environment.

### API Reference

#### Env::get(string $key, mixed $default = null): mixed
- Returns the parsed value for `$key` from the internal cache or from `$_ENV`/`$_SERVER`.
- If not found, returns `$default` and caches that default.

#### Env::set(string $key, mixed $value): void
- Sets the value across `$_ENV[$key]`, `$_SERVER[$key]`, and `putenv("$key=$stringValue")`.
- Caches the provided value (without additional parsing beyond `(string)` used for system storage).

#### Env::has(string $key): bool
- Returns `true` if `$key` exists in `$_ENV` or `$_SERVER`.

#### Env::all(): array
- Returns `array_merge($_SERVER, $_ENV)`. On duplicate keys, values from `$_ENV` overwrite `$_SERVER`.

#### Env::load(string $path = null, bool $required = false): bool
- Reads a `.env` file when a valid `$path` is provided.
- Returns `true` on successful parse; `false` if no file was loaded.
- If `$required` is `true` and the file is missing/invalid, throws `RuntimeException`.
- While parsing, lines beginning with `#` are ignored; empty lines are skipped.
- Key/value lines must be `KEY=VALUE`. Outer single or double quotes around VALUE are removed if present.
- Existing values already set in `$_ENV`/`$_SERVER` are not overwritten by `.env`.

#### Env::clearCache(): void
- Clears the internal per‑request cache so subsequent `get` calls re‑read from `$_ENV`/`$_SERVER`.

### Value Parsing Rules
Parsing occurs in `Env::get()` via `parseValue`:

- Booleans:
  - Truthy: `true`, `1`, `yes`, `on` (case‑insensitive) → `true`
  - Falsy: `false`, `0`, `no`, `off`, empty string `""` → `false`
- Null: `null`, `nil` (case‑insensitive) → `null`
- Numbers:
  - If numeric and contains a dot → `(float)`
  - If numeric and no dot → `(int)`
- JSON:
  - Strings that look like JSON objects `{...}` or arrays `[...]` are decoded with `json_decode(..., true)`
  - On success, returns decoded arrays; otherwise leaves as string
- Strings:
  - Any other value remains a string

Quoted values in `.env` (e.g., `KEY="value"` or `KEY='value'`) are unwrapped (outermost quotes only) before storage.

### Caching Behavior
- The first call to `Env::get($key)` caches the parsed value for that key. Subsequent calls are served from the in‑memory cache.
- `Env::set($key, $value)` updates the cache immediately.
- Use `Env::clearCache()` after external changes to `$_ENV`/`$_SERVER` if you need fresh reads within the same request.

### Precedence and Sources
- Reads consult the cache first, then `$_ENV[$key]`, then `$_SERVER[$key]`.
- `Env::all()` returns `array_merge($_SERVER, $_ENV)`, so for duplicate keys, `$_ENV` wins.
- `.env` loading only sets keys that are not already present in `$_ENV`/`$_SERVER`.

### Error Handling
- `Env::load($path, required: true)` throws `RuntimeException` when the file is missing or invalid at the provided `$path`.

### Examples

Load a `.env` and read typed values:
```php
use Core\Env;

Env::load(__DIR__ . '/.env', required: true);

$enabled = Env::get('FEATURE_FLAG', false); // "true" → bool(true)
$timeout = Env::get('HTTP_TIMEOUT', 5);     // "15" → int(15)
$ratio   = Env::get('SAMPLE_RATIO', 0.1);  // "0.25" → float(0.25)
$config  = Env::get('JSON_CONFIG', []);    // "{\"level\":\"info\"}" → ["level" => "info"]
```

Set and check values at runtime:
```php
Env::set('APP_ENV', 'production');
if (Env::has('APP_ENV')) {
    // do prod setup
}
```

Refresh after external mutation within same request:
```php
putenv('DYNAMIC_KEY=42');
Env::clearCache();
$value = Env::get('DYNAMIC_KEY'); // 42 → int(42)
```

### Notes and Best Practices
- Call `Env::load($path)` as early as possible if your app relies on a `.env` file.
- Avoid relying on empty strings for flags; empty string is parsed as `false`.
- For complex structured settings, prefer JSON values and let `parseValue` decode them.
- Do not store secrets in version control; keep your `.env` files out of VCS.

### Namespace and Location
- Class: `Core\Env`
- File: `core/Env.php`
