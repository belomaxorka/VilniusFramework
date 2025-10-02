<?php declare(strict_types=1);

namespace Core;

/**
 * Рендерит страницы ошибок
 */
class ErrorRenderer
{
    /**
     * Рендерить страницу ошибки
     *
     * @param int $code HTTP статус код
     * @param string $message Сообщение об ошибке
     * @return string HTML контент
     */
    public static function render(int $code, string $message): string
    {
        // Устанавливаем HTTP статус код
        if (!headers_sent()) {
            http_response_code($code);
        }

        // Проверяем, есть ли пользовательский шаблон
        $customTemplate = self::getCustomTemplate($code);
        if ($customTemplate !== null) {
            return $customTemplate;
        }

        // Используем стандартный шаблон
        return self::renderDefaultTemplate($code, $message);
    }

    /**
     * Проверить наличие пользовательского шаблона
     */
    private static function getCustomTemplate(int $code): ?string
    {
        $templatePath = RESOURCES_DIR . '/views/errors/' . $code . '.twig';

        if (is_file($templatePath)) {
            try {
                $engine = TemplateEngine::getInstance();
                $engine->setCacheEnabled(false);
                $html = $engine->render('errors/' . $code . '.twig');
                
                // Внедряем Debug Toolbar в пользовательский шаблон
                return self::injectDebugToolbar($html);
            } catch (\Throwable $e) {
                // Если ошибка при рендере шаблона, используем дефолтный
                return null;
            }
        }

        return null;
    }

    /**
     * Рендерить стандартный шаблон ошибки
     */
    private static function renderDefaultTemplate(int $code, string $message): string
    {
        // Для JSON запросов
        if (self::isJsonRequest()) {
            return self::renderJsonError($code, $message);
        }

        // Определяем заголовок по коду
        $title = self::getErrorTitle($code);

        // Формируем HTML с debug toolbar
        return self::renderSimpleHtml($code, $title, $message);
    }

    /**
     * Рендерить простой HTML с серым фоном
     */
    private static function renderSimpleHtml(int $code, string $title, string $message): string
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$code} - {$title}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #9e9e9e;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        .error-container {
            text-align: center;
            max-width: 600px;
            padding: 40px 20px;
        }
        .error-code {
            font-size: 120px;
            font-weight: 700;
            line-height: 1;
            color: #212121;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .error-title {
            font-size: 32px;
            font-weight: 600;
            color: #424242;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">{$code}</div>
        <div class="error-title">{$code} | {$message}</div>
    </div>
</body>
</html>
HTML;

        // Добавляем Debug Toolbar если нужно
        return self::injectDebugToolbar($html);
    }

    /**
     * Внедрить Debug Toolbar в HTML
     */
    private static function injectDebugToolbar(string $html): string
    {
        // Только в debug режиме
        if (!Environment::isDebug()) {
            return $html;
        }

        // Проверяем наличие </body>
        if (stripos($html, '</body>') === false) {
            return $html;
        }

        // Рендерим Debug Toolbar
        try {
            if (class_exists('Core\DebugToolbar')) {
                $toolbar = \Core\DebugToolbar::render();
                if (!empty($toolbar)) {
                    $html = str_ireplace('</body>', $toolbar . '</body>', $html);
                }
            }
        } catch (\Throwable $e) {
            // Не ломаем страницу если toolbar не работает
        }

        return $html;
    }

    /**
     * Рендерить JSON ошибку
     */
    private static function renderJsonError(int $code, string $message): string
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        $data = [
            'error' => self::getErrorTitle($code),
            'message' => $message,
            'code' => $code,
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Получить заголовок ошибки по коду
     */
    private static function getErrorTitle(int $code): string
    {
        return match ($code) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            408 => 'Request Timeout',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            default => 'Error',
        };
    }

    /**
     * Проверить, является ли запрос JSON
     */
    private static function isJsonRequest(): bool
    {
        // Если Content-Type: application/json - это JSON запрос
        if (Http::isJson()) {
            return true;
        }

        // Если явно запрошен application/json (не */*)
        $types = Http::getAcceptedContentTypes();
        if (in_array('application/json', $types)) {
            return true;
        }

        // Если AJAX запрос и есть JSON в Accept (даже с */*)
        if (Http::isAjax() && Http::acceptsJson()) {
            return true;
        }

        return false;
    }
}

