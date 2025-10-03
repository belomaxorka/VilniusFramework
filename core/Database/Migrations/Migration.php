<?php declare(strict_types=1);

namespace Core\Database\Migrations;

/**
 * Base Migration Class
 * 
 * Базовый класс для всех миграций
 */
abstract class Migration
{
    /**
     * Выполнить миграцию
     */
    abstract public function up(): void;

    /**
     * Откатить миграцию
     */
    abstract public function down(): void;
}

