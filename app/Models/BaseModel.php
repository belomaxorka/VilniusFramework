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
    protected array $hidden = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        $result = $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );

        return $result ? $this->hideFields($result) : null;
    }

    public function all(): array
    {
        $results = $this->db->select("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        return array_map([$this, 'hideFields'], $results);
    }

    public function where(string $column, $operator, $value): array
    {
        $results = $this->db->select(
            "SELECT * FROM {$this->table} WHERE {$column} {$operator} ? ORDER BY created_at DESC",
            [$value]
        );
        return array_map([$this, 'hideFields'], $results);
    }

    public function create(array $data): int
    {
        $data = $this->filterFillable($data);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $this->db->insert(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})",
            array_values($data)
        );

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): int
    {
        $data = $this->filterFillable($data);
        $data['updated_at'] = date('Y-m-d H:i:s');

        $setParts = [];
        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = ?";
        }
        $setClause = implode(', ', $setParts);

        return $this->db->update(
            "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?",
            [...array_values($data), $id]
        );
    }

    public function delete(int $id): int
    {
        return $this->db->delete(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    public function count(): int
    {
        $result = $this->db->selectOne("SELECT COUNT(*) as count FROM {$this->table}");
        return (int)$result['count'];
    }

    public function query(): QueryBuilder
    {
        return Database::table($this->table);
    }

    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function hideFields(array $data): array
    {
        if (empty($this->hidden)) {
            return $data;
        }

        return array_diff_key($data, array_flip($this->hidden));
    }
}
