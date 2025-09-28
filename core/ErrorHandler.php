<?php declare(strict_types=1);

namespace Core;

class ErrorHandler
{
    private static bool $registered = false;
    private static array $fatalErrors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING];

    /**
     * Зарегистрировать обработчики ошибок
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        // Устанавливаем уровень отчета об ошибках
        $config = Environment::getConfig();
        error_reporting($config['error_reporting']);
        ini_set('display_errors', (string)$config['display_errors']);
        ini_set('log_errors', (string)$config['log_errors']);

        // Регистрируем обработчики
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);

        self::$registered = true;
    }

    /**
     * Обработчик ошибок
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        // Не обрабатываем ошибки, которые не включены в error_reporting
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $error = [
            'type' => 'Error',
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        self::processError($error);

        // Возвращаем true, чтобы предотвратить стандартную обработку ошибки
        return true;
    }

    /**
     * Обработчик исключений
     */
    public static function handleException(\Throwable $exception): void
    {
        $exceptionClass = get_class($exception);
        
        $error = [
            'type' => $exceptionClass,
            'severity' => E_ERROR,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'backtrace' => $exception->getTrace(),
            'timestamp' => date('Y-m-d H:i:s'),
            'exception_class' => $exceptionClass,
        ];

        self::processError($error);
    }

    /**
     * Обработчик фатальных ошибок
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error && in_array($error['type'], self::$fatalErrors)) {
            $errorData = [
                'type' => 'Fatal Error',
                'severity' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'backtrace' => [],
                'timestamp' => date('Y-m-d H:i:s'),
            ];

            self::processError($errorData);
        }
    }

    /**
     * Обработать ошибку
     */
    private static function processError(array $error): void
    {
        // Логируем ошибку
        self::logError($error);

        // В режиме разработки показываем ошибку на странице
        if (Environment::isDevelopment()) {
            self::displayError($error);
        } else {
            // В продакшене показываем общую страницу ошибки
            self::displayProductionError();
        }
    }

    /**
     * Логировать ошибку
     */
    private static function logError(array $error): void
    {
        $logMessage = sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s",
            $error['timestamp'],
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line'],
            self::formatBacktrace($error['backtrace'])
        );

        Logger::error($logMessage);
    }

    /**
     * Показать ошибку в режиме разработки
     */
    private static function displayError(array $error): void
    {
        // Если уже отправлен заголовок, не можем изменить его
        if (headers_sent()) {
            echo "\n<!-- Error occurred after headers were sent -->\n";
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<strong>Error:</strong> " . htmlspecialchars($error['message']) . "<br>";
            echo "<strong>File:</strong> " . htmlspecialchars($error['file']) . ":" . $error['line'];
            echo "</div>";
            return;
        }

        // Устанавливаем HTTP статус код
        http_response_code(500);

        // Очищаем буфер вывода
        if (ob_get_level()) {
            ob_clean();
        }

        echo self::renderErrorPage($error);
        exit;
    }

    /**
     * Показать общую страницу ошибки в продакшене
     */
    private static function displayProductionError(): void
    {
        // Если уже отправлен заголовок, не можем изменить его
        if (headers_sent()) {
            return;
        }

        // Устанавливаем HTTP статус код
        http_response_code(500);

        // Очищаем буфер вывода
        if (ob_get_level()) {
            ob_clean();
        }

        echo self::renderProductionErrorPage();
        exit;
    }

    /**
     * Отрендерить страницу ошибки для разработки
     */
    private static function renderErrorPage(array $error): string
    {
        $severityName = self::getSeverityName($error['severity']);
        $backtrace = self::formatBacktraceForDisplay($error['backtrace']);
        
        // Получаем короткое имя класса для отображения
        $displayType = self::getDisplayType($error['type']);
        
        // Получаем полное имя класса исключения
        $exceptionClass = $error['exception_class'] ?? $error['type'];
        
        // Получаем текущее окружение
        $currentEnvironment = Environment::get();
        
        // Сокращаем путь к файлу для лучшего отображения
        $shortFilePath = self::shortenFilePath($error['file']);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - {$displayType}</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            margin: 0; 
            padding: 10px; 
            background: #f8f9fa; 
        }
        .error-container { 
            max-width: 1200px; 
            margin: 0 auto; 
        }
        .error-header { 
            background: #dc3545; 
            color: white; 
            padding: 15px; 
            border-radius: 8px 8px 0 0; 
        }
        .error-body { 
            background: white; 
            padding: 15px; 
            border-radius: 0 0 8px 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        .error-title { 
            margin: 0; 
            font-size: 20px; 
            word-break: break-word; 
        }
        .error-message { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 15px; 
            border-radius: 4px; 
            margin: 15px 0; 
            border-left: 4px solid #dc3545; 
            word-break: break-word; 
        }
        .error-details { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 4px; 
            margin: 15px 0; 
        }
        .error-details dt { 
            font-weight: bold; 
            color: #495057; 
            margin-top: 10px; 
        }
        .error-details dt:first-child { 
            margin-top: 0; 
        }
        .error-details dd { 
            margin: 5px 0 15px 0; 
            color: #6c757d; 
            font-family: monospace; 
            word-break: break-all; 
            overflow-wrap: break-word; 
            hyphens: auto; 
        }
        .backtrace { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 4px; 
            margin: 15px 0; 
        }
        .backtrace h3 { 
            margin-top: 0; 
            color: #495057; 
        }
        .backtrace pre { 
            background: #2d3748; 
            color: #e2e8f0; 
            padding: 15px; 
            border-radius: 4px; 
            overflow-x: auto; 
            font-size: 12px; 
            white-space: pre-wrap; 
            word-break: break-word; 
        }
        .file-path { 
            color: #007bff; 
            word-break: break-all; 
            overflow-wrap: break-word; 
        }
        .line-number { 
            color: #28a745; 
            font-weight: bold; 
        }
        
        /* Мобильные устройства */
        @media (max-width: 768px) {
            body { 
                padding: 5px; 
            }
            .error-header, .error-body { 
                padding: 10px; 
            }
            .error-title { 
                font-size: 18px; 
            }
            .error-details dd { 
                font-size: 14px; 
            }
            .backtrace pre { 
                font-size: 11px; 
                padding: 10px; 
            }
        }
        
        /* Очень маленькие экраны */
        @media (max-width: 480px) {
            .error-title { 
                font-size: 16px; 
            }
            .error-details dd { 
                font-size: 13px; 
            }
            .backtrace pre { 
                font-size: 10px; 
                padding: 8px; 
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <h1 class="error-title">{$displayType} - {$severityName}</h1>
        </div>
        <div class="error-body">
            <div class="error-message">
                <strong>Message:</strong> {$error['message']}
            </div>

            <dl class="error-details">
                <dt>File:</dt>
                <dd><span class="file-path" title="{$error['file']}">{$shortFilePath}</span></dd>

                <dt>Line:</dt>
                <dd><span class="line-number">{$error['line']}</span></dd>

                <dt>Time:</dt>
                <dd>{$error['timestamp']}</dd>

                <dt>Environment:</dt>
                <dd>{$currentEnvironment}</dd>
                
                <dt>Exception Class:</dt>
                <dd><span style="color: #6f42c1; font-family: monospace;">{$exceptionClass}</span></dd>
            </dl>

            <div class="backtrace">
                <h3>Stack Trace</h3>
                <pre>{$backtrace}</pre>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Отрендерить страницу ошибки для продакшена
     */
    private static function renderProductionErrorPage(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Server Error</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 0; background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .error-container { text-align: center; max-width: 500px; padding: 40px; }
        .error-icon { font-size: 64px; color: #dc3545; margin-bottom: 20px; }
        .error-title { font-size: 32px; color: #495057; margin-bottom: 15px; }
        .error-message { color: #6c757d; font-size: 18px; margin-bottom: 30px; }
        .error-actions { }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 0 10px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1 class="error-title">Internal Server Error</h1>
        <p class="error-message">Something went wrong on our end. We're working to fix it.</p>
        <div class="error-actions">
            <a href="/" class="btn">Go Home</a>
            <a href="javascript:history.back()" class="btn">Go Back</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Получить название уровня серьезности ошибки
     */
    private static function getSeverityName(int $severity): string
    {
        $severities = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
        ];

        // TODO: Remove after drop PHP 8.3 support
        if (version_compare(PHP_VERSION, '8.4.0', '<')) {
            $severities[E_STRICT] = 'Strict Notice';
        }

        return $severities[$severity] ?? 'Unknown Error';
    }

    /**
     * Форматировать backtrace для логирования
     */
    private static function formatBacktrace(array $backtrace): string
    {
        $formatted = '';
        foreach ($backtrace as $index => $trace) {
            $file = $trace['file'] ?? 'unknown';
            $line = $trace['line'] ?? 0;
            $function = $trace['function'] ?? 'unknown';
            $class = $trace['class'] ?? '';
            $type = $trace['type'] ?? '';

            $formatted .= "#{$index} {$file}({$line}): {$class}{$type}{$function}()\n";
        }

        return $formatted;
    }

    /**
     * Форматировать backtrace для отображения
     */
    private static function formatBacktraceForDisplay(array $backtrace): string
    {
        $formatted = '';
        foreach ($backtrace as $index => $trace) {
            $file = $trace['file'] ?? 'unknown';
            $line = $trace['line'] ?? 0;
            $function = $trace['function'] ?? 'unknown';
            $class = $trace['class'] ?? '';
            $type = $trace['type'] ?? '';

            $formatted .= "#{$index} {$file}({$line}): {$class}{$type}{$function}()\n";
        }

        return htmlspecialchars($formatted);
    }

    /**
     * Получить отображаемое имя типа ошибки
     */
    private static function getDisplayType(string $type): string
    {
        // Если это полное имя класса с namespace, берем только короткое имя
        if (str_contains($type, '\\')) {
            return basename(str_replace('\\', '/', $type));
        }
        
        return $type;
    }

    /**
     * Сократить путь к файлу для лучшего отображения
     */
    private static function shortenFilePath(string $filePath): string
    {
        // Если путь короткий, возвращаем как есть
        if (strlen($filePath) <= 60) {
            return $filePath;
        }

        // Разбиваем путь на части
        $parts = explode(DIRECTORY_SEPARATOR, $filePath);
        
        // Если частей мало, просто обрезаем
        if (count($parts) <= 3) {
            return '...' . substr($filePath, -50);
        }

        // Берем последние 2 части (папка + файл)
        $lastParts = array_slice($parts, -2);
        
        // Добавляем многоточие в начале
        return '...' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $lastParts);
    }
}
