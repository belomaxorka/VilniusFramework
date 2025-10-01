<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;

/**
 * Коллектор информации о HTTP ответе
 */
class ResponseCollector extends AbstractCollector
{
    private ?int $statusCode = null;
    private array $headers = [];
    private ?int $contentLength = null;
    private ?string $contentType = null;
    private ?float $responseTime = null;

    public function __construct()
    {
        $this->priority = 88;
    }

    public function getName(): string
    {
        return 'response';
    }

    public function getTitle(): string
    {
        return 'Response';
    }

    public function getIcon(): string
    {
        return '📤';
    }

    /**
     * Установить данные ответа вручную
     */
    public function setResponseData(
        ?int    $statusCode = null,
        ?array  $headers = null,
        ?int    $contentLength = null,
        ?string $contentType = null,
        ?float  $responseTime = null
    ): void
    {
        if ($statusCode !== null) $this->statusCode = $statusCode;
        if ($headers !== null) $this->headers = $headers;
        if ($contentLength !== null) $this->contentLength = $contentLength;
        if ($contentType !== null) $this->contentType = $contentType;
        if ($responseTime !== null) $this->responseTime = $responseTime;
    }

    public function collect(): void
    {
        // Определяем status code
        $statusCode = $this->statusCode ?? http_response_code();
        if ($statusCode === false) {
            $statusCode = 200; // По умолчанию
        }

        // Получаем отправленные headers
        $headers = $this->headers;
        if (empty($headers) && function_exists('headers_list')) {
            $headersList = headers_list();
            foreach ($headersList as $header) {
                if (strpos($header, ':') !== false) {
                    [$key, $value] = explode(':', $header, 2);
                    $headers[trim($key)] = trim($value);
                }
            }
        }

        // Content-Type
        $contentType = $this->contentType;
        if (!$contentType && isset($headers['Content-Type'])) {
            $contentType = $headers['Content-Type'];
        }

        // Response Time
        $responseTime = $this->responseTime;
        if ($responseTime === null && defined('VILNIUS_START')) {
            $responseTime = (microtime(true) - VILNIUS_START) * 1000;
        }

        // Content-Length (будет определен при рендеринге)
        $contentLength = $this->contentLength;

        $this->data = [
            'status_code' => $statusCode,
            'status_text' => $this->getStatusText($statusCode),
            'headers' => $headers,
            'content_type' => $contentType,
            'content_length' => $contentLength,
            'response_time' => $responseTime,
            'protocol' => \Core\Http::getProtocol(),
        ];
    }

    public function render(): string
    {
        $html = '<div style="padding: 20px;">';

        // Status Section
        $statusColor = $this->getStatusColor($this->data['status_code']);
        $html .= '<div style="background: ' . $statusColor . '20; border-left: 4px solid ' . $statusColor . '; padding: 15px; margin-bottom: 20px; border-radius: 4px;">';
        $html .= '<h3 style="margin: 0 0 10px 0; color: ' . $statusColor . ';">HTTP Response Status</h3>';
        $html .= '<div style="font-size: 24px; font-weight: bold; color: ' . $statusColor . ';">';
        $html .= $this->data['status_code'] . ' ' . htmlspecialchars($this->data['status_text']);
        $html .= '</div>';
        $html .= '<div style="margin-top: 5px; color: #666; font-size: 13px;">';
        $html .= htmlspecialchars($this->data['protocol']);
        $html .= '</div>';
        $html .= '</div>';

        // Quick Stats
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">';

        // Response Time
        if ($this->data['response_time'] !== null) {
            $html .= $this->renderStatCard(
                '⏱️ Response Time',
                $this->formatTime($this->data['response_time']),
                $this->getTimeColor($this->data['response_time'])
            );
        }

        // Content-Type
        if ($this->data['content_type']) {
            $html .= $this->renderStatCard(
                '📄 Content-Type',
                htmlspecialchars($this->data['content_type']),
                '#2196f3'
            );
        }

        // Content-Length
        if ($this->data['content_length']) {
            $html .= $this->renderStatCard(
                '📦 Content-Length',
                $this->formatBytes($this->data['content_length']),
                '#9c27b0'
            );
        }

        // Headers Count
        $html .= $this->renderStatCard(
            '📋 Headers',
            count($this->data['headers']) . ' sent',
            '#ff9800'
        );

        $html .= '</div>';

        // Response Headers
        if (!empty($this->data['headers'])) {
            $html .= '<h3 style="color: #1976d2; margin-bottom: 10px;">Response Headers</h3>';
            $html .= '<table style="width: 100%; border-collapse: collapse; background: white; margin-bottom: 20px;">';
            $html .= '<thead>';
            $html .= '<tr style="background: #e3f2fd;">';
            $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold; width: 30%;">Header</th>';
            $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold;">Value</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            foreach ($this->data['headers'] as $key => $value) {
                $html .= '<tr>';
                $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; font-weight: bold;">'
                    . htmlspecialchars($key) . '</td>';
                $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; word-break: break-all;">'
                    . htmlspecialchars($value) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        // Status Code Info
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
        $html .= '<h4 style="margin: 0 0 10px 0;">📖 Status Code Information</h4>';
        $html .= '<p style="margin: 0; color: #666;">' . $this->getStatusDescription($this->data['status_code']) . '</p>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    public function getBadge(): ?string
    {
        return (string)$this->data['status_code'];
    }

    public function getHeaderStats(): array
    {
        $stats = [];

        // Status Code
        $statusColor = $this->getStatusColor($this->data['status_code']);
        $stats[] = [
            'icon' => '📤',
            'value' => $this->data['status_code'] . ' ' . $this->data['status_text'],
            'color' => $statusColor,
        ];

        return $stats;
    }

    /**
     * Рендер stat card
     */
    private function renderStatCard(string $title, string $value, string $color): string
    {
        $html = '<div style="background: white; padding: 15px; border-radius: 5px; border-left: 4px solid ' . $color . ';">';
        $html .= '<div style="font-size: 12px; color: #666; margin-bottom: 5px;">' . $title . '</div>';
        $html .= '<div style="font-size: 18px; font-weight: bold; color: ' . $color . ';">' . $value . '</div>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Получить текст статуса
     */
    private function getStatusText(int $code): string
    {
        return match ($code) {
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

            default => 'Unknown Status',
        };
    }

    /**
     * Получить описание статуса
     */
    private function getStatusDescription(int $code): string
    {
        $category = (int)($code / 100);

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
     * Получить цвет для статуса
     */
    private function getStatusColor(int $code): string
    {
        $category = (int)($code / 100);

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
     * Получить цвет для времени ответа
     */
    private function getTimeColor(float $time): string
    {
        if ($time < 100) return '#4caf50'; // Fast - Green
        if ($time < 500) return '#ff9800'; // Medium - Orange
        return '#f44336'; // Slow - Red
    }
}

