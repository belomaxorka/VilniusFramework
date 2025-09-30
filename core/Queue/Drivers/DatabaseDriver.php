<?php declare(strict_types=1);

namespace Core\Queue\Drivers;

use Core\Queue\QueueInterface;
use Core\Queue\Job;
use Core\Database;

/**
 * Драйвер для хранения очереди в базе данных
 */
class DatabaseDriver implements QueueInterface
{
    protected string $table;

    public function __construct(string $table = 'jobs')
    {
        $this->table = $table;
        $this->createTableIfNotExists();
    }

    /**
     * Создает таблицу если не существует
     */
    protected function createTableIfNotExists(): void
    {
        $db = Database::connection();
        
        // Проверяем существование таблицы
        $exists = $db->query("SHOW TABLES LIKE '{$this->table}'")->fetch();
        
        if (!$exists) {
            $sql = "CREATE TABLE `{$this->table}` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `queue` VARCHAR(255) NOT NULL,
                `payload` TEXT NOT NULL,
                `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `reserved_at` INT UNSIGNED NULL,
                `available_at` INT UNSIGNED NOT NULL,
                `created_at` INT UNSIGNED NOT NULL,
                INDEX `queue_index` (`queue`, `reserved_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $db->exec($sql);
        }
    }

    public function push(Job $job, string $queue = 'default'): string
    {
        $db = Database::connection();
        $now = time();
        
        $payload = $job->serialize();
        
        $db->query(
            "INSERT INTO `{$this->table}` 
            (queue, payload, attempts, available_at, created_at) 
            VALUES (?, ?, ?, ?, ?)",
            [$queue, $payload, 0, $now, $now]
        );
        
        $id = (string)$db->lastInsertId();
        $job->setId($id);
        
        return $id;
    }

    public function pop(string $queue = 'default'): ?Job
    {
        $db = Database::connection();
        $now = time();
        
        // Начинаем транзакцию для атомарности
        $db->beginTransaction();
        
        try {
            // Находим доступную задачу и резервируем её
            $result = $db->query(
                "SELECT * FROM `{$this->table}` 
                WHERE queue = ? 
                AND reserved_at IS NULL 
                AND available_at <= ? 
                ORDER BY id ASC 
                LIMIT 1 
                FOR UPDATE",
                [$queue, $now]
            )->fetch();
            
            if (!$result) {
                $db->rollBack();
                return null;
            }
            
            // Резервируем задачу
            $db->query(
                "UPDATE `{$this->table}` 
                SET reserved_at = ?, attempts = attempts + 1 
                WHERE id = ?",
                [$now, $result['id']]
            );
            
            $db->commit();
            
            // Десериализуем задачу
            $job = Job::unserialize($result['payload']);
            $job->setId((string)$result['id'])
                ->setAttempts((int)$result['attempts'] + 1);
            
            return $job;
            
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function acknowledge(Job $job): void
    {
        $db = Database::connection();
        $db->query(
            "DELETE FROM `{$this->table}` WHERE id = ?",
            [$job->getId()]
        );
    }

    public function release(Job $job, int $delay = 0): void
    {
        $db = Database::connection();
        $availableAt = time() + $delay;
        
        $db->query(
            "UPDATE `{$this->table}` 
            SET reserved_at = NULL, available_at = ? 
            WHERE id = ?",
            [$availableAt, $job->getId()]
        );
    }

    public function delete(Job $job): void
    {
        $this->acknowledge($job);
    }

    public function size(string $queue = 'default'): int
    {
        $db = Database::connection();
        $result = $db->query(
            "SELECT COUNT(*) as count FROM `{$this->table}` 
            WHERE queue = ? AND reserved_at IS NULL",
            [$queue]
        )->fetch();
        
        return (int)($result['count'] ?? 0);
    }

    public function clear(string $queue = 'default'): void
    {
        $db = Database::connection();
        $db->query(
            "DELETE FROM `{$this->table}` WHERE queue = ?",
            [$queue]
        );
    }
}
