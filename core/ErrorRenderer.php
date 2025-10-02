<?php declare(strict_types=1);

namespace Core;

use Core\Http\HttpStatus;

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
     * Рендерить простой текстовый вывод
     */
    private static function renderSimpleHtml(int $code, string $title, string $message): string
    {
        return "{$code} | {$message}";
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
        return HttpStatus::getText($code);
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

