<?php

namespace Core;

class App
{
    public static function init(): void
    {
        // Load configuration files
        Config::load(ROOT . '/config');
    }
}
