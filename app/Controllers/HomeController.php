<?php declare(strict_types=1);

namespace App\Controllers;

class HomeController extends Controller
{
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
