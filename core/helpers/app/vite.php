<?php

/**
 * Vite Asset Management Helpers
 * 
 * Provides functions to load assets compiled by Vite
 */

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
        
        // Load manifest only once
        if ($manifest === null) {
            $manifestPath = __DIR__ . '/../../../public/build/.vite/manifest.json';
            
            if (!file_exists($manifestPath)) {
                // Development mode - return dev server URL
                if (vite_is_dev_mode()) {
                    if ($type === 'js') {
                        return "http://localhost:5173/resources/js/{$entry}.js";
                    }
                    return null; // CSS is handled by JS in dev mode
                }
                
                return null;
            }
            
            $manifest = json_decode(file_get_contents($manifestPath), true);
        }
        
        $entryKey = "resources/js/{$entry}.js";
        
        if (!isset($manifest[$entryKey])) {
            return null;
        }
        
        $entry = $manifest[$entryKey];
        
        if ($type === 'css' && isset($entry['css'][0])) {
            return '/build/' . $entry['css'][0];
        }
        
        if ($type === 'js' && isset($entry['file'])) {
            return '/build/' . $entry['file'];
        }
        
        return null;
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
            // Check if hot file exists (created by Vite dev server)
            $hotFile = __DIR__ . '/../../../public/hot';
            $isDevMode = file_exists($hotFile);
        }
        
        return $isDevMode;
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
            $html[] = '<script type="module" src="http://localhost:5173/@vite/client"></script>';
            $html[] = '<script type="module" src="http://localhost:5173/resources/js/' . $entry . '.js"></script>';
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

