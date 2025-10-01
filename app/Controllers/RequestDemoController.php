<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Http;

class RequestDemoController
{
    public function demo(): void
    {
        // Обработка установки cookie
        $cookiesSet = false;
        if (Http::has('set_cookie')) {
            setcookie('demo_cookie', 'test_value_' . time(), time() + 3600, '/');
            $cookiesSet = true;
        }

        // Подготовка данных о текущем запросе
        $requestInfo = [
            'method' => Http::getMethod(),
            'uri' => Http::getUri(),
            'ip' => Http::getClientIp(),
            'time' => date('Y-m-d H:i:s'),
        ];

        // Проверка POST данных
        $postData = !empty(Http::getPostData());

        $data = [
            'title' => 'Request Collector Demo',
            'request_info' => $requestInfo,
            'post_data' => $postData,
            'cookies_set' => $cookiesSet,
        ];

        display('request-demo.tpl', $data);
    }
}

