<?php

/**
 * Vite Asset Management Helpers
 * 
 * Provides functions to load assets compiled by Vite
 */

if (!function_exists('vite_config')) {
    /**
     * Get Vite configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed
     */
    function vite_config(string $key, mixed $default = null): mixed
    {
        static $config = null;
        
        if ($config === null) {
            $config = require __DIR__ . '/../../../config/vite.php';
        }
        
        return $config[$key] ?? $default;
    }
}

if (!function_exists('vite_is_dev_mode')) {
    /**
     * Check if Vite dev server is running
     * 
     * @return bool
     */
    function vite_is_dev_mode(): bool
    {
        static $isDevMode = null;
        
        if ($isDevMode === null) {
            $hotFile = vite_config('hot_file', 'public/hot');
            $fullPath = __DIR__ . '/../../../' . $hotFile;
            $isDevMode = file_exists($fullPath);
        }
        
        return $isDevMode;
    }
}

if (!function_exists('vite_dev_server_url')) {
    /**
     * Get Vite dev server URL
     * 
     * @return string
     */
    function vite_dev_server_url(): string
    {
        return rtrim(vite_config('dev_server_url', 'http://localhost:5173'), '/');
    }
}

if (!function_exists('vite_asset')) {
    /**
     * Get the full URL for a Vite asset from manifest
     * 
     * @param string $entry Entry name (e.g., 'app')
     * @param string $type Asset type ('js' or 'css')
     * @return string|null Asset URL or null if not found
     */
    function vite_asset(string $entry, string $type = 'js'): ?string
    {
        static $manifest = null;
        
        // Development mode
        if (vite_is_dev_mode()) {
            $entries = vite_config('entries', ['app' => 'resources/js/app.js']);
            $entryPath = $entries[$entry] ?? "resources/js/{$entry}.js";
            
            if ($type === 'js') {
                return vite_dev_server_url() . '/' . $entryPath;
            }
            
            // CSS is handled by JS in dev mode
            return null;
        }
        
        // Production mode - load manifest
        if ($manifest === null) {
            $manifestPath = vite_config('manifest_path', 'public/build/.vite/manifest.json');
            $fullPath = __DIR__ . '/../../../' . $manifestPath;
            
            if (!file_exists($fullPath)) {
                return null;
            }
            
            $manifest = json_decode(file_get_contents($fullPath), true);
            if (!is_array($manifest)) {
                return null;
            }
        }
        
        // Get entry from configuration
        $entries = vite_config('entries', ['app' => 'resources/js/app.js']);
        $entryPath = $entries[$entry] ?? "resources/js/{$entry}.js";
        
        if (!isset($manifest[$entryPath])) {
            return null;
        }
        
        $buildPath = vite_config('build_path', '/build');
        $manifestEntry = $manifest[$entryPath];
        
        if ($type === 'css' && isset($manifestEntry['css'][0])) {
            return $buildPath . '/' . $manifestEntry['css'][0];
        }
        
        if ($type === 'js' && isset($manifestEntry['file'])) {
            return $buildPath . '/' . $manifestEntry['file'];
        }
        
        return null;
    }
}

if (!function_exists('vite')) {
    /**
     * Generate HTML tags for Vite assets
     * 
     * @param string $entry Entry name (e.g., 'app')
     * @return string HTML tags for CSS and JS
     */
    function vite(string $entry = 'app'): string
    {
        $html = [];
        
        // Development mode
        if (vite_is_dev_mode()) {
            $devServerUrl = vite_dev_server_url();
            $html[] = '<script type="module" src="' . $devServerUrl . '/@vite/client"></script>';
            
            $entries = vite_config('entries', ['app' => 'resources/js/app.js']);
            $entryPath = $entries[$entry] ?? "resources/js/{$entry}.js";
            
            $html[] = '<script type="module" src="' . $devServerUrl . '/' . $entryPath . '"></script>';
            return implode("\n    ", $html);
        }
        
        // Production mode
        $cssUrl = vite_asset($entry, 'css');
        if ($cssUrl) {
            $html[] = '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl) . '">';
        }
        
        $jsUrl = vite_asset($entry, 'js');
        if ($jsUrl) {
            $html[] = '<script type="module" src="' . htmlspecialchars($jsUrl) . '"></script>';
        }
        
        return implode("\n    ", $html);
    }
}
