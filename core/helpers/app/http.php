<?php declare(strict_types=1);

use Core\Request;
use Core\Response;

if (!function_exists('request')) {
    /**
     * Получить экземпляр Request
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function request(?string $key = null, mixed $default = null): mixed
    {
        $request = Request::getInstance();
        
        if ($key === null) {
            return $request;
        }
        
        return $request->input($key, $default);
    }
}

if (!function_exists('response')) {
    /**
     * Создать Response объект
     *
     * @param mixed $content
     * @param int $status
     * @param array $headers
     * @return Response
     */
    function response(mixed $content = '', int $status = 200, array $headers = []): Response
    {
        if ($content instanceof Response) {
            return $content;
        }
        
        return Response::make($content, $status, $headers);
    }
}

if (!function_exists('json')) {
    /**
     * Создать JSON response
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return Response
     */
    function json(mixed $data, int $status = 200, array $headers = []): Response
    {
        return Response::jsonResponse($data, $status, $headers);
    }
}

if (!function_exists('redirect')) {
    /**
     * Создать редирект
     *
     * @param string $url
     * @param int $status
     * @param array $headers
     * @return Response
     */
    function redirect(string $url, int $status = 302, array $headers = []): Response
    {
        return Response::redirectTo($url, $status, $headers);
    }
}

if (!function_exists('back')) {
    /**
     * Редирект назад
     *
     * @param int $status
     * @return Response
     */
    function back(int $status = 302): Response
    {
        return (new Response())->back($status);
    }
}

if (!function_exists('abort')) {
    /**
     * Прервать выполнение с указанным статусом
     *
     * @param int $code
     * @param string $message
     * @return never
     */
    function abort(int $code = 404, string $message = ''): never
    {
        http_response_code($code);
        
        if (empty($message)) {
            $message = Core\Http\HttpStatus::getText($code);
        }
        
        if (Core\Http::acceptsJson()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => $message,
                'code' => $code
            ]);
        } else {
            echo "<h1>{$code} - {$message}</h1>";
        }
        
        exit($code);
    }
}

if (!function_exists('abort_if')) {
    /**
     * Прервать выполнение если условие истинно
     *
     * @param bool $condition
     * @param int $code
     * @param string $message
     * @return void
     */
    function abort_if(bool $condition, int $code = 404, string $message = ''): void
    {
        if ($condition) {
            abort($code, $message);
        }
    }
}

if (!function_exists('abort_unless')) {
    /**
     * Прервать выполнение если условие ложно
     *
     * @param bool $condition
     * @param int $code
     * @param string $message
     * @return void
     */
    function abort_unless(bool $condition, int $code = 404, string $message = ''): void
    {
        if (!$condition) {
            abort($code, $message);
        }
    }
}

