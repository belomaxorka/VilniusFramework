<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Database\Exceptions\ConnectionException;

class HomeController
{
    public function index(): void
    {
        throw new ConnectionException('123');
        echo "Hello from HomeController!";
    }

    public function name(string $name): void
    {
        echo __('hello', ['name' => $name]);
    }
}
