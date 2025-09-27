<?php declare(strict_types=1);

namespace Core\Database;

use PDO;

interface DatabaseDriverInterface
{
    public function connect(array $config): PDO;

    public function buildDsn(array $config): string;
}
