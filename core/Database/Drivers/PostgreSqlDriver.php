<?php declare(strict_types=1);

namespace Core\Database\Drivers;

use Core\Database\DatabaseDriverInterface;
use PDO;

class PostgreSqlDriver implements DatabaseDriverInterface
{
    public function connect(array $config): PDO
    {
        $dsn = $this->buildDsn($config);
        $options = $config['options'] ?? [];

        return new PDO($dsn, $config['username'], $config['password'], $options);
    }

    public function buildDsn(array $config): string
    {
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";

        if (isset($config['sslmode'])) {
            $dsn .= ";sslmode={$config['sslmode']}";
        }

        return $dsn;
    }
}
