<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Database;

class HomeController extends Controller
{
    public function index()
    {
        $db = Database::getInstance();
        
        // Получаем данные из БД
        $users = $db->table('demo_users')
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get();
        
        // Статистика
        $stats = [
            'total_users' => $db->table('demo_users')->count(),
            'active_users' => $db->table('demo_users')->where('status', 'active')->count(),
            'total_posts' => $db->table('demo_users')->sum('posts_count'),
            'avg_posts' => round($db->table('demo_users')->avg('posts_count'), 1),
        ];
        
        // Группировка по ролям
        $roleStats = [
            'admin' => $db->table('demo_users')->where('role', 'admin')->count(),
            'moderator' => $db->table('demo_users')->where('role', 'moderator')->count(),
            'user' => $db->table('demo_users')->where('role', 'user')->count(),
        ];
        
        return $this->view('dashboard.twig', [
            'title' => 'Vilnius Framework - Dashboard',
            'description' => 'Modern PHP Framework with Vue 3',
            'users' => $users,
            'stats' => $stats,
            'roleStats' => $roleStats,
        ]);
    }
    
    /**
     * API: Получить список пользователей
     */
    public function getUsers()
    {
        $db = Database::getInstance();
        
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $query = $db->table('demo_users');
        
        // Фильтр по роли
        if ($role) {
            $query->where('role', $role);
        }
        
        // Фильтр по статусу
        if ($status) {
            $query->where('status', $status);
        }
        
        // Поиск по имени или email (используем LOWER для кириллицы)
        if ($search) {
            $searchLower = mb_strtolower($search, 'UTF-8');
            $query->whereRaw("(LOWER(name) LIKE ? OR LOWER(email) LIKE ?)", [
                "%{$searchLower}%",
                "%{$searchLower}%"
            ]);
        }
        
        $users = $query->orderBy('created_at', 'DESC')->get();
        
        return $this->json(['users' => $users]);
    }
    
    /**
     * API: Создать пользователя
     */
    public function createUser()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $db = Database::getInstance();
        
        $userId = $db->table('demo_users')->insert([
            'name' => $data['name'],
            'email' => $data['email'],
            'avatar' => $data['avatar'] ?? '👤',
            'role' => $data['role'] ?? 'user',
            'status' => 'active',
            'posts_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        $user = $db->table('demo_users')->where('id', $userId)->first();
        
        return $this->json([
            'success' => true,
            'user' => $user
        ]);
    }
    
    /**
     * API: Обновить пользователя
     */
    public function updateUser($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $db = Database::getInstance();
        
        $db->table('demo_users')->where('id', $id)->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'status' => $data['status'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        $user = $db->table('demo_users')->where('id', $id)->first();
        
        return $this->json([
            'success' => true,
            'user' => $user
        ]);
    }
    
    /**
     * API: Удалить пользователя
     */
    public function deleteUser($id)
    {
        try {
            $db = Database::getInstance();
            
            // Проверяем существует ли пользователь
            $user = $db->table('demo_users')->where('id', $id)->first();
            
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'Пользователь не найден'
                ], 404);
            }
            
            // Удаляем
            $db->table('demo_users')->where('id', $id)->delete();
            
            return $this->json([
                'success' => true,
                'message' => 'Пользователь удалён'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Ошибка при удалении: ' . $e->getMessage()
            ], 500);
        }
    }
}
