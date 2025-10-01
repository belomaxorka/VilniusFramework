<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $data = [
            'title' => 'Welcome to Vilnius!',
            'message' => 'A modern, lightweight PHP framework',
            'description' => 'Vilnius Framework - A modern, lightweight PHP framework'
        ];

        return $this->view('welcome.twig', $data);
    }
}
