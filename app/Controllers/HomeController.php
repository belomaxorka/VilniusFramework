<?php declare(strict_types=1);

namespace App\Controllers;

class HomeController extends Controller
{
    /**
     * Главная страница
     */
    public function index()
    {
        // Приветствие в зависимости от времени суток
        $hour = (int)date('H');
        if ($hour < 6) {
            $greeting = 'Доброй ночи! 🌙';
        } elseif ($hour < 12) {
            $greeting = 'Доброе утро! ☀️';
        } elseif ($hour < 18) {
            $greeting = 'Добрый день! 🌤️';
        } else {
            $greeting = 'Добрый вечер! 🌆';
        }
        
        // Случайное начальное значение счетчика
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
