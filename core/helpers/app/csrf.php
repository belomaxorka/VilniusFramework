<?php declare(strict_types=1);

/**
 * CSRF Helper
 */

if (!function_exists('csrf_token')) {
    /**
     * Get CSRF token
     *
     * @return string
     */
    function csrf_token(): string
    {
        return \Core\Session::generateCsrfToken();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate CSRF hidden input field
     *
     * @return string
     */
    function csrf_field(): string
    {
        $token = csrf_token();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_meta')) {
    /**
     * Generate CSRF meta tag for AJAX requests
     *
     * @return string
     */
    function csrf_meta(): string
    {
        $token = csrf_token();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

