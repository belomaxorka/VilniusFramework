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

        // Render template
        return $this->view('welcome.twig', [
            'title' => 'Vilnius Framework',
            'greeting' => $greeting,
            'phpVersion' => PHP_VERSION,
            'serverTime' => date('H:i:s'),
            'initialCount' => rand(0, 10),
        ]);
    }
}
