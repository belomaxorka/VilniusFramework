<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Cache\CacheManager;
use Core\Contracts\DatabaseInterface;
use Core\Logger;
use Core\Request;
use Core\Response;

class HomeController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        Request                     $request,
        Response                    $response,
        protected DatabaseInterface $db,
        protected CacheManager      $cache,
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
        Logger::info($greeting);

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

        // Логируем статистику
        Logger::info("Total users in database: {$totalUsers}");

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
