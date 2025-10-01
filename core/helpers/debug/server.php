<?php declare(strict_types=1);

/**
 * Dump Server Functions
 */

if (!function_exists('server_dump')) {
    /**
     * Send dump to dump server
     *
     * @param mixed $data Data to dump
     * @param string|null $label Optional label
     * @return bool
     */
    function server_dump(mixed $data, ?string $label = null): bool
    {
        return \Core\DumpClient::dump($data, $label);
    }
}

if (!function_exists('dd_server')) {
    /**
     * Dump to server and die
     *
     * @param mixed $data Data to dump
     * @param string|null $label Optional label
     * @return never
     */
    function dd_server(mixed $data, ?string $label = null): never
    {
        \Core\DumpClient::dump($data, $label);
        exit(1);
    }
}

if (!function_exists('dump_server_available')) {
    /**
     * Check if dump server is available
     *
     * @return bool
     */
    function dump_server_available(): bool
    {
        return \Core\DumpClient::isServerAvailable();
    }
}
