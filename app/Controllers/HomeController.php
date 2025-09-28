<?php declare(strict_types=1);

namespace App\Controllers;

class HomeController
{
    public function index(): void
    {
        // Пример использования системы дебага
        $data = [
            'message' => 'Hello from HomeController!',
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => env('APP_ENV', 'unknown'),
            'debug_mode' => is_debug(),
        ];

        // Собираем данные для дебага
        collect($data, 'Controller Data');
        collect($_SERVER, 'Server Variables');
        
        // Выводим данные
        dump($data, 'HomeController Data');
        
        echo "Hello from HomeController!";
        
        // Показываем все собранные данные
        dump_all();
    }

    public function name(string $name): void
    {
        // Пример с красивым выводом
        $userData = [
            'name' => $name,
            'greeting' => __('hello', ['name' => $name]),
            'request_time' => microtime(true),
        ];
        
        dump_pretty($userData, 'User Data');
        
        echo __('hello', ['name' => $name]);
    }
    
    public function debug(): void
    {
        // Демонстрация различных функций дебага
        
        // Обычный dump
        dump(['test' => 'data'], 'Regular Dump');
        
        // Красивый dump
        dump_pretty(['nested' => ['array' => ['with' => 'data']]], 'Pretty Dump');
        
        // Backtrace
        trace('Current Backtrace');
        
        // Benchmark
        $result = benchmark(function() {
            // Имитация работы
            usleep(100000); // 100ms
            return 'Benchmark result';
        }, 'Test Function');
        
        // Проверка окружения
        dump([
            'environment' => env('APP_ENV'),
            'is_debug' => is_debug(),
            'is_dev' => is_dev(),
            'is_prod' => is_prod(),
        ], 'Environment Info');
        
        echo "Debug page loaded successfully!";
    }
}
