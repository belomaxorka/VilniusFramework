<?php declare(strict_types=1);

namespace App\Controllers;

class HomeController
{
    public function index(): void
    {
        // Примеры использования debug функций
        dump(['test' => 'data', 'number' => 42], 'Test Dump');
        
        dump_pretty([
            'user' => ['name' => 'John', 'age' => 30],
            'settings' => ['theme' => 'dark', 'lang' => 'ru']
        ], 'Pretty Dump Example');

        $result = benchmark(function() {
            usleep(100); // Имитация работы
            return 'Benchmark result';
        }, 'Sleep Test');

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
            'message' => "Welcome, {$name}!",
            'users' => [] // Пустой массив пользователей
        ];

        display('welcome.tpl', $data);
    }
}
