<?php declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Database\DatabaseManager;
use Core\Database\QueryBuilder;

abstract class BaseModel
{
    protected DatabaseManager $db;
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $guarded = ['*'];
    protected array $hidden = [];
    protected array $casts = [];
    protected array $dates = ['created_at', 'updated_at', 'deleted_at'];

    // Timestamps
    protected bool $timestamps = true;
    protected string $createdAtColumn = 'created_at';
    protected string $updatedAtColumn = 'updated_at';

    // Soft Deletes
    protected bool $softDeletes = false;
    protected string $deletedAtColumn = 'deleted_at';

    // Атрибуты модели
    protected array $attributes = [];
    protected array $original = [];

    // Relations
    protected array $relations = [];

    // События
    protected static array $booted = [];
    protected static array $globalScopes = [];

    public function __construct(array $attributes = [])
    {
        $this->db = Database::getInstance();
        $this->bootIfNotBooted();
        $this->fill($attributes);
    }

    /**
     * Инициализировать модель один раз
     */
    protected function bootIfNotBooted(): void
    {
        $class = static::class;

        if (!isset(static::$booted[$class])) {
            static::$booted[$class] = true;
            $this->boot();
        }
    }

    /**
     * Метод для инициализации модели (можно переопределить)
     */
    protected function boot(): void
    {
        // Переопределяется в дочерних классах
    }

    /**
     * Заполнить модель атрибутами
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            // Пропускаем числовые ключи
            if (is_string($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Установить атрибут
     */
    public function setAttribute(string $key, $value): self
    {
        // Вызываем мутатор если есть
        $method = 'set' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';

        if (method_exists($this, $method)) {
            $value = $this->$method($value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Получить атрибут
     */
    public function getAttribute(string $key)
    {
        if (!array_key_exists($key, $this->attributes)) {
            return null;
        }

        $value = $this->attributes[$key];

        // Применяем cast
        if (isset($this->casts[$key])) {
            $value = $this->castAttribute($key, $value);
        }

        // Вызываем accessor если есть
        $method = 'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';

        if (method_exists($this, $method)) {
            return $this->$method($value);
        }

        return $value;
    }

    /**
     * Приведение типа атрибута
     */
    protected function castAttribute(string $key, $value)
    {
        $castType = $this->casts[$key];

        if ($value === null) {
            return null;
        }

        return match ($castType) {
            'int', 'integer' => (int)$value,
            'real', 'float', 'double' => (float)$value,
            'string' => (string)$value,
            'bool', 'boolean' => (bool)$value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : $value,
            'object' => is_string($value) ? json_decode($value) : $value,
            'date', 'datetime' => $value,
            default => $value
        };
    }

    /**
     * Магический метод для получения атрибутов
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Магический метод для установки атрибутов
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Магический метод для проверки существования атрибута
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Создать новый запрос для модели
     */
    public static function query(): QueryBuilder
    {
        return (new static)->newQuery();
    }

    /**
     * Создать новый query builder
     */
    public function newQuery(): QueryBuilder
    {
        $query = Database::table($this->table);

        // Применяем global scopes
        foreach (static::$globalScopes as $scope) {
            $scope($query);
        }

        // Применяем soft deletes если включены
        if ($this->softDeletes) {
            $query->whereNull($this->deletedAtColumn);
        }

        return $query;
    }

    /**
     * Найти модель по ID
     */
    public static function find($id): ?array
    {
        return static::query()
            ->where((new static)->primaryKey, '=', $id)
            ->first();
    }

    /**
     * Найти модель по ID или выбросить исключение
     */
    public static function findOrFail($id): array
    {
        $result = static::find($id);

        if ($result === null) {
            throw new \RuntimeException("Model not found with ID: {$id}");
        }

        return $result;
    }

    /**
     * Найти по атрибуту
     */
    public static function findBy(string $column, $value): ?array
    {
        return static::query()->where($column, '=', $value)->first();
    }

    /**
     * Получить все записи
     */
    public static function all(): array
    {
        return static::query()->get();
    }

    /**
     * Создать WHERE условие
     */
    public static function where($column, $operator = null, $value = null): QueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    /**
     * WHERE IN
     */
    public static function whereIn(string $column, array $values): QueryBuilder
    {
        return static::query()->whereIn($column, $values);
    }

    /**
     * WHERE NULL
     */
    public static function whereNull(string $column): QueryBuilder
    {
        return static::query()->whereNull($column);
    }

    /**
     * ORDER BY
     */
    public static function orderBy(string $column, string $direction = 'ASC'): QueryBuilder
    {
        return static::query()->orderBy($column, $direction);
    }

    /**
     * LIMIT
     */
    public static function limit(int $limit): QueryBuilder
    {
        return static::query()->limit($limit);
    }

    /**
     * Получить первую запись
     */
    public static function first(): ?array
    {
        return static::query()->first();
    }

    /**
     * Получить последние записи
     */
    public static function latest(string $column = 'created_at'): QueryBuilder
    {
        return static::query()->latest($column);
    }

    /**
     * Получить самые старые записи
     */
    public static function oldest(string $column = 'created_at'): QueryBuilder
    {
        return static::query()->oldest($column);
    }

    /**
     * Пагинация
     */
    public static function paginate(int $page = 1, int $perPage = 15): array
    {
        return static::query()->paginate($page, $perPage);
    }

    /**
     * Создать новую запись
     */
    public static function create(array $data): int
    {
        $model = new static;

        // Фильтруем fillable/guarded
        $data = $model->filterFillable($data);

        // Добавляем timestamps
        if ($model->timestamps) {
            $timestamp = date('Y-m-d H:i:s');
            $data[$model->createdAtColumn] = $timestamp;
            $data[$model->updatedAtColumn] = $timestamp;
        }

        // Событие creating
        $model->fireEvent('creating', $data);

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $model->db->insert(
            "INSERT INTO {$model->table} ({$columns}) VALUES ({$placeholders})",
            array_values($data)
        );

        $id = (int)$model->db->lastInsertId();

        // Событие created
        $model->fireEvent('created', $id);

        return $id;
    }

    /**
     * Обновить запись
     */
    public static function updateRecord(int $id, array $data): int
    {
        $model = new static;

        // Фильтруем fillable/guarded
        $data = $model->filterFillable($data);

        // Обновляем timestamp
        if ($model->timestamps) {
            $data[$model->updatedAtColumn] = date('Y-m-d H:i:s');
        }

        // Событие updating
        $model->fireEvent('updating', $data);

        $result = static::query()
            ->where($model->primaryKey, '=', $id)
            ->update($data);

        // Событие updated
        $model->fireEvent('updated', $id);

        return $result;
    }

    /**
     * Удалить запись
     */
    public static function destroy($id): int
    {
        $model = new static;

        // Событие deleting
        $model->fireEvent('deleting', $id);

        $result = 0;

        // Soft delete
        if ($model->softDeletes) {
            $result = static::query()
                ->where($model->primaryKey, '=', $id)
                ->update([$model->deletedAtColumn => date('Y-m-d H:i:s')]);
        } else {
            // Hard delete
            $result = static::query()
                ->where($model->primaryKey, '=', $id)
                ->delete();
        }

        // Событие deleted
        $model->fireEvent('deleted', $id);

        return $result;
    }

    /**
     * Permanently delete (для soft deletes)
     */
    public static function forceDelete($id): int
    {
        $model = new static;

        return $model->db->delete(
            "DELETE FROM {$model->table} WHERE {$model->primaryKey} = ?",
            [$id]
        );
    }

    /**
     * Восстановить soft deleted запись
     */
    public static function restore($id): int
    {
        $model = new static;

        if (!$model->softDeletes) {
            throw new \RuntimeException("Model does not use soft deletes");
        }

        return $model->db->update(
            "UPDATE {$model->table} SET {$model->deletedAtColumn} = NULL WHERE {$model->primaryKey} = ?",
            [$id]
        );
    }

    /**
     * Получить только удаленные записи
     */
    public static function onlyTrashed(): QueryBuilder
    {
        $model = new static;

        if (!$model->softDeletes) {
            throw new \RuntimeException("Model does not use soft deletes");
        }

        return Database::table($model->table)
            ->whereNotNull($model->deletedAtColumn);
    }

    /**
     * Получить записи включая удаленные
     */
    public static function withTrashed(): QueryBuilder
    {
        $model = new static;
        return Database::table($model->table);
    }

    /**
     * Получить количество записей
     */
    public static function count(): int
    {
        return static::query()->count();
    }

    /**
     * Получить максимальное значение
     */
    public static function max(string $column)
    {
        return static::query()->max($column);
    }

    /**
     * Получить минимальное значение
     */
    public static function min(string $column)
    {
        return static::query()->min($column);
    }

    /**
     * Получить среднее значение
     */
    public static function avg(string $column)
    {
        return static::query()->avg($column);
    }

    /**
     * Получить сумму
     */
    public static function sum(string $column)
    {
        return static::query()->sum($column);
    }

    /**
     * Проверить существование записи
     */
    public static function exists(): bool
    {
        return static::query()->exists();
    }

    /**
     * Очистить таблицу
     */
    public static function truncate(): bool
    {
        $model = new static;
        $driver = $model->db->getDriverName();

        // SQLite не поддерживает TRUNCATE, используем DELETE
        if ($driver === 'sqlite') {
            return $model->db->statement("DELETE FROM " . $model->table);
        }

        return $model->db->statement("TRUNCATE TABLE " . $model->table);
    }

    /**
     * Фильтровать fillable атрибуты
     */
    protected function filterFillable(array $data): array
    {
        // Если fillable не пустой, используем его
        if (!empty($this->fillable)) {
            return array_intersect_key($data, array_flip($this->fillable));
        }

        // Если guarded = ['*'], возвращаем пустой массив
        if ($this->guarded === ['*']) {
            return [];
        }

        // Если guarded содержит конкретные поля, исключаем их
        if (!empty($this->guarded)) {
            return array_diff_key($data, array_flip($this->guarded));
        }

        return $data;
    }

    /**
     * Скрыть поля
     */
    protected function hideFields(array $data): array
    {
        if (empty($this->hidden)) {
            return $data;
        }

        return array_diff_key($data, array_flip($this->hidden));
    }

    /**
     * Преобразовать модель в массив
     */
    public function toArray(): array
    {
        $data = $this->attributes;

        // Скрываем hidden поля
        $data = $this->hideFields($data);

        // Применяем casts
        foreach ($this->casts as $key => $type) {
            if (isset($data[$key])) {
                if (in_array($type, ['array', 'json']) && is_array($data[$key])) {
                    // Оставляем как есть для массивов
                    continue;
                }
                $data[$key] = $this->castAttribute($key, $data[$key]);
            }
        }

        return $data;
    }

    /**
     * Преобразовать модель в JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Добавить global scope
     */
    public static function addGlobalScope(callable $scope): void
    {
        static::$globalScopes[] = $scope;
    }

    /**
     * Создать local scope
     */
    public function scopeActive(QueryBuilder $query): QueryBuilder
    {
        return $query->where('active', '=', 1);
    }

    /**
     * Вызов scope методов
     */
    public static function __callStatic(string $method, array $parameters)
    {
        // Проверяем, есть ли scope метод
        $scopeMethod = 'scope' . ucfirst($method);

        if (method_exists(static::class, $scopeMethod)) {
            $model = new static;
            return $model->$scopeMethod(static::query(), ...$parameters);
        }

        // Если нет scope метода, пробуем вызвать метод query builder
        return static::query()->$method(...$parameters);
    }

    /**
     * Запустить событие
     */
    protected function fireEvent(string $event, $data = null): void
    {
        $method = 'on' . ucfirst($event);

        if (method_exists($this, $method)) {
            $this->$method($data);
        }
    }

    /**
     * Relationships
     */

    /**
     * Define a one-to-one relationship
     */
    protected function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): array
    {
        $foreignKey = $foreignKey ?? $this->table . '_id';
        $localKey = $localKey ?? $this->primaryKey;

        $relatedModel = new $related;

        return $relatedModel->db->select(
            "SELECT * FROM {$relatedModel->table} WHERE {$foreignKey} = ?",
            [$this->attributes[$localKey] ?? null]
        );
    }

    /**
     * Define a one-to-many relationship
     */
    protected function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): array
    {
        return $this->hasOne($related, $foreignKey, $localKey);
    }

    /**
     * Define an inverse one-to-one or many relationship
     */
    protected function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): ?array
    {
        $relatedModel = new $related;
        $foreignKey = $foreignKey ?? $relatedModel->table . '_id';
        $ownerKey = $ownerKey ?? $relatedModel->primaryKey;

        $result = $relatedModel->db->selectOne(
            "SELECT * FROM {$relatedModel->table} WHERE {$ownerKey} = ?",
            [$this->attributes[$foreignKey] ?? null]
        );

        return $result;
    }

    /**
     * Define a many-to-many relationship
     */
    protected function belongsToMany(
        string  $related,
        ?string $table = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null
    ): array
    {
        $relatedModel = new $related;

        $table = $table ?? $this->joiningTable($relatedModel);
        $foreignPivotKey = $foreignPivotKey ?? $this->table . '_id';
        $relatedPivotKey = $relatedPivotKey ?? $relatedModel->table . '_id';

        return $this->db->select(
            "SELECT {$relatedModel->table}.* FROM {$relatedModel->table}
             INNER JOIN {$table} ON {$relatedModel->table}.{$relatedModel->primaryKey} = {$table}.{$relatedPivotKey}
             WHERE {$table}.{$foreignPivotKey} = ?",
            [$this->attributes[$this->primaryKey] ?? null]
        );
    }

    /**
     * Получить имя промежуточной таблицы
     */
    protected function joiningTable($related): string
    {
        $segments = [
            $this->table,
            $related->table
        ];

        sort($segments);

        return strtolower(implode('_', $segments));
    }
}
