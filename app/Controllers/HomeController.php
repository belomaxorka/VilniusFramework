<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Request;
use Core\Response;

class HomeController extends Controller
{
    /**
     * Constructor with Dependency Injection
     * 
     * Example: You can inject additional dependencies here!
     * 
     * @param Request $request Auto-injected
     * @param Response $response Auto-injected
     */
    public function __construct(
        Request $request,
        Response $response,
        // Add your custom dependencies here:
        // protected Database $db,
        // protected CacheManager $cache,
        // protected Logger $logger,
    ) {
        parent::__construct($request, $response);
        
        // Your custom dependencies are now available!
        // Example: $this->db, $this->cache, $this->logger
    }
    
    /**
     * Home page
     */
    public function index()
    {
        // Time-based greeting
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
        
        // Random initial counter value
        $initialCount = rand(0, 10);
        
        return $this->view('welcome.twig', [
            'title' => 'Vilnius Framework',
            'greeting' => $greeting,
            'phpVersion' => PHP_VERSION,
            'serverTime' => date('H:i:s'),
            'initialCount' => $initialCount,
        ]);
    }
}
