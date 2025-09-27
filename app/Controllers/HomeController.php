<?php declare(strict_types=1);

namespace App\Controllers;

class HomeController
{
    public function index(): void
    {
        echo "Hello from HomeController!\n";
        echo __('hello', ['name' => 'John']);
    }
}

