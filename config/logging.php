<?php declare(strict_types=1);

/**
 * Logging Configuration
 *
 * Supported drivers: file, slack, telegram
 */

return [
    /**
     * Default logging driver
     */
    'default' => env('LOG_CHANNEL', 'file'),

    /**
     * Minimum log level
     * Available levels: debug, info, warning, error, critical
     */
    'min_level' => env('LOG_LEVEL', 'debug'),

    /**
     * Active logging channels
     * Leave empty [] to use only default driver
     * Or specify array of drivers: ['file', 'slack', 'telegram']
     * Can be a comma-separated string: 'file' or 'file,slack,telegram'
     */
    'channels' => env('LOG_CHANNELS', 'file'),

    /**
     * Driver configurations
     */
    'drivers' => [
        /**
         * File driver settings
         */
        'file' => [
            'driver' => 'file',
            'path' => env('LOG_FILE', LOG_DIR . '/app.log'),
            'min_level' => env('LOG_FILE_LEVEL', 'debug'),
            'max_size' => env('LOG_FILE_MAX_SIZE', 10485760), // 10MB in bytes
        ],

        /**
         * Slack driver settings
         * To use: create an Incoming Webhook in Slack
         * https://api.slack.com/messaging/webhooks
         */
        'slack' => [
            'driver' => 'slack',
            'webhook_url' => env('LOG_SLACK_WEBHOOK_URL', ''),
            'channel' => env('LOG_SLACK_CHANNEL', '#logs'),
            'username' => env('LOG_SLACK_USERNAME', 'Logger Bot'),
            'emoji' => env('LOG_SLACK_EMOJI', ':robot_face:'),
            'min_level' => env('LOG_SLACK_LEVEL', 'debug'), // Only errors to Slack
        ],

        /**
         * Telegram driver settings
         * To use: create a bot via @BotFather and get:
         * - Bot Token
         * - Chat ID (you can get it via @userinfobot)
         */
        'telegram' => [
            'driver' => 'telegram',
            'bot_token' => env('LOG_TELEGRAM_BOT_TOKEN', ''),
            'chat_id' => env('LOG_TELEGRAM_CHAT_ID', ''),
            'min_level' => env('LOG_TELEGRAM_LEVEL', 'debug'),
            'parse_mode' => env('LOG_TELEGRAM_PARSE_MODE', 'HTML'), // HTML or Markdown
        ],
    ],
];
