<?php declare(strict_types=1);

namespace App\Controllers;

class RequestDemoController
{
    public function demo(): void
    {
        // Обработка установки cookie
        $cookiesSet = false;
        if (isset($_GET['set_cookie'])) {
            setcookie('demo_cookie', 'test_value_' . time(), time() + 3600, '/');
            $cookiesSet = true;
        }

        // Подготовка данных о текущем запросе
        $requestInfo = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            'time' => date('Y-m-d H:i:s'),
        ];

        // Проверка POST данных
        $postData = !empty($_POST);

        $data = [
            'title' => 'Request Collector Demo',
            'request_info' => $requestInfo,
            'post_data' => $postData,
            'cookies_set' => $cookiesSet,
        ];

        display('request-demo.tpl', $data);
    }
}

