<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Contracts\CacheInterface;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\LoggerInterface;
use Core\Request;
use Core\Response;

class HomeController extends Controller
{
    /**
     * Constructor with Dependency Injection
     */
    public function __construct(
        Request                     $request,
        Response                    $response,
        protected DatabaseInterface $db,
        protected CacheInterface    $cache,
        protected LoggerInterface   $logger,
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

        $this->logger->info($greeting);

        $users = $this->db->table('users')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalUsers = count($users);
        $verifiedUsers = array_slice(
            array_filter($users, fn($user) => $user['email_verified_at'] !== null),
            0,
            5
        );

        $this->logger->info("Total users in database: {$totalUsers}");

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
        ]);
    }
}
