<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\DebugToolbar\ColorPalette;
use Core\DebugToolbar\HtmlRenderer;
use Core\Environment;
use Core\Http;

/**
 * Коллектор информации о HTTP-запросе
 */
class RequestCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 90; // Высокий приоритет, отображается одним из первых
    }

    public function getName(): string
    {
        return 'request';
    }

    public function getTitle(): string
    {
        return 'Request';
    }

    public function getIcon(): string
    {
        return '🌐';
    }

    public function collect(): void
    {
        $this->data = [
            'method' => Http::getMethod(),
            'uri' => Http::getUri(),
            'query_string' => Http::getQueryString(),
            'protocol' => Http::getProtocol(),
            'scheme' => Http::getScheme(),
            'host' => Http::getHost(),
            'port' => Http::getPort(),
            'path' => Http::getPath(),
            'remote_addr' => Http::getClientIp(),
            'user_agent' => Http::getUserAgent(),
            'referer' => Http::getReferer(),
            'request_time' => Http::getRequestTime(),
            'get' => Http::getQueryParams(),
            'post' => Http::getPostData(),
            'cookies' => Http::getCookies(),
            'files' => Http::getFiles(),
            'headers' => Http::getHeaders(),
            'server' => $this->filterServer(),
        ];
    }

    public function render(): string
    {
        $html = '<div style="padding: 20px;">';

        // Request Info Section
        $html .= '<h3 style="margin-top: 0;">🌐 Request Information</h3>';

        // Basic Info
        $html .= $this->renderSection('Basic Info', [
            'Method' => HtmlRenderer::renderBadge($this->data['method'], $this->getMethodColor($this->data['method'])),
            'URI' => '<code>' . htmlspecialchars($this->data['uri']) . '</code>',
            'Full URL' => '<code>' . htmlspecialchars(Http::getFullUrl()) . '</code>',
            'Protocol' => $this->data['protocol'],
            'Remote Address' => $this->data['remote_addr'],
            'Request Time' => date('Y-m-d H:i:s', (int)$this->data['request_time']),
        ]);

        // GET Parameters
        if (!empty($this->data['get'])) {
            $html .= HtmlRenderer::renderDataTable('GET Parameters', $this->data['get']);
        } else {
            $html .= $this->renderEmptySection('GET Parameters', 'No GET parameters');
        }

        // POST Parameters
        if (!empty($this->data['post'])) {
            $html .= HtmlRenderer::renderDataTable('POST Parameters', $this->data['post']);
        } else {
            $html .= $this->renderEmptySection('POST Parameters', 'No POST data');
        }

        // Files
        if (!empty($this->data['files'])) {
            $html .= $this->renderFilesTable('Uploaded Files', $this->data['files']);
        }

        // Cookies
        if (!empty($this->data['cookies'])) {
            $html .= HtmlRenderer::renderDataTable('Cookies', $this->data['cookies']);
        } else {
            $html .= $this->renderEmptySection('Cookies', 'No cookies');
        }

        // Headers
        if (!empty($this->data['headers'])) {
            $html .= HtmlRenderer::renderDataTable('HTTP Headers', $this->data['headers']);
        }

        // Server Variables
        if (!empty($this->data['server'])) {
            $isProduction = Environment::isProduction();
            $title = 'Server Variables';

            if ($isProduction) {
                $title .= ' <span style="background: #f44336; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-left: 8px;">🔒 PRODUCTION MODE</span>';
            }

            $html .= HtmlRenderer::renderDataTable($title, $this->data['server'], true, $isProduction ? 'All server variables are hidden in production mode for security reasons.' : null);
        }

        $html .= '</div>';

        return $html;
    }

    public function getBadge(): ?string
    {
        return $this->data['method'] ?? null;
    }

    public function getHeaderStats(): array
    {
        return [
            [
                'icon' => '🌐',
                'value' => $this->data['method'] . ' ' . $this->data['path'],
                'color' => ColorPalette::INFO,
            ],
        ];
    }

    /**
     * Фильтровать SERVER переменные (убрать чувствительные данные и дубликаты)
     */
    private function filterServer(): array
    {
        $filtered = [];

        foreach ($_SERVER as $key => $value) {
            // Пропускаем HTTP_ заголовки (они в отдельной секции)
            if (str_starts_with($key, 'HTTP_')) {
                continue;
            }

            // В production режиме скрываем ВСЕ серверные переменные
            if (Environment::isProduction()) {
                $filtered[$key] = '***HIDDEN (PRODUCTION MODE)***';
            } else {
                // В development режиме показываем всё
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }


    /**
     * Рендер секции с информацией
     */
    private function renderSection(string $title, array $data): string
    {
        $html = '<div style="margin-bottom: 20px;">';
        $html .= '<h4 style="color: #1976d2; margin-bottom: 10px;">📋 ' . htmlspecialchars($title) . '</h4>';
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';

        foreach ($data as $key => $value) {
            $html .= '<div style="margin-bottom: 8px;">';
            $html .= '<strong>' . htmlspecialchars($key) . ':</strong> ';
            $html .= is_string($value) ? $value : htmlspecialchars(print_r($value, true));
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Рендер пустой секции
     */
    private function renderEmptySection(string $title, string $message): string
    {
        $html = '<div style="margin-bottom: 20px;">';
        $html .= '<h4 style="color: #1976d2; margin-bottom: 10px;">📋 ' . htmlspecialchars($title) . '</h4>';
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px; color: #757575; font-style: italic;">';
        $html .= htmlspecialchars($message);
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }


    /**
     * Рендер таблицы файлов
     */
    private function renderFilesTable(string $title, array $files): string
    {
        $html = '<div style="margin-bottom: 20px;">';
        $html .= '<h4 style="color: #1976d2; margin-bottom: 10px;">📋 ' . htmlspecialchars($title) . '</h4>';

        $html .= '<table style="width: 100%; border-collapse: collapse; background: white;">';
        $html .= '<thead>';
        $html .= '<tr style="background: #e3f2fd;">';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Name</th>';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Type</th>';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Size</th>';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Error</th>';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Tmp Name</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($files as $key => $file) {
            if (is_array($file['name'] ?? null)) {
                // Множественная загрузка
                foreach ($file['name'] as $index => $name) {
                    $html .= $this->renderFileRow(
                        $key . '[' . $index . ']',
                        $name,
                        $file['type'][$index] ?? '',
                        $file['size'][$index] ?? 0,
                        $file['error'][$index] ?? 0,
                        $file['tmp_name'][$index] ?? ''
                    );
                }
            } else {
                // Одиночная загрузка
                $html .= $this->renderFileRow(
                    $key,
                    $file['name'] ?? '',
                    $file['type'] ?? '',
                    $file['size'] ?? 0,
                    $file['error'] ?? 0,
                    $file['tmp_name'] ?? ''
                );
            }
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Рендер строки файла
     */
    private function renderFileRow(string $name, string $fileName, string $type, int $size, int $error, string $tmpName): string
    {
        $errorText = $error === UPLOAD_ERR_OK ? '✓ OK' : $this->getUploadErrorMessage($error);
        $errorColor = $error === UPLOAD_ERR_OK ? '#4caf50' : '#f44336';

        $html = '<tr>';
        $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace;">' . htmlspecialchars($name) . '</td>';
        $html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($type) . '</td>';
        $html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . $this->formatBytes($size) . '</td>';
        $html .= '<td style="padding: 8px; border: 1px solid #ddd; color: ' . $errorColor . ';">' . htmlspecialchars($errorText) . '</td>';
        $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; font-size: 11px;">'
            . htmlspecialchars($tmpName) . '</td>';
        $html .= '</tr>';

        return $html;
    }

    /**
     * Получить сообщение об ошибке загрузки
     */
    private function getUploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp directory',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
            default => 'Unknown error',
        };
    }

}

