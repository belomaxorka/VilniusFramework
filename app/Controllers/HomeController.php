<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Logger;
use Core\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        Logger::debug('123');
        Logger::error('123');
        Logger::info('123');
        $data = [
            'title' => 'Welcome to Vilnius Framework',
            'message' => 'Hello from HomeController!'
        ];

        return $this->view('welcome.tpl', $data);
    }
}
