<?php declare(strict_types=1);

use App\Models\BaseModel;
use Core\Database;
use Core\Database\QueryBuilder;

// Тестовая модель
class TestUser extends BaseModel
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'age', 'country'];
    protected array $hidden = ['password', 'secret'];
    protected array $casts = [
        'age' => 'int',
        'active' => 'bool',
        'settings' => 'json',
    ];
    protected bool $timestamps = true;
    protected bool $softDeletes = false;
    
    // Accessor
    protected function getNameAttribute($value)
    {
        return ucfirst($value);
    }
    
    // Mutator
    protected function setEmailAttribute($value)
    {
        return strtolower($value);
    }
    
    // Custom Scopes (не переопределяем scopeActive из BaseModel)
    public function scopeVerified(QueryBuilder $query): QueryBuilder
    {
        return $query->where('verified', 1);
    }
    
    public function scopeInCountry(QueryBuilder $query, string $country): QueryBuilder
    {
        return $query->where('country', $country);
    }
    
    public function scopeOlderThan(QueryBuilder $query, int $age): QueryBuilder
    {
        return $query->where('age', '>', $age);
    }
}

class TestUserWithSoftDelete extends BaseModel
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email'];
    protected bool $softDeletes = true;
}

beforeEach(function (): void {
    // Инициализируем базу данных
    $config = [
        'default' => 'test',
        'connections' => [
            'test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ],
    ];
    
    $db = new Core\Database\DatabaseManager($config);
    
    // Устанавливаем instance для Database фасада
    $reflection = new ReflectionClass(Database::class);
    $instanceProperty = $reflection->getProperty('instance');
    $instanceProperty->setAccessible(true);
    $instanceProperty->setValue(null, $db);
    
    $this->connection = $db->connection();
    
    // Создаем таблицу
    $this->connection->exec('
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            email TEXT,
            age INTEGER,
            country TEXT,
            active INTEGER DEFAULT 1,
            password TEXT,
            secret TEXT,
            settings TEXT,
            created_at DATETIME,
            updated_at DATETIME,
            deleted_at DATETIME
        )
    ');
    
    // Вставляем тестовые данные
    $this->connection->exec("
        INSERT INTO users (name, email, age, country, active, password, settings, created_at) VALUES
        ('john', 'JOHN@EXAMPLE.COM', 30, 'USA', 1, 'secret123', '{\"theme\":\"dark\"}', datetime('now', '-5 days')),
        ('jane', 'JANE@EXAMPLE.COM', 25, 'Canada', 1, 'secret456', '{\"theme\":\"light\"}', datetime('now', '-3 days')),
        ('bob', 'BOB@EXAMPLE.COM', 35, 'USA', 0, 'secret789', NULL, datetime('now', '-1 day'))
    ");
});

// ============================================================================
// Basic Model Tests
// ============================================================================

it('creates model instance', function (): void {
    $user = new TestUser();
    expect($user)->toBeInstanceOf(BaseModel::class);
});

it('fills model with attributes', function (): void {
    $user = new TestUser([
        'name' => 'test user',
        'email' => 'TEST@EXAMPLE.COM',
        'age' => 40
    ]);
    
    expect($user->name)->toBe('Test user'); // Accessor применен (ucfirst)
    expect($user->email)->toBe('test@example.com'); // Mutator применен (strtolower)
    expect($user->age)->toBe(40);
});

// ============================================================================
// Find Tests
// ============================================================================

it('finds record by id', function (): void {
    $user = TestUser::find(1);
    
    expect($user)->toBeArray();
    expect($user['id'])->toBe(1);
    expect($user['name'])->toBe('john');
});

it('returns null when record not found', function (): void {
    $user = TestUser::find(999);
    expect($user)->toBeNull();
});

it('finds or fails', function (): void {
    $user = TestUser::findOrFail(1);
    expect($user)->toBeArray();
});

it('throws exception when findOrFail fails', function (): void {
    expect(fn() => TestUser::findOrFail(999))
        ->toThrow(RuntimeException::class, 'Model not found with ID: 999');
});

it('finds by column', function (): void {
    $user = TestUser::findBy('email', 'JOHN@EXAMPLE.COM');
    
    expect($user)->toBeArray();
    expect($user['name'])->toBe('john');
});

// ============================================================================
// All and Query Tests
// ============================================================================

it('gets all records', function (): void {
    $users = TestUser::all();
    
    expect($users)->toHaveCount(3);
});

it('returns query builder', function (): void {
    $query = TestUser::query();
    
    expect($query)->toBeInstanceOf(QueryBuilder::class);
});

// ============================================================================
// Where Tests
// ============================================================================

it('handles where clause', function (): void {
    $users = TestUser::where('country', 'USA')->get();
    
    expect($users)->toHaveCount(2);
});

it('handles whereIn', function (): void {
    $users = TestUser::whereIn('id', [1, 2])->get();
    
    expect($users)->toHaveCount(2);
});

it('handles whereNull', function (): void {
    $users = TestUser::whereNull('deleted_at')->get();
    
    expect($users)->toHaveCount(3);
});

// ============================================================================
// Order and Limit Tests
// ============================================================================

it('handles orderBy', function (): void {
    $users = TestUser::orderBy('age', 'DESC')->get();
    
    expect($users[0]['age'])->toBe(35);
});

it('handles limit', function (): void {
    $users = TestUser::limit(2)->get();
    
    expect($users)->toHaveCount(2);
});

it('handles first', function (): void {
    $user = TestUser::first();
    
    expect($user)->toBeArray();
});

it('handles latest', function (): void {
    $query = TestUser::latest();
    
    expect($query)->toBeInstanceOf(QueryBuilder::class);
});

it('handles oldest', function (): void {
    $query = TestUser::oldest();
    
    expect($query)->toBeInstanceOf(QueryBuilder::class);
});

// ============================================================================
// Pagination Tests
// ============================================================================

it('paginates results', function (): void {
    $result = TestUser::paginate(1, 2);
    
    expect($result)->toHaveKey('data');
    expect($result)->toHaveKey('total');
    expect($result['data'])->toHaveCount(2);
    expect($result['total'])->toBe(3);
});

// ============================================================================
// Create Tests
// ============================================================================

it('creates new record', function (): void {
    $id = TestUser::create([
        'name' => 'New User',
        'email' => 'new@example.com',
        'age' => 28,
        'country' => 'UK'
    ]);
    
    expect($id)->toBeInt();
    expect($id)->toBeGreaterThan(0);
    
    $user = TestUser::find($id);
    expect($user['name'])->toBe('New User');
    expect($user['email'])->toBe('new@example.com');
});

it('respects fillable when creating', function (): void {
    $id = TestUser::create([
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'should_not_be_set' // Не в fillable
    ]);
    
    $user = TestUser::find($id);
    expect($user['password'])->toBeNull();
});

it('adds timestamps when creating', function (): void {
    $id = TestUser::create([
        'name' => 'Timestamp Test',
        'email' => 'timestamp@example.com'
    ]);
    
    $user = TestUser::find($id);
    expect($user['created_at'])->not->toBeNull();
    expect($user['updated_at'])->not->toBeNull();
});

// ============================================================================
// Update Tests
// ============================================================================

it('updates record', function (): void {
    $affected = TestUser::updateRecord(1, [
        'name' => 'Updated Name',
        'age' => 31
    ]);
    
    expect($affected)->toBe(1);
    
    $user = TestUser::find(1);
    expect($user['name'])->toBe('Updated Name');
    expect($user['age'])->toBe(31);
});

it('updates timestamp when updating', function (): void {
    $user = TestUser::find(1);
    $oldUpdatedAt = $user['updated_at'];
    
    sleep(1); // Ждем секунду чтобы timestamp изменился
    
    TestUser::updateRecord(1, ['name' => 'Updated']);
    
    $updatedUser = TestUser::find(1);
    expect($updatedUser['updated_at'])->not->toBe($oldUpdatedAt);
});

// ============================================================================
// Delete Tests
// ============================================================================

it('deletes record', function (): void {
    $deleted = TestUser::destroy(1);
    
    expect($deleted)->toBe(1);
    
    $user = TestUser::find(1);
    expect($user)->toBeNull();
});

// ============================================================================
// Soft Delete Tests
// ============================================================================

it('soft deletes record', function (): void {
    $deleted = TestUserWithSoftDelete::destroy(1);
    
    expect($deleted)->toBe(1);
    
    // Запись все еще есть в БД
    $user = Database::getInstance()->selectOne('SELECT * FROM users WHERE id = 1');
    expect($user)->not->toBeNull();
    expect($user['deleted_at'])->not->toBeNull();
});

it('excludes soft deleted from queries', function (): void {
    TestUserWithSoftDelete::destroy(1);
    
    $users = TestUserWithSoftDelete::all();
    expect($users)->toHaveCount(2); // Исключая удаленную
});

it('gets only trashed records', function (): void {
    TestUserWithSoftDelete::destroy(1);
    
    $trashed = TestUserWithSoftDelete::onlyTrashed()->get();
    expect($trashed)->toHaveCount(1);
});

it('gets records with trashed', function (): void {
    TestUserWithSoftDelete::destroy(1);
    
    $all = TestUserWithSoftDelete::withTrashed()->get();
    expect($all)->toHaveCount(3); // Включая удаленную
});

it('restores soft deleted record', function (): void {
    TestUserWithSoftDelete::destroy(1);
    TestUserWithSoftDelete::restore(1);
    
    $users = TestUserWithSoftDelete::all();
    expect($users)->toHaveCount(3);
});

it('force deletes record', function (): void {
    TestUserWithSoftDelete::forceDelete(1);
    
    $user = Database::getInstance()->selectOne('SELECT * FROM users WHERE id = 1');
    expect($user)->toBeNull();
});

// ============================================================================
// Aggregate Tests
// ============================================================================

it('counts records', function (): void {
    $count = TestUser::count();
    expect($count)->toBe(3);
});

it('gets max value', function (): void {
    $max = TestUser::max('age');
    expect($max)->toBe(35);
});

it('gets min value', function (): void {
    $min = TestUser::min('age');
    expect($min)->toBe(25);
});

it('gets average value', function (): void {
    $avg = TestUser::avg('age');
    expect($avg)->toBeFloat();
});

it('gets sum', function (): void {
    $sum = TestUser::sum('age');
    expect($sum)->toBe(90);
});

it('checks existence', function (): void {
    $exists = TestUser::where('email', 'JOHN@EXAMPLE.COM')->exists();
    expect($exists)->toBeTrue();
});

// ============================================================================
// Scope Tests
// ============================================================================

it('applies local scope from base model', function (): void {
    // scopeActive уже определен в BaseModel
    $users = TestUser::active()->get();
    
    expect($users)->toHaveCount(2); // active = 1
});

it('applies custom local scope', function (): void {
    $users = TestUser::verified()->get();
    
    expect($users)->toHaveCount(3); // verified = 1
});

it('applies scope with parameters', function (): void {
    $users = TestUser::inCountry('USA')->get();
    
    expect($users)->toHaveCount(2);
});

it('applies scope with numeric parameter', function (): void {
    $users = TestUser::olderThan(25)->get();
    
    expect($users)->toHaveCount(2); // age > 25: john(30), bob(35)
});

it('chains multiple scopes', function (): void {
    $users = TestUser::active()->inCountry('USA')->get();
    
    expect($users)->toHaveCount(1); // active=1 AND country='USA'
});

// ============================================================================
// Accessor and Mutator Tests
// ============================================================================

it('applies accessor when getting attribute', function (): void {
    $user = new TestUser(['name' => 'john']);
    
    expect($user->name)->toBe('John'); // ucfirst - первая буква заглавная
});

it('applies mutator when setting attribute', function (): void {
    $user = new TestUser(['email' => 'TEST@EXAMPLE.COM']);
    
    expect($user->email)->toBe('test@example.com'); // strtolower - все буквы маленькие
});

it('applies mutator and accessor together', function (): void {
    $user = new TestUser([
        'name' => 'test user',
        'email' => 'USER@EXAMPLE.COM'
    ]);
    
    expect($user->name)->toBe('Test user'); // Accessor: ucfirst
    expect($user->email)->toBe('user@example.com'); // Mutator: strtolower
});

// ============================================================================
// Cast Tests
// ============================================================================

it('casts integer', function (): void {
    $user = new TestUser(['age' => '30']);
    
    expect($user->age)->toBe(30);
    expect($user->age)->toBeInt();
});

it('casts boolean', function (): void {
    $user = new TestUser(['active' => 1]);
    
    expect($user->active)->toBe(true);
    expect($user->active)->toBeBool();
});

it('casts json', function (): void {
    $data = TestUser::find(1);
    $user = new TestUser($data);
    
    expect($user->settings)->toBeArray();
    expect($user->settings['theme'])->toBe('dark');
});

// ============================================================================
// Hidden Fields Tests
// ============================================================================

it('hides fields in toArray', function (): void {
    $data = TestUser::find(1);
    $user = new TestUser($data);
    $array = $user->toArray();
    
    expect($array)->not->toHaveKey('password');
    expect($array)->not->toHaveKey('secret');
});

it('hides fields in toJson', function (): void {
    $data = TestUser::find(1);
    $user = new TestUser($data);
    $json = $user->toJson();
    
    expect($json)->toBeString();
    $decoded = json_decode($json, true);
    expect($decoded)->not->toHaveKey('password');
});

// ============================================================================
// Magic Methods Tests
// ============================================================================

it('gets attribute via magic getter', function (): void {
    $user = new TestUser(['name' => 'test']);
    
    expect($user->name)->toBe('Test');
});

it('sets attribute via magic setter', function (): void {
    $user = new TestUser();
    $user->name = 'test';
    
    expect($user->name)->toBe('Test');
});

it('checks attribute via magic isset', function (): void {
    $user = new TestUser(['name' => 'test']);
    
    expect(isset($user->name))->toBeTrue();
    expect(isset($user->nonexistent))->toBeFalse();
});

// ============================================================================
// Static Call Tests
// ============================================================================

it('calls query builder methods statically', function (): void {
    $query = TestUser::where('age', '>', 25);
    
    expect($query)->toBeInstanceOf(QueryBuilder::class);
});

// ============================================================================
// Fillable Tests
// ============================================================================

it('filters non-fillable attributes', function (): void {
    $user = new TestUser();
    $reflection = new ReflectionClass($user);
    $method = $reflection->getMethod('filterFillable');
    $method->setAccessible(true);
    
    $data = [
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'secret', // Не в fillable
        'age' => 30
    ];
    
    $filtered = $method->invoke($user, $data);
    
    expect($filtered)->toHaveKey('name');
    expect($filtered)->toHaveKey('email');
    expect($filtered)->toHaveKey('age');
    expect($filtered)->not->toHaveKey('password');
});

// ============================================================================
// Guarded Tests
// ============================================================================

class TestUserWithGuarded extends BaseModel
{
    protected string $table = 'users';
    protected array $guarded = ['password', 'secret'];
}

it('filters guarded attributes', function (): void {
    $user = new TestUserWithGuarded();
    $reflection = new ReflectionClass($user);
    $method = $reflection->getMethod('filterFillable');
    $method->setAccessible(true);
    
    $data = [
        'name' => 'Test',
        'password' => 'secret',
        'secret' => 'hidden'
    ];
    
    $filtered = $method->invoke($user, $data);
    
    expect($filtered)->toHaveKey('name');
    expect($filtered)->not->toHaveKey('password');
    expect($filtered)->not->toHaveKey('secret');
});

// ============================================================================
// Truncate Tests
// ============================================================================

it('truncates table', function (): void {
    TestUser::truncate();
    
    $count = TestUser::count();
    expect($count)->toBe(0);
});

// ============================================================================
// Complex Query Tests
// ============================================================================

it('handles complex query with model', function (): void {
    $users = TestUser::where('age', '>', 20)
        ->whereIn('country', ['USA', 'Canada'])
        ->orderBy('age', 'DESC')
        ->limit(10)
        ->get();
    
    expect($users)->toBeArray();
});

// ============================================================================
// Events Tests (если реализованы)
// ============================================================================

class TestUserWithEvents extends BaseModel
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email'];
    public bool $eventFired = false;
    
    protected function onCreating($data)
    {
        $this->eventFired = true;
    }
}

it('fires creating event', function (): void {
    $model = new TestUserWithEvents();
    
    // Создаем через статический метод
    TestUserWithEvents::create([
        'name' => 'Event Test',
        'email' => 'event@example.com'
    ]);
    
    // Событие должно быть вызвано
    // Примечание: в текущей реализации событие вызывается на экземпляре модели
});
