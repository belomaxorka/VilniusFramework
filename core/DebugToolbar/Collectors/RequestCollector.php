<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\Environment;

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
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'query_string' => $_SERVER['QUERY_STRING'] ?? '',
            'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? '',
            'scheme' => $this->getScheme(),
            'host' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'port' => $_SERVER['SERVER_PORT'] ?? 80,
            'path' => parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '',
            'remote_addr' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'request_time' => $_SERVER['REQUEST_TIME_FLOAT'] ?? $_SERVER['REQUEST_TIME'] ?? microtime(true),
            'get' => $_GET ?? [],
            'post' => $_POST ?? [],
            'cookies' => $_COOKIE ?? [],
            'files' => $_FILES ?? [],
            'headers' => $this->getHeaders(),
            'server' => $this->filterServer($_SERVER),
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
            'Full URL' => '<code>' . htmlspecialchars($this->getFullUrl()) . '</code>',
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
     * Получить схему (http/https)
     */
    private function getScheme(): string
    {
        if (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ) {
            return 'https';
        }
        return 'http';
    }

    /**
     * Получить IP клиента
     */
    private function getClientIp(): string
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }

    /**
     * Получить все HTTP заголовки
     */
    private function getHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders() ?: [];
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$header] = $value;
            }
        }

        return $headers;
    }

    /**
     * Фильтровать SERVER переменные (убрать чувствительные данные и дубликаты)
     */
    private function filterServer(array $server): array
    {
        $filtered = [];
        
        // В production режиме скрываем почти все значения
        $isProduction = Environment::isProduction();
        
        // Всегда скрываем чувствительные данные
        $alwaysHidden = [
            'PHP_AUTH_PW',
            'PHP_AUTH_USER',
            'HTTP_AUTHORIZATION',
            'DATABASE_URL',
            'DB_PASSWORD',
            'DB_USERNAME',
            'API_KEY',
            'SECRET_KEY',
            'AWS_SECRET',
            'STRIPE_SECRET',
        ];

        // В production режиме разрешаем показывать только базовые безопасные переменные
        $safeInProduction = [
            'REQUEST_METHOD',
            'REQUEST_URI',
            'REQUEST_TIME',
            'REQUEST_TIME_FLOAT',
            'SERVER_PROTOCOL',
            'GATEWAY_INTERFACE',
            'SERVER_SOFTWARE',
            'QUERY_STRING',
            'CONTENT_TYPE',
            'CONTENT_LENGTH',
        ];

        foreach ($server as $key => $value) {
            // Пропускаем HTTP_ заголовки (они в отдельной секции)
            if (str_starts_with($key, 'HTTP_')) {
                continue;
            }

            // Всегда скрываем чувствительные данные
            if ($this->isSensitiveKey($key, $alwaysHidden)) {
                $filtered[$key] = '***HIDDEN***';
                continue;
            }

            // В production режиме скрываем всё, кроме безопасных переменных
            if ($isProduction) {
                if (in_array($key, $safeInProduction)) {
                    $filtered[$key] = $value;
                } else {
                    $filtered[$key] = '***HIDDEN (PRODUCTION MODE)***';
                }
            } else {
                // В development режиме показываем всё
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Проверить, является ли ключ чувствительным
     */
    private function isSensitiveKey(string $key, array $sensitiveKeys): bool
    {
        // Точное совпадение
        if (in_array($key, $sensitiveKeys)) {
            return true;
        }

        // Проверяем по паттернам
        $patterns = ['PASSWORD', 'SECRET', 'TOKEN', 'KEY', 'AUTH', 'CREDENTIAL'];
        foreach ($patterns as $pattern) {
            if (str_contains(strtoupper($key), $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Получить полный URL
     */
    private function getFullUrl(): string
    {
        $url = $this->data['scheme'] . '://' . $this->data['host'];
        
        if (
            ($this->data['scheme'] === 'http' && $this->data['port'] != 80)
            || ($this->data['scheme'] === 'https' && $this->data['port'] != 443)
        ) {
            $url .= ':' . $this->data['port'];
        }

        $url .= $this->data['uri'];

        return $url;
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
            $html .= '⚠️ <strong>Production Mode:</strong> Sensitive server variables are hidden for security reasons. ';
            $html .= 'Only safe variables are shown.';
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

