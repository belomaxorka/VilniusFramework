<?php declare(strict_types=1);

namespace Core\Http;

/**
 * HTTP Status Codes и их описания
 * 
 * Централизованное хранилище всех HTTP статус-кодов.
 * Используется в ErrorRenderer и ResponseCollector.
 */
class HttpStatus
{
    /**
     * Все известные HTTP статус-коды
     */
    private const STATUS_CODES = [
        // 1xx Informational
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',

        // 2xx Success
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',

        // 3xx Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        // 4xx Client Errors
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',

        // 5xx Server Errors
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * Получить текстовое описание статус-кода
     */
    public static function getText(int $code): string
    {
        return self::STATUS_CODES[$code] ?? 'Unknown Status';
    }

    /**
     * Получить полное описание категории статус-кода
     */
    public static function getDescription(int $code): string
    {
        $category = self::getCategory($code);

        return match ($category) {
            1 => 'ℹ️ Informational response - Request received, continuing process.',
            2 => '✅ Success - The request was successfully received, understood, and accepted.',
            3 => '↪️ Redirection - Further action needs to be taken to complete the request.',
            4 => '❌ Client Error - The request contains bad syntax or cannot be fulfilled.',
            5 => '🔥 Server Error - The server failed to fulfill an apparently valid request.',
            default => '❓ Unknown status code category.',
        };
    }

    /**
     * Получить цвет для статус-кода
     */
    public static function getColor(int $code): string
    {
        $category = self::getCategory($code);

        return match ($category) {
            1 => '#2196f3', // Blue - Informational
            2 => '#4caf50', // Green - Success
            3 => '#ff9800', // Orange - Redirection
            4 => '#ff5722', // Red-Orange - Client Error
            5 => '#f44336', // Red - Server Error
            default => '#757575', // Grey - Unknown
        };
    }

    /**
     * Получить категорию статус-кода (1, 2, 3, 4, 5)
     */
    public static function getCategory(int $code): int
    {
        return (int)($code / 100);
    }

    /**
     * Проверить, является ли код успешным (2xx)
     */
    public static function isSuccess(int $code): bool
    {
        return self::getCategory($code) === 2;
    }

    /**
     * Проверить, является ли код ошибкой клиента (4xx)
     */
    public static function isClientError(int $code): bool
    {
        return self::getCategory($code) === 4;
    }

    /**
     * Проверить, является ли код ошибкой сервера (5xx)
     */
    public static function isServerError(int $code): bool
    {
        return self::getCategory($code) === 5;
    }

    /**
     * Проверить, является ли код редиректом (3xx)
     */
    public static function isRedirection(int $code): bool
    {
        return self::getCategory($code) === 3;
    }

    /**
     * Получить все доступные статус-коды
     * 
     * @return array<int, string> Массив [код => текст]
     */
    public static function getAll(): array
    {
        return self::STATUS_CODES;
    }
}

