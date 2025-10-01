<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
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
            'Method' => $this->renderBadge($this->data['method'], $this->getMethodColor($this->data['method'])),
            'URI' => '<code>' . htmlspecialchars($this->data['uri']) . '</code>',
            'Full URL' => '<code>' . htmlspecialchars(Http::getFullUrl()) . '</code>',
            'Protocol' => $this->data['protocol'],
            'Remote Address' => $this->data['remote_addr'],
            'Request Time' => date('Y-m-d H:i:s', (int)$this->data['request_time']),
        ]);

        // GET Parameters
        if (!empty($this->data['get'])) {
            $html .= $this->renderDataTable('GET Parameters', $this->data['get']);
        } else {
            $html .= $this->renderEmptySection('GET Parameters', 'No GET parameters');
        }

        // POST Parameters
        if (!empty($this->data['post'])) {
            $html .= $this->renderDataTable('POST Parameters', $this->data['post']);
        } else {
            $html .= $this->renderEmptySection('POST Parameters', 'No POST data');
        }

        // Files
        if (!empty($this->data['files'])) {
            $html .= $this->renderFilesTable('Uploaded Files', $this->data['files']);
        }

        // Cookies
        if (!empty($this->data['cookies'])) {
            $html .= $this->renderDataTable('Cookies', $this->data['cookies']);
        } else {
            $html .= $this->renderEmptySection('Cookies', 'No cookies');
        }

        // Headers
        if (!empty($this->data['headers'])) {
            $html .= $this->renderDataTable('HTTP Headers', $this->data['headers']);
        }

        // Server Variables
        if (!empty($this->data['server'])) {
            $isProduction = Environment::isProduction();
            $title = 'Server Variables';

            if ($isProduction) {
                $title .= ' <span style="background: #f44336; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-left: 8px;">🔒 PRODUCTION MODE</span>';
            }

            $html .= $this->renderDataTable($title, $this->data['server'], true, $isProduction);
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
                'color' => '#2196f3',
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
     * Получить цвет для HTTP метода
     */
    private function getMethodColor(string $method): string
    {
        return match ($method) {
            'GET' => '#4caf50',
            'POST' => '#2196f3',
            'PUT' => '#ff9800',
            'PATCH' => '#9c27b0',
            'DELETE' => '#f44336',
            default => '#757575',
        };
    }

    /**
     * Рендер badge
     */
    private function renderBadge(string $text, string $color): string
    {
        return '<span style="background: ' . $color . '; color: white; padding: 4px 8px; border-radius: 3px; font-weight: bold;">'
            . htmlspecialchars($text) . '</span>';
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
     * Рендер таблицы с данными
     */
    private function renderDataTable(string $title, array $data, bool $collapsible = false, bool $isProduction = false): string
    {
        $tableId = 'table_' . md5($title . random_bytes(8));

        $html = '<div style="margin-bottom: 20px;">';
        $html .= '<h4 style="color: #1976d2; margin-bottom: 10px; cursor: ' . ($collapsible ? 'pointer' : 'default') . ';" ';

        if ($collapsible) {
            $html .= 'onclick="document.getElementById(\'' . $tableId . '\').style.display = document.getElementById(\'' . $tableId . '\').style.display === \'none\' ? \'table\' : \'none\'"';
        }

        $html .= '>📋 ' . $title . ' <span style="color: #757575; font-size: 12px;">(' . count($data) . ')</span>';

        if ($collapsible) {
            $html .= ' <span style="font-size: 12px; color: #757575;">[click to toggle]</span>';
        }

        $html .= '</h4>';

        // Предупреждение для production режима
        if ($isProduction) {
            $html .= '<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 4px; margin-bottom: 10px; color: #856404;">';
            $html .= '⚠️ <strong>Production Mode:</strong> All server variables are hidden for security reasons. ';
            $html .= 'Server variables are only visible in development mode.';
            $html .= '</div>';
        }

        $html .= '<table id="' . $tableId . '" style="width: 100%; border-collapse: collapse; background: white; ' . ($collapsible ? 'display: none;' : '') . '">';
        $html .= '<thead>';
        $html .= '<tr style="background: #e3f2fd;">';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold;">Key</th>';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold;">Value</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($data as $key => $value) {
            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; vertical-align: top; width: 30%;">'
                . htmlspecialchars($key) . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; word-break: break-all;">'
                . htmlspecialchars($this->formatValue($value)) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
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

    /**
     * Форматировать значение для отображения
     */
    private function formatValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_object($value)) {
            return get_class($value);
        }

        return (string)$value;
    }
}

