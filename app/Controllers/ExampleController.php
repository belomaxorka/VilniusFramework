<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Response;

/**
 * Example Controller
 * 
 * Пример использования нового базового контроллера с Request/Response
 */
class ExampleController extends Controller
{
    /**
     * Показать JSON ответ
     */
    public function apiExample(): Response
    {
        $data = [
            'message' => 'Hello from API',
            'timestamp' => time(),
            'ip' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ];

        return $this->success('Data fetched successfully', $data);
    }

    /**
     * Пример работы с входящими данными
     */
    public function formExample(): Response
    {
        // Получить все данные запроса
        $allData = $this->request->all();

        // Получить только определенные поля
        $onlyName = $this->request->only(['name', 'email']);

        // Получить все кроме
        $exceptPassword = $this->request->except(['password']);

        // Проверить наличие параметра
        if ($this->request->has('name')) {
            $name = $this->request->input('name');
        }

        // Проверить, заполнен ли параметр
        if ($this->request->filled('email')) {
            $email = $this->request->input('email');
        }

        // Magic get
        $name = $this->request->name;

        return $this->json([
            'all' => $allData,
            'only' => $onlyName,
            'except' => $exceptPassword,
        ]);
    }

    /**
     * Пример редиректов
     */
    public function redirectExample(): Response
    {
        // Простой редирект
        // return $this->redirect('/home');

        // Редирект на именованный роут
        // return $this->redirectRoute('home');

        // Редирект назад
        return $this->back();
    }

    /**
     * Пример работы с файлами
     */
    public function downloadExample(): Response
    {
        $filePath = ROOT . '/README.md';

        if (!file_exists($filePath)) {
            return $this->notFound('File not found');
        }

        return $this->download($filePath, 'readme.txt');
    }

    /**
     * Пример условных ответов
     */
    public function conditionalExample(): Response
    {
        // Если запрос хочет JSON, вернем JSON
        if ($this->request->wantsJson()) {
            return $this->json(['message' => 'JSON response']);
        }

        // Иначе HTML
        return $this->html('<h1>HTML response</h1>');
    }

    /**
     * Пример проверки метода
     */
    public function methodExample(): Response
    {
        if ($this->request->isMethod('POST')) {
            return $this->success('POST request received');
        }

        return $this->view('example/form');
    }

    /**
     * Пример работы с headers
     */
    public function headersExample(): Response
    {
        $token = $this->request->bearerToken();
        $acceptLanguage = $this->request->header('Accept-Language');

        $allHeaders = $this->request->headers();

        return $this->json([
            'token' => $token,
            'accept_language' => $acceptLanguage,
            'all_headers' => $allHeaders,
        ]);
    }

    /**
     * Пример работы с cookies
     */
    public function cookieExample(): Response
    {
        $response = $this->json(['message' => 'Cookie set']);

        // Установить cookie
        return $response->cookie('example_cookie', 'example_value', time() + 3600);
    }

    /**
     * Пример создания ответа с кастомными заголовками
     */
    public function customHeadersExample(): Response
    {
        return $this->json(['message' => 'With custom headers'])
            ->withHeaders([
                'X-Custom-Header' => 'Custom Value',
                'X-API-Version' => '1.0',
            ]);
    }

    /**
     * Пример различных статус кодов
     */
    public function statusCodesExample(string $type): Response
    {
        return match($type) {
            'created' => $this->created(['id' => 123], 'Resource created'),
            'no-content' => $this->noContent(),
            'not-found' => $this->notFound('Resource not found'),
            'unauthorized' => $this->unauthorized('Authentication required'),
            'forbidden' => $this->forbidden('Access denied'),
            'error' => $this->error('Something went wrong', 500),
            default => $this->success('OK'),
        };
    }

    /**
     * Пример работы с uploaded файлами
     */
    public function uploadExample(): Response
    {
        if (!$this->request->hasFile('avatar')) {
            return $this->error('No file uploaded', 400);
        }

        $file = $this->request->file('avatar');

        return $this->json([
            'name' => $file['name'],
            'type' => $file['type'],
            'size' => $file['size'],
            'tmp_name' => $file['tmp_name'],
        ]);
    }
}

