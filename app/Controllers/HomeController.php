<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Cache\CacheManager;
use Core\Database;
use Core\Logger;
use Core\Request;
use Core\Response;

class HomeController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        Request                $request,
        Response               $response,
        protected Database     $db,
        protected CacheManager $cache,
        protected Logger       $logger,
    )
    {
        parent::__construct($request, $response);
    }

    /**
     * Home page
     */
    public function index(): Response
    {
        // Greeting
        $hour = (int)date('H');
        if ($hour < 6) {
            $greeting = 'Good Night 🌙';
        } elseif ($hour < 12) {
            $greeting = 'Good Morning ☀️';
        } elseif ($hour < 18) {
            $greeting = 'Good Afternoon 🌤️';
        } else {
            $greeting = 'Good Evening 🌆';
        }

        // Add something into log ...
        $this->logger::info($greeting);

        // ==========================================
        // Примеры работы с базой данных через DI
        // ==========================================

        // 1. Получение всех пользователей через QueryBuilder
        $users = $this->db->table('users')->get();

        // 2. Получение пользователей с условиями
        $verifiedUsers = $this->db->table('users')
            ->whereNotNull('email_verified_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // 3. Получение одного пользователя
        $firstUser = $this->db->table('users')
            ->where('id', 1)
            ->first();

        // 4. Подсчет количества пользователей
        $totalUsers = $this->db->table('users')->count();

        // 5. Пример сырого SQL запроса (если нужно что-то специфичное)
        $customQuery = $this->db->select(
            'SELECT name, email FROM users WHERE created_at > ? LIMIT ?',
            [date('Y-m-d', strtotime('-30 days')), 10]
        );

        // 6. Пример вставки данных (раскомментируйте для теста)
        /*
        $this->db->table('users')->insert([
            'name' => 'Test User',
            'email' => 'test' . time() . '@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        */

        // 7. Пример обновления данных
        /*
        $this->db->table('users')
            ->where('id', 1)
            ->update([
                'name' => 'Updated Name',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        */

        // 8. Пример транзакции
        /*
        $this->db->transaction(function () {
            $this->db->table('users')->insert([
                'name' => 'User 1',
                'email' => 'user1@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT),
            ]);
            
            $this->db->table('users')->insert([
                'name' => 'User 2',
                'email' => 'user2@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT),
            ]);
        });
        */

        // Логируем статистику
        $this->logger::info("Total users in database: {$totalUsers}");

        // Render template
        return $this->view('welcome.twig', [
            'title' => 'Vilnius Framework',
            'greeting' => $greeting,
            'phpVersion' => PHP_VERSION,
            'serverTime' => date('H:i:s'),
            'initialCount' => rand(0, 10),
            
            // Передаем данные из БД в шаблон
            'totalUsers' => $totalUsers,
            'users' => $users,
            'verifiedUsers' => $verifiedUsers,
            'firstUser' => $firstUser,
        ]);
    }
}
