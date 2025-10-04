<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\DebugToolbar\ColorPalette;
use Core\DebugToolbar\HtmlRenderer;
use Core\Http\HttpStatus;

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
        $this->priority = 89; // Сразу после Request (90)
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
            'status_text' => HttpStatus::getText($statusCode),
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
        $statusColor = HttpStatus::getColor($this->data['status_code']);
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
            $html .= HtmlRenderer::renderStatCard(
                '⏱️ Response Time',
                $this->formatTime($this->data['response_time']),
                $this->getTimeColor($this->data['response_time'])
            );
        }

        // Content-Type
        if ($this->data['content_type']) {
            $html .= HtmlRenderer::renderStatCard(
                '📄 Content-Type',
                htmlspecialchars($this->data['content_type']),
                ColorPalette::INFO
            );
        }

        // Content-Length
        if ($this->data['content_length']) {
            $html .= HtmlRenderer::renderStatCard(
                '📦 Content-Length',
                $this->formatBytes($this->data['content_length']),
                ColorPalette::SECONDARY
            );
        }

        // Headers Count
        $html .= HtmlRenderer::renderStatCard(
            '📋 Headers',
            count($this->data['headers']) . ' sent',
            ColorPalette::ACCENT
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
        $html .= '<p style="margin: 0; color: #666;">' . HttpStatus::getDescription($this->data['status_code']) . '</p>';
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
        $statusColor = HttpStatus::getColor($this->data['status_code']);
        $stats[] = [
            'icon' => '📤',
            'value' => $this->data['status_code'] . ' ' . $this->data['status_text'],
            'color' => $statusColor,
        ];

        return $stats;
    }

}

