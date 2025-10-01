<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Response;

class HomeController extends Controller
{
    public function index(): Response
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

        return $this->view('welcome.tpl', $data);
    }

    public function name(string $name): Response
    {
        $data = [
            'title' => 'Personal Greeting',
            'name' => $name,
            'greeting' => __('hello', ['name' => $name]),
            'message' => "Welcome, {$name}!",
            'users' => []
        ];

        return $this->view('welcome.tpl', $data);
    }
}
