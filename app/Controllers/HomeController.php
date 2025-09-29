<?php declare(strict_types=1);

namespace App\Controllers;

class HomeController
{
    public function index(): void
    {
        $data = [
            'title' => 'Welcome to TorrentPier',
            'message' => 'Hello from HomeController!',
            'users' => [
                ['name' => 'John', 'email' => 'john@example.com'],
                ['name' => 'Jane', 'email' => 'jane@example.com'],
                ['name' => 'Bob', 'email' => 'bob@example.com']
            ]
        ];
        
        display('welcome.tpl', $data);
    }

    public function name(string $name): void
    {
        $data = [
            'title' => 'Personal Greeting',
            'name' => $name,
            'greeting' => __('hello', ['name' => $name]),
            'message' => "Welcome, {$name}!"
        ];
        
        display('welcome.tpl', $data);
    }
}
