<?php declare(strict_types=1);

namespace App\Controllers;

use Core\TemplateEngine;
use Core\Database;

class HomeController
{
    /**
     * Dependency Injection через конструктор
     * 
     * Container автоматически внедрит эти зависимости
     */
    public function __construct(
        protected TemplateEngine $view,
        protected ?Database $db = null
    ) {
    }

    public function index(): void
    {
        // Пример использования Debug системы
        context_run('page_load', function() {
            timer_start('total');
            memory_start();

            // Подготовка данных
            context_run('data_preparation', function() {
                timer_start('prepare_data');

                $data = [
                    'title' => 'Welcome to TorrentPier',
                    'message' => 'Hello from HomeController!',
                    'users' => [
                        ['name' => 'John', 'email' => 'john@example.com'],
                        ['name' => 'Jane', 'email' => 'jane@example.com'],
                        ['name' => 'Bob', 'email' => 'bob@example.com']
                    ]
                ];

                // Debug вывод
                dump($data['users'], 'Users Array');

                timer_stop('prepare_data');
                memory_snapshot('after_prepare');
            });

            // Симуляция SQL запроса
            query_log('SELECT * FROM users WHERE active = 1', ['active' => 1], 15.5, 3);
            query_log('SELECT * FROM posts WHERE user_id = ?', [1], 8.2, 5);

            $data = [
                'title' => 'Welcome to TorrentPier',
                'message' => 'Hello from HomeController!',
                'users' => [
                    ['name' => 'John', 'email' => 'john@example.com'],
                    ['name' => 'Jane', 'email' => 'jane@example.com'],
                    ['name' => 'Bob', 'email' => 'bob@example.com']
                ]
            ];

            timer_stop('total');
            memory_dump();

            // Используем внедренный TemplateEngine
            $this->view->display('welcome.tpl', $data);
        });
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

        // Используем внедренный TemplateEngine
        $this->view->display('welcome.tpl', $data);
    }
}
