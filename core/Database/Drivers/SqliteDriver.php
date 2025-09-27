<?php declare(strict_types=1);

namespace Core\Database\Drivers;

use Core\Database\DatabaseDriverInterface;
use PDO;

class SqliteDriver implements DatabaseDriverInterface
{
    public function connect(array $config): PDO
    {
        $dsn = $this->buildDsn($config);
        $options = $config['options'] ?? [];

        return new PDO($dsn, null, null, $options);
    }

    public function buildDsn(array $config): string
    {
        return "sqlite:{$config['database']}";
    }
}
