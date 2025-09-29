<?php declare(strict_types=1);

namespace Core\Database;

use Closure;
use Core\Database\Exceptions\QueryException;

class QueryBuilder
{
    protected DatabaseManager $db;
    protected string $table = '';
    protected array $selects = ['*'];
    protected array $wheres = [];
    protected array $joins = [];
    protected array $orders = [];
    protected array $groups = [];
    protected array $havings = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $bindings = [
        'select' => [],
        'join' => [],
        'where' => [],
        'having' => [],
        'order' => [],
    ];
    protected bool $distinct = false;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Установить таблицу для запроса
     */
    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Указать колонки для выборки
     */
    public function select(...$columns): self
    {
        if (empty($columns)) {
            $columns = ['*'];
        }
        
        // Поддержка массива или отдельных аргументов
        if (count($columns) === 1 && is_array($columns[0])) {
            $columns = $columns[0];
        }
        
        $this->selects = $columns;
        return $this;
    }

    /**
     * Добавить DISTINCT к запросу
     */
    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Добавить WHERE условие
     */
    public function where($column, $operator = null, $value = null, string $boolean = 'AND'): self
    {
        // where(closure) - вложенные условия
        if ($column instanceof Closure) {
            return $this->whereNested($column, $boolean);
        }

        // where(['column' => 'value', ...]) - массив условий
        if (is_array($column)) {
            return $this->whereMultiple($column, $boolean);
        }

        // where('column', 'value') - оператор = по умолчанию
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];
        
        $this->bindings['where'][] = $value;
        
        return $this;
    }

    /**
     * Добавить OR WHERE условие
     */
    public function orWhere($column, $operator = null, $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * WHERE IN условие
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): self
    {
        $type = $not ? 'not_in' : 'in';
        
        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean
        ];
        
        foreach ($values as $value) {
            $this->bindings['where'][] = $value;
        }
        
        return $this;
    }

    /**
     * WHERE NOT IN условие
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * OR WHERE IN условие
     */
    public function orWhereIn(string $column, array $values): self
    {
        return $this->whereIn($column, $values, 'OR');
    }

    /**
     * OR WHERE NOT IN условие
     */
    public function orWhereNotIn(string $column, array $values): self
    {
        return $this->whereIn($column, $values, 'OR', true);
    }

    /**
     * WHERE NULL условие
     */
    public function whereNull(string $column, string $boolean = 'AND', bool $not = false): self
    {
        $type = $not ? 'not_null' : 'null';
        
        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'boolean' => $boolean
        ];
        
        return $this;
    }

    /**
     * WHERE NOT NULL условие
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): self
    {
        return $this->whereNull($column, $boolean, true);
    }

    /**
     * OR WHERE NULL условие
     */
    public function orWhereNull(string $column): self
    {
        return $this->whereNull($column, 'OR');
    }

    /**
     * OR WHERE NOT NULL условие
     */
    public function orWhereNotNull(string $column): self
    {
        return $this->whereNull($column, 'OR', true);
    }

    /**
     * WHERE BETWEEN условие
     */
    public function whereBetween(string $column, array $values, string $boolean = 'AND', bool $not = false): self
    {
        $type = $not ? 'not_between' : 'between';
        
        $this->wheres[] = [
            'type' => $type,
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean
        ];
        
        $this->bindings['where'][] = $values[0];
        $this->bindings['where'][] = $values[1];
        
        return $this;
    }

    /**
     * WHERE NOT BETWEEN условие
     */
    public function whereNotBetween(string $column, array $values, string $boolean = 'AND'): self
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    /**
     * WHERE LIKE условие
     */
    public function whereLike(string $column, string $value, string $boolean = 'AND'): self
    {
        return $this->where($column, 'LIKE', $value, $boolean);
    }

    /**
     * OR WHERE LIKE условие
     */
    public function orWhereLike(string $column, string $value): self
    {
        return $this->whereLike($column, $value, 'OR');
    }

    /**
     * Вложенные WHERE условия
     */
    protected function whereNested(Closure $callback, string $boolean = 'AND'): self
    {
        $query = $this->newQuery();
        $callback($query);
        
        if (!empty($query->wheres)) {
            $this->wheres[] = [
                'type' => 'nested',
                'query' => $query,
                'boolean' => $boolean
            ];
            
            // Копируем биндинги из вложенного запроса
            $this->bindings['where'] = array_merge(
                $this->bindings['where'],
                $query->getBindings()['where']
            );
        }
        
        return $this;
    }

    /**
     * Массив WHERE условий
     */
    protected function whereMultiple(array $columns, string $boolean = 'AND'): self
    {
        foreach ($columns as $column => $value) {
            $this->where($column, '=', $value, $boolean);
        }
        
        return $this;
    }

    /**
     * Добавить JOIN
     */
    public function join(string $table, $first, ?string $operator = null, ?string $second = null, string $type = 'INNER'): self
    {
        // join('table', closure)
        if ($first instanceof Closure) {
            return $this->joinNested($table, $first, $type);
        }

        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        
        return $this;
    }

    /**
     * LEFT JOIN
     */
    public function leftJoin(string $table, $first, ?string $operator = null, ?string $second = null): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * RIGHT JOIN
     */
    public function rightJoin(string $table, $first, ?string $operator = null, ?string $second = null): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * CROSS JOIN
     */
    public function crossJoin(string $table): self
    {
        $this->joins[] = [
            'type' => 'CROSS',
            'table' => $table
        ];
        
        return $this;
    }

    /**
     * Вложенный JOIN с несколькими условиями
     */
    protected function joinNested(string $table, Closure $callback, string $type): self
    {
        $joinBuilder = new JoinClause($table, $type);
        $callback($joinBuilder);
        
        $this->joins[] = $joinBuilder;
        
        return $this;
    }

    /**
     * GROUP BY
     */
    public function groupBy(...$columns): self
    {
        if (count($columns) === 1 && is_array($columns[0])) {
            $columns = $columns[0];
        }
        
        $this->groups = array_merge($this->groups, $columns);
        
        return $this;
    }

    /**
     * HAVING условие
     */
    public function having(string $column, string $operator, $value, string $boolean = 'AND'): self
    {
        $this->havings[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];
        
        $this->bindings['having'][] = $value;
        
        return $this;
    }

    /**
     * OR HAVING условие
     */
    public function orHaving(string $column, string $operator, $value): self
    {
        return $this->having($column, $operator, $value, 'OR');
    }

    /**
     * ORDER BY
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        
        if (!in_array($direction, ['ASC', 'DESC'])) {
            throw new QueryException("Order direction must be ASC or DESC");
        }
        
        $this->orders[] = ['column' => $column, 'direction' => $direction];
        
        return $this;
    }

    /**
     * ORDER BY DESC
     */
    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Сортировка по последним добавленным
     */
    public function latest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Сортировка по первым добавленным
     */
    public function oldest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'ASC');
    }

    /**
     * LIMIT
     */
    public function limit(int $limit): self
    {
        if ($limit > 0) {
            $this->limit = $limit;
        }
        
        return $this;
    }

    /**
     * Алиас для limit
     */
    public function take(int $limit): self
    {
        return $this->limit($limit);
    }

    /**
     * OFFSET
     */
    public function offset(int $offset): self
    {
        if ($offset >= 0) {
            $this->offset = $offset;
        }
        
        return $this;
    }

    /**
     * Алиас для offset
     */
    public function skip(int $offset): self
    {
        return $this->offset($offset);
    }

    /**
     * Пагинация
     */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        
        // Получаем общее количество записей
        $total = $this->count();
        
        // Получаем данные для текущей страницы
        $data = $this->offset($offset)->limit($perPage)->get();
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }

    /**
     * Выполнить SELECT запрос
     */
    public function get(): array
    {
        $sql = $this->toSql();
        return $this->db->select($sql, $this->getAllBindings());
    }

    /**
     * Получить первую запись
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    /**
     * Получить значение одной колонки
     */
    public function value(string $column)
    {
        $result = $this->select($column)->first();
        return $result[$column] ?? null;
    }

    /**
     * Получить массив значений одной колонки
     */
    public function pluck(string $column, ?string $key = null): array
    {
        $results = $this->get();
        
        if ($key === null) {
            return array_column($results, $column);
        }
        
        return array_column($results, $column, $key);
    }

    /**
     * Проверить существование записей
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Проверить отсутствие записей
     */
    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    /**
     * Получить количество записей
     */
    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('COUNT', $column);
    }

    /**
     * Получить максимальное значение
     */
    public function max(string $column)
    {
        return $this->aggregate('MAX', $column);
    }

    /**
     * Получить минимальное значение
     */
    public function min(string $column)
    {
        return $this->aggregate('MIN', $column);
    }

    /**
     * Получить среднее значение
     */
    public function avg(string $column)
    {
        return $this->aggregate('AVG', $column);
    }

    /**
     * Получить сумму значений
     */
    public function sum(string $column)
    {
        return $this->aggregate('SUM', $column);
    }

    /**
     * Выполнить агрегатную функцию
     */
    protected function aggregate(string $function, string $column)
    {
        $previousSelects = $this->selects;
        
        $this->selects = ["{$function}({$column}) as aggregate"];
        
        $result = $this->first();
        
        $this->selects = $previousSelects;
        
        return $result['aggregate'] ?? null;
    }

    /**
     * Вставить запись
     */
    public function insert(array $values): bool
    {
        // Если массив массивов - batch insert
        if (is_array(reset($values))) {
            return $this->insertMultiple($values);
        }

        $columns = array_keys($values);
        $placeholders = array_fill(0, count($values), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        return $this->db->insert($sql, array_values($values));
    }

    /**
     * Вставить запись и вернуть ID
     */
    public function insertGetId(array $values): int
    {
        $this->insert($values);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Вставить несколько записей
     */
    protected function insertMultiple(array $values): bool
    {
        if (empty($values)) {
            return true;
        }

        $columns = array_keys(reset($values));
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($values), $placeholders));
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES " . $allPlaceholders;
        
        $bindings = [];
        foreach ($values as $row) {
            $bindings = array_merge($bindings, array_values($row));
        }
        
        return $this->db->insert($sql, $bindings);
    }

    /**
     * Обновить записи
     */
    public function update(array $values): int
    {
        $setParts = [];
        $bindings = [];
        
        foreach ($values as $column => $value) {
            $setParts[] = "{$column} = ?";
            $bindings[] = $value;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts);
        
        // Добавляем WHERE условия
        if (!empty($this->wheres)) {
            $sql .= $this->compileWheres();
            $bindings = array_merge($bindings, $this->bindings['where']);
        }
        
        return $this->db->update($sql, $bindings);
    }

    /**
     * Увеличить значение колонки
     */
    public function increment(string $column, int $amount = 1, array $extra = []): int
    {
        $values = array_merge([$column => new Expression("{$column} + {$amount}")], $extra);
        return $this->update($values);
    }

    /**
     * Уменьшить значение колонки
     */
    public function decrement(string $column, int $amount = 1, array $extra = []): int
    {
        $values = array_merge([$column => new Expression("{$column} - {$amount}")], $extra);
        return $this->update($values);
    }

    /**
     * Удалить записи
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";
        
        // Добавляем WHERE условия
        if (!empty($this->wheres)) {
            $sql .= $this->compileWheres();
        }
        
        return $this->db->delete($sql, $this->bindings['where']);
    }

    /**
     * Очистить таблицу
     */
    public function truncate(): bool
    {
        $driver = $this->db->getDriverName();
        
        // SQLite не поддерживает TRUNCATE, используем DELETE
        if ($driver === 'sqlite') {
            $sql = "DELETE FROM {$this->table}";
        } else {
            $sql = "TRUNCATE TABLE {$this->table}";
        }
        
        return $this->db->statement($sql);
    }

    /**
     * Создать SQL запрос
     */
    public function toSql(): string
    {
        $sql = 'SELECT ';
        
        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }
        
        $sql .= implode(', ', $this->selects) . ' FROM ' . $this->table;

        // JOINs
        if (!empty($this->joins)) {
            $sql .= $this->compileJoins();
        }

        // WHERE
        if (!empty($this->wheres)) {
            $sql .= $this->compileWheres();
        }

        // GROUP BY
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        // HAVING
        if (!empty($this->havings)) {
            $sql .= $this->compileHavings();
        }

        // ORDER BY
        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ';
            $orderClauses = [];
            foreach ($this->orders as $order) {
                $orderClauses[] = "{$order['column']} {$order['direction']}";
            }
            $sql .= implode(', ', $orderClauses);
        }

        // LIMIT
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        // OFFSET
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Компилировать JOINs
     */
    protected function compileJoins(): string
    {
        $sql = '';
        
        foreach ($this->joins as $join) {
            if ($join instanceof JoinClause) {
                $sql .= ' ' . $join->toSql();
            } elseif ($join['type'] === 'CROSS') {
                $sql .= " CROSS JOIN {$join['table']}";
            } else {
                $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
            }
        }
        
        return $sql;
    }

    /**
     * Компилировать WHERE условия
     */
    protected function compileWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }
        
        $sql = ' WHERE ';
        $conditions = [];
        
        foreach ($this->wheres as $index => $where) {
            $boolean = $index === 0 ? '' : " {$where['boolean']} ";
            
            switch ($where['type']) {
                case 'basic':
                    $conditions[] = $boolean . "{$where['column']} {$where['operator']} ?";
                    break;
                    
                case 'in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $conditions[] = $boolean . "{$where['column']} IN ({$placeholders})";
                    break;
                    
                case 'not_in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $conditions[] = $boolean . "{$where['column']} NOT IN ({$placeholders})";
                    break;
                    
                case 'null':
                    $conditions[] = $boolean . "{$where['column']} IS NULL";
                    break;
                    
                case 'not_null':
                    $conditions[] = $boolean . "{$where['column']} IS NOT NULL";
                    break;
                    
                case 'between':
                    $conditions[] = $boolean . "{$where['column']} BETWEEN ? AND ?";
                    break;
                    
                case 'not_between':
                    $conditions[] = $boolean . "{$where['column']} NOT BETWEEN ? AND ?";
                    break;
                    
                case 'nested':
                    $nestedSql = $this->compileNestedWhere($where['query']);
                    $conditions[] = $boolean . "({$nestedSql})";
                    break;
            }
        }
        
        return $sql . implode('', $conditions);
    }

    /**
     * Компилировать вложенные WHERE
     */
    protected function compileNestedWhere(QueryBuilder $query): string
    {
        $sql = '';
        $conditions = [];
        
        foreach ($query->wheres as $index => $where) {
            $boolean = $index === 0 ? '' : " {$where['boolean']} ";
            
            switch ($where['type']) {
                case 'basic':
                    $conditions[] = $boolean . "{$where['column']} {$where['operator']} ?";
                    break;
                case 'in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $conditions[] = $boolean . "{$where['column']} IN ({$placeholders})";
                    break;
                case 'null':
                    $conditions[] = $boolean . "{$where['column']} IS NULL";
                    break;
            }
        }
        
        return implode('', $conditions);
    }

    /**
     * Компилировать HAVING условия
     */
    protected function compileHavings(): string
    {
        if (empty($this->havings)) {
            return '';
        }
        
        $sql = ' HAVING ';
        $conditions = [];
        
        foreach ($this->havings as $index => $having) {
            $boolean = $index === 0 ? '' : " {$having['boolean']} ";
            $conditions[] = $boolean . "{$having['column']} {$having['operator']} ?";
        }
        
        return $sql . implode('', $conditions);
    }

    /**
     * Получить все биндинги
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Получить все биндинги в правильном порядке
     */
    protected function getAllBindings(): array
    {
        return array_merge(
            $this->bindings['select'],
            $this->bindings['join'],
            $this->bindings['where'],
            $this->bindings['having'],
            $this->bindings['order']
        );
    }

    /**
     * Создать новый экземпляр QueryBuilder
     */
    protected function newQuery(): self
    {
        return new static($this->db);
    }

    /**
     * Клонировать билдер для повторного использования
     */
    public function clone(): self
    {
        return clone $this;
    }

    /**
     * Debug - вывести SQL и биндинги
     */
    public function dump(): self
    {
        dump([
            'sql' => $this->toSql(),
            'bindings' => $this->getAllBindings()
        ]);
        
        return $this;
    }

    /**
     * Debug - вывести SQL и биндинги и прекратить выполнение
     */
    public function dd(): void
    {
        dd([
            'sql' => $this->toSql(),
            'bindings' => $this->getAllBindings()
        ]);
    }
}

/**
 * Класс для построения JOIN условий
 */
class JoinClause
{
    protected string $table;
    protected string $type;
    protected array $conditions = [];

    public function __construct(string $table, string $type = 'INNER')
    {
        $this->table = $table;
        $this->type = $type;
    }

    public function on(string $first, string $operator, string $second, string $boolean = 'AND'): self
    {
        $this->conditions[] = [
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'boolean' => $boolean
        ];
        
        return $this;
    }

    public function orOn(string $first, string $operator, string $second): self
    {
        return $this->on($first, $operator, $second, 'OR');
    }

    public function toSql(): string
    {
        $sql = "{$this->type} JOIN {$this->table}";
        
        if (!empty($this->conditions)) {
            $sql .= ' ON ';
            $parts = [];
            
            foreach ($this->conditions as $index => $condition) {
                $boolean = $index === 0 ? '' : " {$condition['boolean']} ";
                $parts[] = $boolean . "{$condition['first']} {$condition['operator']} {$condition['second']}";
            }
            
            $sql .= implode('', $parts);
        }
        
        return $sql;
    }
}

/**
 * Класс для raw SQL выражений
 */
class Expression
{
    protected string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}