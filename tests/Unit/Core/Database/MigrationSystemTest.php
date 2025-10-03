<?php declare(strict_types=1);

use Core\Database\Schema\Schema;
use Core\Database\Schema\Blueprint;
use Core\Database\Schema\Column;
use Core\Database\Migrations\MigrationRepository;
use Core\Database\Migrations\Migrator;
use Core\Database;

beforeEach(function () {
    // Используем SQLite в памяти для тестов
    config(['database.default' => 'sqlite']);
    config(['database.connections.sqlite.database' => ':memory:']);
    
    // Очищаем подключение
    Database::purge();
});

afterEach(function () {
    Database::purge();
});

describe('Schema Builder - Blueprint', function () {
    test('blueprint can add id column', function () {
        $blueprint = new Blueprint('users');
        $column = $blueprint->id();
        
        expect($column)->toBeInstanceOf(Column::class);
        expect($column->getName())->toBe('id');
        expect($column->getType())->toBe('INTEGER');
        expect($column->isPrimary())->toBeTrue();
        expect($column->isAutoIncrement())->toBeTrue();
    });
    
    test('blueprint can add string column', function () {
        $blueprint = new Blueprint('users');
        $column = $blueprint->string('name', 100);
        
        expect($column->getName())->toBe('name');
        expect($column->getType())->toBe('VARCHAR');
        expect($column->getLength())->toBe(100);
    });
    
    test('blueprint can add integer column', function () {
        $blueprint = new Blueprint('users');
        $column = $blueprint->integer('age');
        
        expect($column->getName())->toBe('age');
        expect($column->getType())->toBe('INTEGER');
    });
    
    test('blueprint can add text column', function () {
        $blueprint = new Blueprint('posts');
        $column = $blueprint->text('content');
        
        expect($column->getName())->toBe('content');
        expect($column->getType())->toBe('TEXT');
    });
    
    test('blueprint can add timestamps', function () {
        $blueprint = new Blueprint('users');
        $blueprint->timestamps();
        
        $columns = $blueprint->getColumns();
        $columnNames = array_map(fn($col) => $col->getName(), $columns);
        
        expect($columnNames)->toContain('created_at');
        expect($columnNames)->toContain('updated_at');
    });
    
    test('column can be nullable', function () {
        $blueprint = new Blueprint('users');
        $column = $blueprint->string('email')->nullable();
        
        expect($column->isNullable())->toBeTrue();
    });
    
    test('column can have default value', function () {
        $blueprint = new Blueprint('users');
        $column = $blueprint->boolean('active')->default(true);
        
        expect($column->getDefault())->toBeTrue();
    });
    
    test('column can be unique', function () {
        $blueprint = new Blueprint('users');
        $column = $blueprint->string('email')->unique();
        
        expect($column->isUnique())->toBeTrue();
    });
});

describe('Schema Builder - Foreign Keys', function () {
    test('blueprint can add foreign key', function () {
        $blueprint = new Blueprint('posts');
        $foreignKey = $blueprint->foreignId('user_id')->constrained();
        
        expect($foreignKey->getName())->toBe('user_id');
        expect($foreignKey->getType())->toBe('INTEGER');
    });
    
    test('foreign key can cascade on delete', function () {
        $blueprint = new Blueprint('posts');
        $blueprint->foreignId('user_id')->constrained()->cascadeOnDelete();
        
        $foreignKeys = $blueprint->getForeignKeys();
        
        expect($foreignKeys)->toHaveCount(1);
        expect($foreignKeys[0]->getOnDelete())->toBe('CASCADE');
    });
    
    test('foreign key can set null on delete', function () {
        $blueprint = new Blueprint('posts');
        $blueprint->foreignId('user_id')->constrained()->nullOnDelete();
        
        $foreignKeys = $blueprint->getForeignKeys();
        
        expect($foreignKeys[0]->getOnDelete())->toBe('SET NULL');
    });
});

describe('Schema Builder - Create Table', function () {
    test('can create simple table', function () {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });
        
        expect(Schema::hasTable('users'))->toBeTrue();
    });
    
    test('can check if table exists', function () {
        expect(Schema::hasTable('non_existent_table'))->toBeFalse();
        
        Schema::create('test_table', function (Blueprint $table) {
            $table->id();
        });
        
        expect(Schema::hasTable('test_table'))->toBeTrue();
    });
    
    test('can drop table', function () {
        Schema::create('temp_table', function (Blueprint $table) {
            $table->id();
        });
        
        expect(Schema::hasTable('temp_table'))->toBeTrue();
        
        Schema::drop('temp_table');
        
        expect(Schema::hasTable('temp_table'))->toBeFalse();
    });
    
    test('can drop table if exists', function () {
        // Не должно выбросить исключение если таблицы нет
        Schema::dropIfExists('non_existent_table');
        
        Schema::create('temp_table', function (Blueprint $table) {
            $table->id();
        });
        
        Schema::dropIfExists('temp_table');
        
        expect(Schema::hasTable('temp_table'))->toBeFalse();
    });
});

describe('Schema Builder - Column Types', function () {
    test('supports all common column types', function () {
        Schema::create('test_types', function (Blueprint $table) {
            $table->id();
            $table->string('varchar_col');
            $table->text('text_col');
            $table->integer('int_col');
            $table->bigInteger('bigint_col');
            $table->decimal('decimal_col', 10, 2);
            $table->float('float_col');
            $table->boolean('bool_col');
            $table->date('date_col');
            $table->dateTime('datetime_col');
            $table->timestamp('timestamp_col');
            $table->json('json_col');
        });
        
        expect(Schema::hasTable('test_types'))->toBeTrue();
    });
});

describe('Migration Repository', function () {
    test('creates migrations table if not exists', function () {
        $repository = new MigrationRepository();
        $repository->createRepository();
        
        expect(Schema::hasTable('migrations'))->toBeTrue();
    });
    
    test('can log migration', function () {
        $repository = new MigrationRepository();
        $repository->createRepository();
        
        $repository->log('2025_10_03_120000_create_users_table', 1);
        
        $ran = $repository->getRan();
        
        expect($ran)->toContain('2025_10_03_120000_create_users_table');
    });
    
    test('can get last batch number', function () {
        $repository = new MigrationRepository();
        $repository->createRepository();
        
        expect($repository->getNextBatchNumber())->toBe(1);
        
        $repository->log('migration_1', 1);
        
        expect($repository->getNextBatchNumber())->toBe(2);
    });
    
    test('can delete migration', function () {
        $repository = new MigrationRepository();
        $repository->createRepository();
        
        $repository->log('migration_1', 1);
        
        expect($repository->getRan())->toContain('migration_1');
        
        $repository->delete('migration_1');
        
        expect($repository->getRan())->not->toContain('migration_1');
    });
    
    test('can get migrations by batch', function () {
        $repository = new MigrationRepository();
        $repository->createRepository();
        
        $repository->log('migration_1', 1);
        $repository->log('migration_2', 1);
        $repository->log('migration_3', 2);
        
        $batch1 = $repository->getMigrations(1);
        
        expect($batch1)->toHaveCount(2);
        expect(array_column($batch1, 'migration'))->toContain('migration_1');
        expect(array_column($batch1, 'migration'))->toContain('migration_2');
    });
});

describe('Migrator', function () {
    test('finds pending migrations', function () {
        $tempDir = sys_get_temp_dir() . '/migrations_' . uniqid();
        mkdir($tempDir);
        
        // Создаем тестовые файлы миграций
        file_put_contents($tempDir . '/2025_01_01_000001_create_users.php', '<?php
            use Core\Database\Migrations\Migration;
            use Core\Database\Schema\Schema;
            
            return new class extends Migration {
                public function up(): void {
                    Schema::create("test_users", function($t) { $t->id(); });
                }
                public function down(): void {
                    Schema::drop("test_users");
                }
            };
        ');
        
        $migrator = new Migrator($tempDir);
        $pending = $migrator->getPendingMigrations();
        
        expect($pending)->toHaveCount(1);
        expect($pending[0])->toContain('create_users');
        
        // Cleanup
        unlink($tempDir . '/2025_01_01_000001_create_users.php');
        rmdir($tempDir);
    });
});

describe('Schema Builder - SQLite Specifics', function () {
    test('uses correct autoincrement syntax for sqlite', function () {
        Schema::create('test_autoincrement', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        
        expect(Schema::hasTable('test_autoincrement'))->toBeTrue();
        
        // Проверяем что таблица создалась без ошибок
        Database::table('test_autoincrement')->insert(['name' => 'Test']);
        $result = Database::table('test_autoincrement')->first();
        
        expect($result)->toHaveKey('id');
        expect($result['id'])->toBe(1);
    });
});

