<?php declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use Core\Database;
use Core\Response;

class UserController extends Controller
{
    public function __construct(
        \Core\Request $request,
        Response $response,
        protected Database $db
    ) {
        parent::__construct($request, $response);
    }

    /**
     * Удалить пользователя
     */
    public function delete(string $id): Response
    {
        try {
            // Преобразуем ID в integer
            $userId = (int) $id;
            
            // Проверяем, существует ли пользователь
            $user = $this->db->table('users')
                ->where('id', $userId)
                ->first();

            if (!$user) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Пользователь не найден'
                ], 404);
            }

            // Удаляем пользователя
            $this->db->table('users')
                ->where('id', $userId)
                ->delete();

            // Получаем обновлённое количество пользователей
            $totalUsers = $this->db->table('users')->count();

            return $this->response->json([
                'success' => true,
                'message' => 'Пользователь успешно удалён',
                'totalUsers' => $totalUsers
            ]);

        } catch (\Throwable $e) {
            return $this->response->json([
                'success' => false,
                'message' => 'Ошибка при удалении пользователя: ' . $e->getMessage()
            ], 500);
        }
    }
}

