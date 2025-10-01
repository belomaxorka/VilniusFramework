<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Request;
use Core\Response;

/**
 * Base Controller
 * 
 * Базовый класс для всех контроллеров
 */
abstract class Controller
{
    protected Request $request;
    protected Response $response;

    public function __construct()
    {
        $this->request = Request::capture();
        $this->response = new Response();
    }

    /**
     * Создать JSON ответ
     */
    protected function json(mixed $data, int $status = 200, array $headers = []): Response
    {
        return $this->response->json($data, $status, $headers);
    }

    /**
     * Создать HTML ответ
     */
    protected function html(string $content, int $status = 200, array $headers = []): Response
    {
        return $this->response->html($content, $status, $headers);
    }

    /**
     * Отрендерить view
     */
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return $this->response->view($template, $data, $status);
    }

    /**
     * Редирект
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return $this->response->redirect($url, $status);
    }

    /**
     * Редирект назад
     */
    protected function back(): Response
    {
        return $this->response->back();
    }

    /**
     * Редирект на именованный роут
     */
    protected function redirectRoute(string $name, array $params = []): Response
    {
        return $this->response->route($name, $params);
    }

    /**
     * Success JSON response
     */
    protected function success(string $message = 'Success', mixed $data = null, int $status = 200): Response
    {
        $response = ['success' => true, 'message' => $message];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $this->json($response, $status);
    }

    /**
     * Error JSON response
     */
    protected function error(string $message = 'Error', int $status = 400, array $errors = []): Response
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return $this->json($response, $status);
    }

    /**
     * Not found response
     */
    protected function notFound(string $message = 'Not Found'): Response
    {
        if ($this->request->wantsJson()) {
            return $this->error($message, Response::HTTP_NOT_FOUND);
        }
        
        return $this->view('errors/404', ['message' => $message], Response::HTTP_NOT_FOUND);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorized(string $message = 'Unauthorized'): Response
    {
        if ($this->request->wantsJson()) {
            return $this->error($message, Response::HTTP_UNAUTHORIZED);
        }
        
        return $this->redirect('/login');
    }

    /**
     * Forbidden response
     */
    protected function forbidden(string $message = 'Forbidden'): Response
    {
        if ($this->request->wantsJson()) {
            return $this->error($message, Response::HTTP_FORBIDDEN);
        }
        
        return $this->view('errors/403', ['message' => $message], Response::HTTP_FORBIDDEN);
    }

    /**
     * Download file
     */
    protected function download(string $path, ?string $name = null): Response
    {
        return $this->response->download($path, $name);
    }

    /**
     * No content response
     */
    protected function noContent(): Response
    {
        return $this->response->noContent();
    }

    /**
     * Created response (201)
     */
    protected function created(mixed $data = null, string $message = 'Created'): Response
    {
        if ($this->request->wantsJson()) {
            return $this->success($message, $data, Response::HTTP_CREATED);
        }
        
        return $this->back();
    }
}

