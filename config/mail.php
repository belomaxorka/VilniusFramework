<?php declare(strict_types=1);

/**
 * Email Configuration
 *
 * Supported drivers: smtp, sendgrid, mailgun, log
 */

return [
    /**
     * Default email driver
     */
    'default' => env('MAIL_DRIVER', 'log'),

    /**
     * Default from address
     */
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'name' => env('MAIL_FROM_NAME', 'My Framework'),
    ],

    /**
     * Driver configurations
     */
    'drivers' => [
        /**
         * SMTP Driver
         * 
         * Standard SMTP email sending
         */
        'smtp' => [
            'driver' => 'smtp',
            'host' => env('MAIL_SMTP_HOST', 'localhost'),
            'port' => env('MAIL_SMTP_PORT', 587),
            'username' => env('MAIL_SMTP_USERNAME', ''),
            'password' => env('MAIL_SMTP_PASSWORD', ''),
            'encryption' => env('MAIL_SMTP_ENCRYPTION', 'tls'), // tls, ssl, or empty
            'timeout' => env('MAIL_SMTP_TIMEOUT', 30),
        ],

        /**
         * SendGrid Driver
         * 
         * SendGrid API email sending
         * Get your API key from: https://app.sendgrid.com/settings/api_keys
         */
        'sendgrid' => [
            'driver' => 'sendgrid',
            'api_key' => env('MAIL_SENDGRID_API_KEY', ''),
        ],

        /**
         * Mailgun Driver
         * 
         * Mailgun API email sending
         * Get your credentials from: https://app.mailgun.com/
         */
        'mailgun' => [
            'driver' => 'mailgun',
            'api_key' => env('MAIL_MAILGUN_API_KEY', ''),
            'domain' => env('MAIL_MAILGUN_DOMAIN', ''),
            'endpoint' => env('MAIL_MAILGUN_ENDPOINT', 'api.mailgun.net'), // api.eu.mailgun.net for EU
        ],

        /**
         * Log Driver
         * 
         * Log emails instead of sending (useful for development)
         */
        'log' => [
            'driver' => 'log',
            'path' => env('MAIL_LOG_PATH', LOG_DIR . '/emails.log'),
        ],
    ],
];

