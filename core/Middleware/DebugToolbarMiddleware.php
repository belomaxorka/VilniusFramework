<?php declare(strict_types=1);

namespace Core\Middleware;

use Core\Environment;
use Core\Response;

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
        // Если не в debug режиме, просто пропускаем дальше
        if (!Environment::isDebug()) {
            return $next();
        }

        // Начинаем перехват вывода
        ob_start();

        // Выполняем следующий обработчик
        $result = $next();

        // Получаем весь вывод из буфера
        $output = ob_get_clean();

        // Если нет контента, возвращаем результат как есть
        if (empty($output)) {
            return $result;
        }

        // Внедряем Debug Toolbar, если это HTML
        $output = $this->injectDebugToolbar($output);

        // Выводим модифицированный результат
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

        // Проверяем Content-Type
        if (!$this->isHtmlResponse()) {
            return $content;
        }

        // Получаем HTML Debug Toolbar
        $toolbar = $this->renderDebugToolbar();

        if (empty($toolbar)) {
            return $content;
        }

        // Внедряем перед закрывающим </body>
        return str_ireplace('</body>', $toolbar . '</body>', $content);
    }

    /**
     * Проверить, является ли ответ HTML
     */
    protected function isHtmlResponse(): bool
    {
        // Если заголовки уже отправлены, не можем проверить
        if (headers_sent()) {
            return true;
        }

        // Получаем список отправленных заголовков
        $headers = headers_list();
        
        foreach ($headers as $header) {
            // Ищем заголовок Content-Type
            if (stripos($header, 'Content-Type:') === 0) {
                // Проверяем, что это text/html
                return stripos($header, 'text/html') !== false;
            }
        }

        // Если Content-Type не установлен, считаем что это HTML
        // (по умолчанию PHP отправляет text/html)
        return true;
    }

    /**
     * Отрендерить Debug Toolbar
     */
    protected function renderDebugToolbar(): string
    {
        if (!class_exists('\Core\DebugToolbar')) {
            return '';
        }

        try {
            return \Core\DebugToolbar::render();
        } catch (\Throwable $e) {
            // Если произошла ошибка при рендеринге toolbar, не ломаем страницу
            if (Environment::isDevelopment()) {
                return '<!-- Debug Toolbar Error: ' . htmlspecialchars($e->getMessage()) . ' -->';
            }
            return '';
        }
    }
}

