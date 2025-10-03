<?php declare(strict_types=1);

namespace Core\Database\Schema;

/**
 * Database Column Definition
 * 
 * Определение колонки таблицы
 */
class Column
{
    private string $name;
    private string $type;
    private ?int $length = null;
    private ?int $precision = null;
    private ?int $scale = null;
    private bool $nullable = false;
    private mixed $default = null;
    private bool $unsigned = false;
    private bool $autoIncrement = false;
    private bool $primary = false;
    private bool $unique = false;
    private ?string $comment = null;
    private array $allowedValues = [];
    private ?string $after = null;
    private bool $first = false;

    public function __construct(string $name, string $type, ?int $length = null, ?int $scale = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->length = $length;
        $this->precision = $length;
        $this->scale = $scale;
    }

    /**
     * Сделать колонку nullable
     */
    public function nullable(bool $nullable = true): self
    {
        $this->nullable = $nullable;
        return $this;
    }

    /**
     * Установить значение по умолчанию
     */
    public function default(mixed $value): self
    {
        $this->default = $value;
        return $this;
    }

    /**
     * Сделать колонку unsigned
     */
    public function unsigned(bool $unsigned = true): self
    {
        $this->unsigned = $unsigned;
        return $this;
    }

    /**
     * Сделать колонку auto-increment
     */
    public function autoIncrement(bool $autoIncrement = true): self
    {
        $this->autoIncrement = $autoIncrement;
        return $this;
    }

    /**
     * Сделать колонку первичным ключом
     */
    public function primary(bool $primary = true): self
    {
        $this->primary = $primary;
        return $this;
    }

    /**
     * Сделать колонку уникальной
     */
    public function unique(bool $unique = true): self
    {
        $this->unique = $unique;
        return $this;
    }

    /**
     * Добавить комментарий
     */
    public function comment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Установить допустимые значения (для enum)
     */
    public function setAllowedValues(array $values): self
    {
        $this->allowedValues = $values;
        return $this;
    }

    /**
     * Добавить колонку после указанной
     */
    public function after(string $column): self
    {
        $this->after = $column;
        return $this;
    }

    /**
     * Добавить колонку первой
     */
    public function first(): self
    {
        $this->first = true;
        return $this;
    }

    /**
     * Получить имя колонки
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Получить тип колонки
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Получить длину колонки
     */
    public function getLength(): ?int
    {
        return $this->length;
    }

    /**
     * Является ли колонка nullable
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Получить значение по умолчанию
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * Является ли колонка unsigned
     */
    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    /**
     * Является ли колонка auto-increment
     */
    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    /**
     * Является ли колонка первичным ключом
     */
    public function isPrimary(): bool
    {
        return $this->primary;
    }

    /**
     * Является ли колонка уникальной
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * Получить комментарий
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Получить допустимые значения
     */
    public function getAllowedValues(): array
    {
        return $this->allowedValues;
    }

    /**
     * Получить precision
     */
    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    /**
     * Получить scale
     */
    public function getScale(): ?int
    {
        return $this->scale;
    }

    /**
     * Получить колонку, после которой нужно вставить
     */
    public function getAfter(): ?string
    {
        return $this->after;
    }

    /**
     * Нужно ли вставить колонку первой
     */
    public function isFirst(): bool
    {
        return $this->first;
    }

    /**
     * Есть ли значение по умолчанию
     */
    public function hasDefault(): bool
    {
        return $this->default !== null;
    }
}

