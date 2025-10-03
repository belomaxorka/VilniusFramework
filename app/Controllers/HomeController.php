<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Response;
use Core\Cache;

class HomeController extends Controller
{
    public function index(): Response
    {
        Cache::add('title', 'Welcome to Vilnius!', 3600);

        $data = [
            'title' => Cache::get('title'),
            'message' => 'A modern, lightweight PHP framework',
            'description' => 'Vilnius Framework - A modern, lightweight PHP framework'
        ];

        return $this->view('welcome.twig', $data);
    }
}
