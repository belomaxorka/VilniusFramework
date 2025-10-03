<?php declare(strict_types=1);

namespace Core\Database\Schema;

/**
 * Foreign Key Definition
 * 
 * Определение внешнего ключа
 */
class ForeignKey
{
    private array $columns;
    private ?string $referencedTable = null;
    private array $referencedColumns = [];
    private ?string $onDelete = null;
    private ?string $onUpdate = null;
    private ?string $name = null;

    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * Установить referenced table и columns
     */
    public function references(string|array $columns): self
    {
        $this->referencedColumns = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    /**
     * Установить referenced table
     */
    public function on(string $table): self
    {
        $this->referencedTable = $table;
        return $this;
    }

    /**
     * Установить действие при удалении
     */
    public function onDelete(string $action): self
    {
        $this->onDelete = strtoupper($action);
        return $this;
    }

    /**
     * Установить действие при обновлении
     */
    public function onUpdate(string $action): self
    {
        $this->onUpdate = strtoupper($action);
        return $this;
    }

    /**
     * Каскадное удаление
     */
    public function cascadeOnDelete(): self
    {
        return $this->onDelete('CASCADE');
    }

    /**
     * Каскадное обновление
     */
    public function cascadeOnUpdate(): self
    {
        return $this->onUpdate('CASCADE');
    }

    /**
     * Установить NULL при удалении
     */
    public function nullOnDelete(): self
    {
        return $this->onDelete('SET NULL');
    }

    /**
     * Ограничить удаление
     */
    public function restrictOnDelete(): self
    {
        return $this->onDelete('RESTRICT');
    }

    /**
     * Установить имя внешнего ключа
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Получить колонки
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Получить referenced table
     */
    public function getReferencedTable(): ?string
    {
        return $this->referencedTable;
    }

    /**
     * Получить referenced columns
     */
    public function getReferencedColumns(): array
    {
        return $this->referencedColumns;
    }

    /**
     * Получить действие при удалении
     */
    public function getOnDelete(): ?string
    {
        return $this->onDelete;
    }

    /**
     * Получить действие при обновлении
     */
    public function getOnUpdate(): ?string
    {
        return $this->onUpdate;
    }

    /**
     * Получить имя
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}

