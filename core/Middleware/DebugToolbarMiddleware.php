<?php declare(strict_types=1);

namespace Core\Middleware;

use Core\Environment;

/**
 * Debug Toolbar Middleware
 * 
 * Автоматически внедряет Debug Toolbar в HTML ответы
 */
class DebugToolbarMiddleware implements MiddlewareInterface
{
    /**
     * Обработать запрос
     */
    public function handle(callable $next): mixed
    {
        // Выполняем следующий обработчик
        $result = $next();

        // Если не в debug режиме, ничего не делаем
        if (!Environment::isDebug()) {
            return $result;
        }

        // Перехватываем output buffering
        if (ob_get_level() === 0) {
            ob_start();
        }

        // Если есть результат, выводим его
        if ($result !== null) {
            echo $result;
        }

        // Получаем буфер
        $output = ob_get_clean();

        // Если нет контента, ничего не делаем
        if (empty($output)) {
            return $result;
        }

        // Внедряем Debug Toolbar, если это HTML
        $output = $this->injectDebugToolbar($output);

        // Выводим результат
        echo $output;

        return $result;
    }

    /**
     * Внедрить Debug Toolbar в HTML
     */
    protected function injectDebugToolbar(string $content): string
    {
        // Проверяем, что это HTML с закрывающим body тегом
        if (stripos($content, '</body>') === false) {
            return $content;
        }

        // Проверяем Content-Type если заголовки еще не отправлены
        if (!headers_sent()) {
            $headers = headers_list();
            $isHtml = false;

            foreach ($headers as $header) {
                if (stripos($header, 'Content-Type:') === 0) {
                    $isHtml = stripos($header, 'text/html') !== false;
                    break;
                }
            }

            // Если Content-Type не установлен, считаем что это HTML
            // (по умолчанию PHP отправляет text/html)
            if (!isset($isHtml)) {
                $isHtml = true;
            }

            if (!$isHtml) {
                return $content;
            }
        }

        // Получаем HTML Debug Toolbar
        $toolbar = '';
        
        if (function_exists('render_debug_toolbar')) {
            $toolbar = render_debug_toolbar();
        }

        if (empty($toolbar)) {
            return $content;
        }

        // Внедряем перед закрывающим </body>
        return str_ireplace('</body>', $toolbar . '</body>', $content);
    }
}

