<?php declare(strict_types=1);

/**
 * Timer Functions
 */

if (!function_exists('timer_start')) {
    /**
     * Start a timer
     * 
     * @param string $name Timer name
     * @return void
     */
    function timer_start(string $name = 'default'): void
    {
        \Core\DebugTimer::start($name);
    }
}

if (!function_exists('timer_stop')) {
    /**
     * Stop a timer and return elapsed time
     * 
     * @param string $name Timer name
     * @return float Elapsed time in milliseconds
     */
    function timer_stop(string $name = 'default'): float
    {
        return \Core\DebugTimer::stop($name);
    }
}

if (!function_exists('timer_lap')) {
    /**
     * Record a lap time
     * 
     * @param string $name Timer name
     * @param string|null $label Optional lap label
     * @return float Lap time in milliseconds
     */
    function timer_lap(string $name = 'default', ?string $label = null): float
    {
        return \Core\DebugTimer::lap($name, $label);
    }
}

if (!function_exists('timer_elapsed')) {
    /**
     * Get elapsed time without stopping timer
     * 
     * @param string $name Timer name
     * @return float Elapsed time in milliseconds
     */
    function timer_elapsed(string $name = 'default'): float
    {
        return \Core\DebugTimer::getElapsed($name);
    }
}

if (!function_exists('timer_dump')) {
    /**
     * Dump timer(s) information
     * 
     * @param string|null $name Timer name (null for all timers)
     * @return void
     */
    function timer_dump(?string $name = null): void
    {
        \Core\DebugTimer::dump($name);
    }
}

if (!function_exists('timer_clear')) {
    /**
     * Clear timer(s)
     * 
     * @param string|null $name Timer name (null for all timers)
     * @return void
     */
    function timer_clear(?string $name = null): void
    {
        \Core\DebugTimer::clear($name);
    }
}

if (!function_exists('timer_measure')) {
    /**
     * Measure execution time of a function with named timer
     * 
     * @param string $name Timer name
     * @param callable $callback Function to measure
     * @return mixed Return value of the callback
     */
    function timer_measure(string $name, callable $callback): mixed
    {
        return \Core\DebugTimer::measure($name, $callback);
    }
}

