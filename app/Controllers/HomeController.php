<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        \Core\Cache::set('key', 'value', 3600);
        $get = \Core\Cache::get('key');

        $data = [
            'title' => $get,
            'message' => 'A modern, lightweight PHP framework',
            'description' => 'Vilnius Framework - A modern, lightweight PHP framework'
        ];

        return $this->view('welcome.twig', $data);
    }
}
