<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\Environment;
use Core\Env;

/**
 * Коллектор информации об окружении и PHP конфигурации
 */
class EnvironmentCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 60;
    }

    public function getName(): string
    {
        return 'environment';
    }

    public function getTitle(): string
    {
        return 'Environment';
    }

    public function getIcon(): string
    {
        return '⚙️';
    }

    public function collect(): void
    {
        $this->data = [
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'os' => PHP_OS,
            'environment' => Environment::get(),
            'debug_mode' => Environment::isDebug(),
            'env_vars' => $this->getEnvVars(),
            'php_extensions' => get_loaded_extensions(),
            'php_config' => $this->getPhpConfig(),
            'framework_info' => $this->getFrameworkInfo(),
        ];
    }

    public function render(): string
    {
        $html = '<div style="padding: 20px;">';

        // Framework & PHP Info
        $html .= '<h3 style="margin-top: 0;">⚙️ Environment Information</h3>';

        // Framework Info
        $html .= $this->renderSection('Framework', [
            'Environment' => $this->renderEnvironmentBadge($this->data['environment']),
            'Debug Mode' => $this->renderBoolBadge($this->data['debug_mode']),
            'Framework Path' => '<code>' . htmlspecialchars(dirname(__DIR__, 2)) . '</code>',
        ]);

        // PHP Info
        $html .= $this->renderSection('PHP', [
            'Version' => '<strong>' . htmlspecialchars($this->data['php_version']) . '</strong>',
            'SAPI' => '<code>' . htmlspecialchars($this->data['php_sapi']) . '</code>',
            'OS' => htmlspecialchars($this->data['os']),
            'Architecture' => PHP_INT_SIZE === 8 ? '64-bit' : '32-bit',
            'Zend Engine' => zend_version(),
        ]);

        // Environment Variables (only in development)
        if (Environment::isDevelopment() && !empty($this->data['env_vars'])) {
            $html .= $this->renderDataTable('Environment Variables (.env)', $this->data['env_vars'], true);
        } elseif (Environment::isProduction()) {
            $html .= $this->renderWarningSection(
                'Environment Variables',
                '⚠️ Environment variables are hidden in production mode for security reasons.'
            );
        }

        // PHP Configuration
        $html .= $this->renderDataTable('PHP Configuration', $this->data['php_config'], true);

        // PHP Extensions
        $html .= $this->renderExtensions('Loaded Extensions', $this->data['php_extensions']);

        $html .= '</div>';

        return $html;
    }

    public function getHeaderStats(): array
    {
        return [[
            'icon' => '⚙️',
            'value' => $this->data['environment'] . ' | PHP ' . $this->data['php_version'],
            'color' => $this->getEnvironmentColor($this->data['environment']),
        ]];
    }

    /**
     * Получить переменные окружения
     */
    private function getEnvVars(): array
    {
        if (!class_exists('\Core\Env')) {
            return [];
        }

        // Получаем все env переменные
        $envVars = [];
        $envFile = dirname(__DIR__, 2) . '/.env';

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Пропускаем комментарии
                if (str_starts_with(trim($line), '#')) {
                    continue;
                }

                // Парсим KEY=VALUE
                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);

                    // Маскируем чувствительные данные
                    if ($this->isSensitiveKey($key)) {
                        $value = '***HIDDEN***';
                    } else {
                        $value = trim($value, '"\'');
                    }

                    $envVars[$key] = $value;
                }
            }
        }

        return $envVars;
    }

    /**
     * Проверить, является ли ключ чувствительным
     */
    private function isSensitiveKey(string $key): bool
    {
        $sensitivePatterns = [
            'PASSWORD',
            'SECRET',
            'KEY',
            'TOKEN',
            'API',
            'PRIVATE',
            'CREDENTIAL',
            'AUTH',
        ];

        $upperKey = strtoupper($key);
        foreach ($sensitivePatterns as $pattern) {
            if (str_contains($upperKey, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Получить важную PHP конфигурацию
     */
    private function getPhpConfig(): array
    {
        return [
            // Memory
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . ' seconds',

            // Upload
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),

            // Display Errors
            'display_errors' => ini_get('display_errors') ? 'On' : 'Off',
            'display_startup_errors' => ini_get('display_startup_errors') ? 'On' : 'Off',
            'error_reporting' => $this->errorReportingToString((int)ini_get('error_reporting')),

            // Date/Time
            'date.timezone' => ini_get('date.timezone') ?: 'Not set',
            'default_charset' => ini_get('default_charset'),

            // Session
            'session.save_handler' => ini_get('session.save_handler'),
            'session.gc_maxlifetime' => ini_get('session.gc_maxlifetime') . ' seconds',

            // OPcache
            'opcache.enable' => extension_loaded('Zend OPcache') && ini_get('opcache.enable') ? 'On' : 'Off',
            'opcache.memory_consumption' => extension_loaded('Zend OPcache') ? ini_get('opcache.memory_consumption') . 'MB' : 'N/A',

            // Other
            'max_input_vars' => ini_get('max_input_vars'),
            'max_input_time' => ini_get('max_input_time') . ' seconds',
            'default_socket_timeout' => ini_get('default_socket_timeout') . ' seconds',
        ];
    }

    /**
     * Получить информацию о фреймворке
     */
    private function getFrameworkInfo(): array
    {
        return [
            'version' => '1.0.0', // TODO: добавить версию из константы
            'environment' => Environment::get(),
            'debug' => Environment::isDebug(),
        ];
    }

    /**
     * Конвертировать error_reporting в строку
     */
    private function errorReportingToString(int $level): string
    {
        if ($level === 0) {
            return 'None';
        }

        if ($level === E_ALL) {
            return 'E_ALL';
        }

        $levels = [];
        $constants = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];

        foreach ($constants as $value => $name) {
            if (($level & $value) === $value) {
                $levels[] = $name;
            }
        }

        return implode(' | ', $levels);
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
     * Рендер предупреждения
     */
    private function renderWarningSection(string $title, string $message): string
    {
        $html = '<div style="margin-bottom: 20px;">';
        $html .= '<h4 style="color: #1976d2; margin-bottom: 10px;">📋 ' . htmlspecialchars($title) . '</h4>';
        $html .= '<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; color: #856404;">';
        $html .= $message;
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
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold;">Setting</th>';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold;">Value</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($data as $key => $value) {
            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; vertical-align: top; width: 40%;">'
                . htmlspecialchars($key) . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; word-break: break-all;">'
                . htmlspecialchars($value) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Рендер списка расширений
     */
    private function renderExtensions(string $title, array $extensions): string
    {
        sort($extensions);

        $html = '<div style="margin-bottom: 20px;">';
        $html .= '<h4 style="color: #1976d2; margin-bottom: 10px;">📦 ' . htmlspecialchars($title) . ' <span style="color: #757575; font-size: 12px;">(' . count($extensions) . ')</span></h4>';
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 300px; overflow-y: auto;">';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px;">';

        foreach ($extensions as $extension) {
            $html .= '<div style="background: white; padding: 8px 12px; border-radius: 3px; border: 1px solid #e0e0e0;">';
            $html .= '<span style="font-family: monospace; font-size: 12px;">' . htmlspecialchars($extension) . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Рендер badge окружения
     */
    private function renderEnvironmentBadge(string $environment): string
    {
        $color = $this->getEnvironmentColor($environment);
        return '<span style="background: ' . $color . '; color: white; padding: 4px 12px; border-radius: 3px; font-weight: bold;">'
            . htmlspecialchars(ucfirst($environment)) . '</span>';
    }

    /**
     * Рендер badge для boolean значений
     */
    private function renderBoolBadge(bool $value): string
    {
        $color = $value ? '#4caf50' : '#f44336';
        $text = $value ? 'Enabled' : 'Disabled';

        return '<span style="background: ' . $color . '; color: white; padding: 4px 12px; border-radius: 3px; font-weight: bold;">'
            . $text . '</span>';
    }

    /**
     * Получить цвет для окружения
     */
    private function getEnvironmentColor(string $environment): string
    {
        return match ($environment) {
            'production' => '#f44336',
            'development' => '#4caf50',
            'testing' => '#ff9800',
            'staging' => '#2196f3',
            default => '#757575',
        };
    }
}

