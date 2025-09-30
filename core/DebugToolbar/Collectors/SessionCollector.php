<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;

/**
 * Коллектор информации о сессии
 */
class SessionCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 75;
    }

    public function getName(): string
    {
        return 'session';
    }

    public function getTitle(): string
    {
        return 'Session';
    }

    public function getIcon(): string
    {
        return '🔐';
    }

    public function isEnabled(): bool
    {
        // Коллектор активен только если сессия запущена
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function collect(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->data = [
            'session_id' => session_id(),
            'session_name' => session_name(),
            'session_status' => $this->getSessionStatusText(),
            'session_data' => $_SESSION ?? [],
            'session_config' => $this->getSessionConfig(),
            'cookie_params' => session_get_cookie_params(),
        ];
    }

    public function getBadge(): ?string
    {
        $count = count($this->data['session_data'] ?? []);
        return $count > 0 ? (string)$count : null;
    }

    public function render(): string
    {
        if (!$this->isEnabled()) {
            return '<div style="padding: 20px; text-align: center; color: #757575;">Session is not active</div>';
        }

        $html = '<div style="padding: 20px;">';

        // Session Info
        $html .= '<h3 style="margin-top: 0;">🔐 Session Information</h3>';
        $html .= $this->renderSection('Session Details', [
            'Session ID' => '<code>' . htmlspecialchars($this->data['session_id']) . '</code>',
            'Session Name' => '<code>' . htmlspecialchars($this->data['session_name']) . '</code>',
            'Status' => $this->renderStatusBadge($this->data['session_status']),
        ]);

        // Cookie Parameters
        $html .= $this->renderSection('Cookie Parameters', [
            'Lifetime' => $this->data['cookie_params']['lifetime'] . ' seconds' . 
                         ($this->data['cookie_params']['lifetime'] === 0 ? ' (until browser closes)' : ''),
            'Path' => '<code>' . htmlspecialchars($this->data['cookie_params']['path']) . '</code>',
            'Domain' => '<code>' . htmlspecialchars($this->data['cookie_params']['domain'] ?: 'default') . '</code>',
            'Secure' => $this->renderBoolBadge($this->data['cookie_params']['secure']),
            'HttpOnly' => $this->renderBoolBadge($this->data['cookie_params']['httponly']),
            'SameSite' => '<code>' . htmlspecialchars($this->data['cookie_params']['samesite'] ?? 'none') . '</code>',
        ]);

        // Session Configuration
        $html .= $this->renderDataTable('Session Configuration', $this->data['session_config'], true);

        // Session Data
        if (!empty($this->data['session_data'])) {
            $html .= $this->renderSessionDataTable('Session Data', $this->data['session_data']);
        } else {
            $html .= $this->renderEmptySection('Session Data', 'No session data');
        }

        $html .= '</div>';

        return $html;
    }

    public function getHeaderStats(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $count = count($this->data['session_data'] ?? []);
        if ($count === 0) {
            return [];
        }

        return [[
            'icon' => '🔐',
            'value' => $count . ' session vars',
            'color' => '#9c27b0',
        ]];
    }

    /**
     * Получить текстовый статус сессии
     */
    private function getSessionStatusText(): string
    {
        return match (session_status()) {
            PHP_SESSION_DISABLED => 'Disabled',
            PHP_SESSION_NONE => 'None',
            PHP_SESSION_ACTIVE => 'Active',
            default => 'Unknown',
        };
    }

    /**
     * Получить конфигурацию сессии
     */
    private function getSessionConfig(): array
    {
        return [
            'save_handler' => ini_get('session.save_handler'),
            'save_path' => ini_get('session.save_path'),
            'gc_maxlifetime' => ini_get('session.gc_maxlifetime') . ' seconds',
            'gc_probability' => ini_get('session.gc_probability'),
            'gc_divisor' => ini_get('session.gc_divisor'),
            'cookie_lifetime' => ini_get('session.cookie_lifetime') . ' seconds',
            'cookie_httponly' => ini_get('session.cookie_httponly') ? 'On' : 'Off',
            'cookie_secure' => ini_get('session.cookie_secure') ? 'On' : 'Off',
            'use_cookies' => ini_get('session.use_cookies') ? 'On' : 'Off',
            'use_only_cookies' => ini_get('session.use_only_cookies') ? 'On' : 'Off',
            'cache_limiter' => ini_get('session.cache_limiter'),
        ];
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
     * Рендер таблицы данных
     */
    private function renderDataTable(string $title, array $data, bool $collapsible = false): string
    {
        $tableId = 'table_' . md5($title . random_bytes(8));

        $html = '<div style="margin-bottom: 20px;">';
        $html .= '<h4 style="color: #1976d2; margin-bottom: 10px; cursor: ' . ($collapsible ? 'pointer' : 'default') . ';" ';

        if ($collapsible) {
            $html .= 'onclick="document.getElementById(\'' . $tableId . '\').style.display = document.getElementById(\'' . $tableId . '\').style.display === \'none\' ? \'table\' : \'none\'"';
        }

        $html .= '>📋 ' . htmlspecialchars($title) . ' <span style="color: #757575; font-size: 12px;">(' . count($data) . ')</span>';

        if ($collapsible) {
            $html .= ' <span style="font-size: 12px; color: #757575;">[click to toggle]</span>';
        }

        $html .= '</h4>';

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
     * Рендер таблицы данных сессии с возможностью раскрытия вложенных структур
     */
    private function renderSessionDataTable(string $title, array $data): string
    {
        $html = '<div style="margin-bottom: 20px;">';
        $html .= '<h4 style="color: #1976d2; margin-bottom: 10px;">📋 ' . htmlspecialchars($title) . ' <span style="color: #757575; font-size: 12px;">(' . count($data) . ')</span></h4>';

        $html .= '<table style="width: 100%; border-collapse: collapse; background: white;">';
        $html .= '<thead>';
        $html .= '<tr style="background: #e3f2fd;">';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold;">Key</th>';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold;">Type</th>';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold;">Value</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($data as $key => $value) {
            $type = gettype($value);
            if (is_object($value)) {
                $type = get_class($value);
            }

            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; vertical-align: top; width: 25%;">'
                . htmlspecialchars($key) . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; vertical-align: top; width: 15%; color: #1976d2;">'
                . htmlspecialchars($type) . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; word-break: break-all;">'
                . $this->formatValueHtml($value) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Рендер badge для статуса
     */
    private function renderStatusBadge(string $status): string
    {
        $color = match ($status) {
            'Active' => '#4caf50',
            'None' => '#ff9800',
            'Disabled' => '#f44336',
            default => '#757575',
        };

        return '<span style="background: ' . $color . '; color: white; padding: 4px 8px; border-radius: 3px; font-weight: bold;">'
            . htmlspecialchars($status) . '</span>';
    }

    /**
     * Рендер badge для boolean значений
     */
    private function renderBoolBadge(bool $value): string
    {
        $color = $value ? '#4caf50' : '#f44336';
        $text = $value ? 'Yes' : 'No';

        return '<span style="background: ' . $color . '; color: white; padding: 4px 8px; border-radius: 3px; font-weight: bold;">'
            . $text . '</span>';
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

    /**
     * Форматировать значение для HTML отображения
     */
    private function formatValueHtml(mixed $value): string
    {
        if (is_array($value)) {
            $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return '<pre style="margin: 0; padding: 5px; background: #f9f9f9; border-radius: 3px; max-height: 200px; overflow-y: auto;">'
                . htmlspecialchars($json) . '</pre>';
        }

        if (is_bool($value)) {
            $color = $value ? '#4caf50' : '#f44336';
            $text = $value ? 'true' : 'false';
            return '<span style="color: ' . $color . '; font-weight: bold;">' . $text . '</span>';
        }

        if (is_null($value)) {
            return '<span style="color: #9e9e9e; font-style: italic;">null</span>';
        }

        if (is_object($value)) {
            return '<span style="color: #9c27b0; font-weight: bold;">' . htmlspecialchars(get_class($value)) . '</span>';
        }

        if (is_string($value) && strlen($value) > 100) {
            return '<pre style="margin: 0; padding: 5px; background: #f9f9f9; border-radius: 3px; max-height: 200px; overflow-y: auto;">'
                . htmlspecialchars($value) . '</pre>';
        }

        return htmlspecialchars((string)$value);
    }
}

