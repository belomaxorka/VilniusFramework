<?php declare(strict_types=1);

return [
    /**
     * Default language for new users
     * Set to 'auto' for browser detection or specific language code
     */
    'default' => 'auto',

    /**
     * Fallback language when translation is missing
     */
    'fallback' => 'en',

    /**
     * Supported languages with their display names
     */
    'supported' => [
        'en' => 'English',
        'ru' => 'Русский',
    ],

    /**
     * Auto-detect language from browser headers
     */
    'auto_detect' => true,

    /**
     * Log missing translations
     */
    'log_missing' => true,

    /**
     * RTL (Right-to-Left) languages
     */
    'rtl_languages' => []
];
