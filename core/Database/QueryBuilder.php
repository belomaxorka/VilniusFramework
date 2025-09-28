<?php declare(strict_types=1);

namespace Core\Database;

class QueryBuilder
{
    protected DatabaseManager $db;
    protected string $table = '';
    protected array $selects = ['*'];
    protected array $wheres = [];
    protected array $joins = [];
    protected array $orders = [];
    protected array $groups = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $bindings = [];

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function select(array $columns = ['*']): self
    {
        $this->selects = $columns;
        return $this;
    }

    public function where(string $column, string $operator, $value): self
    {
        $this->wheres[] = ['type' => 'basic', 'column' => $column, 'operator' => $operator, 'value' => $value];
        $this->bindings[] = $value;
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = ['type' => 'inner', 'table' => $table, 'first' => $first, 'operator' => $operator, 'second' => $second];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orders[] = ['column' => $column, 'direction' => $direction];
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->toSql();
        return $this->db->select($sql, $this->bindings);
    }

    public function first(): ?array
    {
        return $this->limit(1)->get()[0] ?? null;
    }

    public function toSql(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->selects) . ' FROM ' . $this->table;

        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
            }
        }

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ';
            $conditions = [];
            foreach ($this->wheres as $where) {
                $conditions[] = "{$where['column']} {$where['operator']} ?";
            }
            $sql .= implode(' AND ', $conditions);
        }

        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ';
            $orderClauses = [];
            foreach ($this->orders as $order) {
                $orderClauses[] = "{$order['column']} {$order['direction']}";
            }
            $sql .= implode(', ', $orderClauses);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }
}
