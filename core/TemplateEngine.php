<?php declare(strict_types=1);

namespace Core;

use Core\Logger;

class TemplateEngine
{
    // Константы безопасности
    private const MAX_TEMPLATE_SIZE = 5 * 1024 * 1024; // 5MB - максимальный размер шаблона
    private const MAX_NESTING_LEVEL = 50; // Максимальная глубина вложенности блоков
    private const PROTECTED_FILTERS = ['escape', 'e', 'upper', 'lower', 'raw']; // Защищённые фильтры
    private const RESERVED_VARIABLES = ['__tpl', 'this', 'GLOBALS', '_SERVER', '_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_REQUEST', '_ENV']; // Зарезервированные переменные
    private const MAX_UNDEFINED_VARS = 1000; // Максимальное количество собранных undefined переменных
    private const MAX_RENDERED_TEMPLATES = 500; // Максимальное количество записей в истории рендеринга
    
    private static ?TemplateEngine $instance = null;
    private string $templateDir;
    private string $cacheDir;
    private array $variables = [];
    private bool $cacheEnabled = true;
    private int $cacheLifetime = 3600; // 1 час
    private array $filters = [];
    private array $functions = []; // Зарегистрированные функции для использования в шаблонах
    private bool $logUndefinedVars = true; // Логировать неопределенные переменные в production
    private bool $strictVariables = false; // Строгий режим - ошибка при undefined переменной
    private static array $undefinedVars = []; // Сбор неопределенных переменных
    private static array $renderedTemplates = []; // История рендеринга шаблонов для Debug Toolbar
    private int $currentNestingLevel = 0; // Текущая глубина вложенности
    private static int $loopCounter = 0; // Счётчик циклов для генерации уникальных ID

    // Поддержка блоков (extends/block)
    private array $blocks = []; // Определённые блоки
    private ?string $currentBlock = null; // Текущий блок
    private ?string $parentTemplate = null; // Родительский шаблон

    public function __construct(?string $templateDir = null, ?string $cacheDir = null)
    {
        $this->templateDir = $templateDir ?? RESOURCES_DIR . '/views';
        $this->cacheDir = $cacheDir ?? STORAGE_DIR . '/cache/templates';

        // Создаем директорию кэша если её нет
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        // Регистрируем встроенные фильтры
        $this->registerBuiltInFilters();
        
        // Регистрируем встроенные функции
        $this->registerBuiltInFunctions();
    }

    /**
     * Получает единственный экземпляр шаблонизатора (Singleton)
     *
     * @param string|null $templateDir Директория шаблонов (используется только при первом вызове)
     * @param string|null $cacheDir Директория кэша (используется только при первом вызове)
     */
    public static function getInstance(?string $templateDir = null, ?string $cacheDir = null): TemplateEngine
    {
        if (self::$instance === null) {
            self::$instance = new self($templateDir, $cacheDir);
        }
        return self::$instance;
    }

    /**
     * Устанавливает переменную для шаблона
     */
    public function assign(string $key, mixed $value): self
    {
        $this->variables[$key] = $value;
        return $this;
    }

    /**
     * Устанавливает несколько переменных сразу
     */
    public function assignMultiple(array $variables): self
    {
        $this->variables = array_merge($this->variables, $variables);
        return $this;
    }

    /**
     * Рендерит шаблон и возвращает результат
     */
    public function render(string $template, array $variables = []): string
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Проверяем безопасность пути
        $template = $this->sanitizeTemplatePath($template);
        
        $templatePath = $this->templateDir . '/' . $template;

        if (!file_exists($templatePath)) {
            throw new \InvalidArgumentException("Template not found: {$template}");
        }

        // Проверяем размер файла
        $this->validateTemplateSize($templatePath);

        // Сбрасываем блоки для нового рендеринга
        $this->blocks = [];
        $this->currentBlock = null;
        $this->parentTemplate = null;
        $this->currentNestingLevel = 0; // Сбрасываем счётчик вложенности

        // Объединяем переменные
        $allVariables = array_merge($this->variables, $variables);

        // Проверяем кэш
        $fromCache = false;
        if ($this->cacheEnabled) {
            $cachedContent = $this->getCachedContent($templatePath);
            if ($cachedContent !== null) {
                $fromCache = true;
                $output = $this->executeTemplate($cachedContent, $allVariables, $template);
            }
        }

        if (!$fromCache) {
            // Читаем и компилируем шаблон
            $templateContent = file_get_contents($templatePath);
            $compiledContent = $this->compileTemplate($templateContent, $template);

            // Сохраняем в кэш
            if ($this->cacheEnabled) {
                $this->saveCachedContent($templatePath, $compiledContent);
            }

            $output = $this->executeTemplate($compiledContent, $allVariables, $template);
        }

        // Сохраняем информацию о рендеринге для Debug Toolbar
        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        // Автоочистка истории при превышении лимита для защиты от утечки памяти
        if (count(self::$renderedTemplates) >= self::MAX_RENDERED_TEMPLATES) {
            // Удаляем первую половину массива (FIFO)
            self::$renderedTemplates = array_slice(self::$renderedTemplates, self::MAX_RENDERED_TEMPLATES / 2);
        }

        self::$renderedTemplates[] = [
            'template' => $template,
            'path' => $templatePath,
            'variables' => array_keys($allVariables),
            'variables_count' => count($allVariables),
            'time' => ($endTime - $startTime) * 1000, // в миллисекундах
            'memory' => $endMemory - $startMemory,
            'size' => strlen($output),
            'from_cache' => $fromCache,
            'timestamp' => microtime(true),
        ];

        return $output;
    }

    /**
     * Рендерит шаблон и выводит результат
     */
    public function display(string $template, array $variables = []): void
    {
        $output = $this->render($template, $variables);
        echo $output;
    }

    /**
     * Включает/выключает кэширование
     */
    public function setCacheEnabled(bool $enabled): self
    {
        $this->cacheEnabled = $enabled;
        return $this;
    }

    /**
     * Включает/выключает логирование неопределенных переменных
     */
    public function setLogUndefinedVars(bool $enabled): self
    {
        $this->logUndefinedVars = $enabled;
        return $this;
    }

    /**
     * Включает/выключает строгий режим для переменных
     * В строгом режиме выбрасывается исключение при обращении к неопределённой переменной
     */
    public function setStrictVariables(bool $enabled): self
    {
        $this->strictVariables = $enabled;
        return $this;
    }

    /**
     * Получить список неопределенных переменных
     */
    public static function getUndefinedVars(): array
    {
        return self::$undefinedVars;
    }

    /**
     * Очистить список неопределенных переменных
     */
    public static function clearUndefinedVars(): void
    {
        self::$undefinedVars = [];
    }

    /**
     * Получить список отрендеренных шаблонов
     */
    public static function getRenderedTemplates(): array
    {
        return self::$renderedTemplates;
    }

    /**
     * Очистить список отрендеренных шаблонов
     */
    public static function clearRenderedTemplates(): void
    {
        self::$renderedTemplates = [];
    }

    /**
     * Получить статистику по рендерингу
     */
    public static function getRenderStats(): array
    {
        $totalTime = 0;
        $totalMemory = 0;
        $totalSize = 0;
        $fromCache = 0;

        foreach (self::$renderedTemplates as $tpl) {
            $totalTime += $tpl['time'];
            $totalMemory += $tpl['memory'];
            $totalSize += $tpl['size'];
            if ($tpl['from_cache']) {
                $fromCache++;
            }
        }

        return [
            'total' => count(self::$renderedTemplates),
            'total_time' => $totalTime,
            'total_memory' => $totalMemory,
            'total_size' => $totalSize,
            'from_cache' => $fromCache,
            'compiled' => count(self::$renderedTemplates) - $fromCache,
        ];
    }

    /**
     * Устанавливает время жизни кэша в секундах
     */
    public function setCacheLifetime(int $lifetime): self
    {
        $this->cacheLifetime = $lifetime;
        return $this;
    }

    /**
     * Очищает кэш шаблонов
     */
    public function clearCache(): void
    {
        $files = glob($this->cacheDir . '/*.php');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Логирует использование неопределенной переменной
     */
    private function logUndefinedVariable(string $varName, string $message, string $file, int $line, array $availableVars): void
    {
        $logMessage = sprintf(
            "Template undefined variable: \$%s\nMessage: %s\nFile: %s:%d\nAvailable variables: %s",
            $varName,
            $message,
            basename($file),
            $line,
            implode(', ', array_keys($availableVars))
        );

        Logger::warning($logMessage);
    }

    /**
     * Обрабатывает неопределённую переменную
     * Используется в скомпилированных шаблонах
     */
    public function handleUndefinedVar(string $varName): mixed
    {
        // В строгом режиме выбрасываем исключение
        if ($this->strictVariables) {
            throw new \RuntimeException(
                "Undefined variable '{$varName}' in template."
            );
        }
        
        // В обычном режиме возвращаем пустую строку
        return '';
    }

    /**
     * Рендерит отладочную информацию
     */
    public function renderDebug(mixed $value, string $label = 'debug'): string
    {
        ob_start();
        echo '<div style="background: #f8f9fa; border: 2px solid #dee2e6; border-radius: 4px; padding: 16px; margin: 16px 0; font-family: monospace; font-size: 14px;">';
        echo '<strong style="color: #495057; display: block; margin-bottom: 8px;">🐛 Debug: ' . htmlspecialchars($label) . '</strong>';
        echo '<pre style="margin: 0; overflow-x: auto; background: #fff; padding: 12px; border-radius: 4px;">';
        
        if (is_array($value) || is_object($value)) {
            echo htmlspecialchars(print_r($value, true));
        } else {
            var_dump($value);
        }
        
        echo '</pre>';
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Применяет spaceless обработку к HTML
     * Удаляет пробелы между тегами, но сохраняет внутри <pre>, <textarea>, <script>, <style>
     */
    public function applySpaceless(string $html): string
    {
        // Защищаем теги где пробелы важны
        $preserveTags = ['pre', 'textarea', 'script', 'style'];
        $protected = [];
        
        foreach ($preserveTags as $tag) {
            $html = preg_replace_callback(
                '/<' . $tag . '(?:\s[^>]*)?>(.*?)<\/' . $tag . '>/si',
                function ($matches) use (&$protected) {
                    $placeholder = '___PRESERVE_' . count($protected) . '___';
                    $protected[$placeholder] = $matches[0];
                    return $placeholder;
                },
                $html
            );
        }
        
        // Удаляем пробелы между тегами
        $html = preg_replace('/>\s+/', '>', $html);
        $html = preg_replace('/\s+</', '<', $html);
        $html = trim($html);
        
        // Восстанавливаем защищённые теги
        foreach ($protected as $placeholder => $content) {
            $html = str_replace($placeholder, $content, $html);
        }
        
        return $html;
    }

    /**
     * Регистрирует пользовательский фильтр
     * 
     * @param string $name Имя фильтра
     * @param callable $callback Функция-обработчик
     * @param bool $allowOverride Разрешить перезапись защищённых фильтров (по умолчанию false)
     * @return self
     * @throws \RuntimeException Если попытка перезаписать защищённый фильтр
     */
    public function addFilter(string $name, callable $callback, bool $allowOverride = false): self
    {
        // Защищаем встроенные фильтры от перезаписи
        if (!$allowOverride && in_array($name, self::PROTECTED_FILTERS, true)) {
            throw new \RuntimeException("Cannot override protected filter: {$name}");
        }
        
        $this->filters[$name] = $callback;
        return $this;
    }

    /**
     * Проверяет существование фильтра
     */
    public function hasFilter(string $name): bool
    {
        return isset($this->filters[$name]);
    }

    /**
     * Применяет фильтр к значению
     */
    public function applyFilter(string $name, mixed $value, ...$args): mixed
    {
        if (!isset($this->filters[$name])) {
            throw new \InvalidArgumentException("Filter '{$name}' not found");
        }

        return call_user_func($this->filters[$name], $value, ...$args);
    }

    /**
     * Регистрирует функцию для использования в шаблонах
     */
    public function addFunction(string $name, callable $callback): self
    {
        $this->functions[$name] = $callback;
        return $this;
    }

    /**
     * Проверяет существование функции
     */
    public function hasFunction(string $name): bool
    {
        return isset($this->functions[$name]);
    }

    /**
     * Вызывает зарегистрированную функцию
     */
    public function callFunction(string $name, ...$args): mixed
    {
        if (!isset($this->functions[$name])) {
            throw new \InvalidArgumentException("Function '{$name}' not found");
        }

        return call_user_func($this->functions[$name], ...$args);
    }

    /**
     * Компилирует шаблон в PHP код
     */
    private function compileTemplate(string $content, string $templateName = ''): string
    {
        // Проверяем глубину вложенности для защиты от ReDoS и stack overflow
        $this->validateNestingLevel($content);
        
        // Проверяем наличие extends
        if (preg_match('/\{\%\s*extends\s+[\'"]([^\'"]+)[\'"]\s*\%\}/', $content, $extendsMatch)) {
            $parentTemplate = $extendsMatch[1];
            // Удаляем директиву extends из контента
            $content = preg_replace('/\{\%\s*extends\s+[\'"]([^\'"]+)[\'"]\s*\%\}/', '', $content);

            // Парсим блоки в текущем шаблоне
            $childBlocks = $this->parseBlocks($content);

            // Читаем родительский шаблон
            $parentPath = $this->templateDir . '/' . $parentTemplate;
            if (file_exists($parentPath)) {
                $parentContent = file_get_contents($parentPath);
                // Компилируем родительский шаблон с заменой блоков
                return $this->compileWithBlocks($parentContent, $childBlocks, $parentTemplate);
            }
        }

        return $this->compileTemplateContent($content);
    }

    /**
     * Выполняет скомпилированный шаблон
     */
    private function executeTemplate(string $compiledContent, array $variables, string $templateName = ''): string
    {
        // Фильтруем переменные - удаляем зарезервированные имена для защиты от перезаписи
        $filteredVariables = [];
        foreach ($variables as $key => $value) {
            // Проверяем, что имя переменной не зарезервировано
            if (!in_array($key, self::RESERVED_VARIABLES, true) && !str_starts_with($key, '__')) {
                $filteredVariables[$key] = $value;
            } else {
                // Логируем попытку использования зарезервированного имени
                Logger::warning("Attempt to use reserved variable name in template: {$key}");
            }
        }

        extract($filteredVariables, EXTR_SKIP); // EXTR_SKIP - не перезаписывать существующие переменные

        // Передаем ссылку на движок шаблонов для доступа к helper-методам
        $__tpl = $this;

        // Устанавливаем обработчик ошибок для отслеживания undefined variables
        $previousErrorHandler = set_error_handler(function ($severity, $message, $file, $line) use (&$variables) {
            // Проверяем если это undefined variable
            if ($severity === E_WARNING || $severity === E_NOTICE) {
                // Пытаемся извлечь имя переменной из сообщения
                if (preg_match('/Undefined variable\s+\$?(\w+)/i', $message, $matches) ||
                    preg_match('/Undefined array key\s+["\']?(\w+)["\']?/i', $message, $matches)) {
                    $varName = $matches[1];

                    // Логируем в production режиме
                    if ($this->logUndefinedVars && Environment::isProduction()) {
                        $this->logUndefinedVariable($varName, $message, $file, $line, $variables);
                    }

                    // Собираем для статистики
                    if (!isset(self::$undefinedVars[$varName])) {
                        // Автоочистка при превышении лимита для защиты от утечки памяти
                        if (count(self::$undefinedVars) >= self::MAX_UNDEFINED_VARS) {
                            // Удаляем первую половину массива (FIFO)
                            self::$undefinedVars = array_slice(self::$undefinedVars, self::MAX_UNDEFINED_VARS / 2, null, true);
                        }
                        
                        self::$undefinedVars[$varName] = [
                            'count' => 0,
                            'message' => $message,
                            'file' => $file,
                            'line' => $line
                        ];
                    }
                    self::$undefinedVars[$varName]['count']++;

                    // В строгом режиме выбрасываем исключение
                    if ($this->strictVariables) {
                        restore_error_handler();
                        throw new \RuntimeException(
                            "Undefined variable '\${$varName}' in template. Available variables: " . 
                            implode(', ', array_keys($variables))
                        );
                    }

                    // В development показываем ошибку через ErrorHandler
                    if (Environment::isDevelopment() && error_reporting() & $severity) {
                        // Вызываем наш ErrorHandler для красивого отображения
                        if (class_exists('\Core\ErrorHandler')) {
                            return \Core\ErrorHandler::handleError($severity, $message, $file, $line);
                        }
                        // Если ErrorHandler недоступен, используем стандартную обработку
                        return false;
                    }

                    // Подавляем ошибку в production
                    return true;
                }
            }

            // Для других ошибок вызываем ErrorHandler или стандартную обработку
            if (class_exists('\Core\ErrorHandler')) {
                return \Core\ErrorHandler::handleError($severity, $message, $file, $line);
            }
            return false;
        });

        ob_start();
        try {
            eval('?>' . $compiledContent);
            $output = ob_get_clean();

            // Восстанавливаем предыдущий обработчик ошибок
            restore_error_handler();

            return $output;
        } catch (\Throwable $e) {
            ob_end_clean(); // Очищаем буфер в случае ошибки
            restore_error_handler(); // Восстанавливаем обработчик
            throw $e;
        }
    }

    /**
     * Унифицированный доступ к свойствам массивов и объектов
     * Используется в скомпилированных шаблонах
     */
    private function getValue($data, $key)
    {
        if (is_array($data)) {
            return $data[$key] ?? null;
        } elseif (is_object($data)) {
            return $data->$key ?? null;
        }
        return null;
    }

    /**
     * Получает кэшированное содержимое
     */
    private function getCachedContent(string $templatePath): ?string
    {
        $cacheFile = $this->getCacheFilePath($templatePath);

        if (!file_exists($cacheFile)) {
            return null;
        }

        // Проверяем время модификации
        if (filemtime($cacheFile) < filemtime($templatePath)) {
            unlink($cacheFile);
            return null;
        }

        // Проверяем время жизни кэша
        if (time() - filemtime($cacheFile) > $this->cacheLifetime) {
            unlink($cacheFile);
            return null;
        }

        return file_get_contents($cacheFile);
    }

    /**
     * Сохраняет скомпилированный шаблон в кэш
     */
    private function saveCachedContent(string $templatePath, string $compiledContent): void
    {
        $cacheFile = $this->getCacheFilePath($templatePath);
        file_put_contents($cacheFile, $compiledContent);
    }

    /**
     * Получает путь к файлу кэша
     */
    private function getCacheFilePath(string $templatePath): string
    {
        $hash = md5($templatePath);
        return $this->cacheDir . '/' . $hash . '.php';
    }

    /**
     * Увеличивает счётчик вложенности и проверяет лимит
     * 
     * @param string $blockType Тип блока (for, if, while и т.д.)
     * @throws \RuntimeException Если превышен максимальный уровень вложенности
     */
    private function increaseNesting(string $blockType = 'block'): void
    {
        $this->currentNestingLevel++;
        
        if ($this->currentNestingLevel > self::MAX_NESTING_LEVEL) {
            throw new \RuntimeException(
                "Maximum nesting level exceeded ({$this->currentNestingLevel} > " . 
                self::MAX_NESTING_LEVEL . "). Check your template for deep nesting or infinite loops."
            );
        }
    }

    /**
     * Уменьшает счётчик вложенности
     */
    private function decreaseNesting(): void
    {
        if ($this->currentNestingLevel > 0) {
            $this->currentNestingLevel--;
        }
    }

    /**
     * Проверяет и очищает путь к шаблону для защиты от Path Traversal
     * 
     * @param string $path Путь к шаблону
     * @return string Очищенный путь
     * @throws \InvalidArgumentException Если путь небезопасен
     */
    private function sanitizeTemplatePath(string $path): string
    {
        // Запрещаем пустые пути
        if (empty($path)) {
            throw new \InvalidArgumentException("Template path cannot be empty");
        }

        // Запрещаем абсолютные пути
        if (str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[a-zA-Z]:/', $path)) {
            throw new \InvalidArgumentException("Absolute paths are not allowed in templates: {$path}");
        }

        // Запрещаем path traversal (..)
        if (str_contains($path, '..')) {
            throw new \InvalidArgumentException("Path traversal is not allowed in templates: {$path}");
        }

        // Запрещаем нулевые байты
        if (str_contains($path, "\0")) {
            throw new \InvalidArgumentException("Null bytes are not allowed in template paths");
        }

        // Нормализуем путь
        $path = str_replace('\\', '/', $path);
        
        // Получаем реальный путь и проверяем, что он находится внутри templateDir
        $fullPath = $this->templateDir . '/' . $path;
        $realTemplatePath = realpath($fullPath);
        $realTemplateDir = realpath($this->templateDir);

        // Если файл не существует, realpath вернёт false - это нормально для новых файлов
        // Но если существует, проверяем что он внутри templateDir
        if ($realTemplatePath !== false && $realTemplateDir !== false) {
            if (!str_starts_with($realTemplatePath, $realTemplateDir)) {
                throw new \InvalidArgumentException("Template path is outside of template directory: {$path}");
            }
        }

        return $path;
    }

    /**
     * Проверяет размер файла шаблона
     * 
     * @param string $filePath Путь к файлу
     * @throws \RuntimeException Если файл слишком большой
     */
    private function validateTemplateSize(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return; // Будет обработано в другом месте
        }

        $size = filesize($filePath);
        if ($size === false) {
            throw new \RuntimeException("Cannot determine template file size: {$filePath}");
        }

        if ($size > self::MAX_TEMPLATE_SIZE) {
            $maxSizeMB = round(self::MAX_TEMPLATE_SIZE / 1024 / 1024, 2);
            $actualSizeMB = round($size / 1024 / 1024, 2);
            throw new \RuntimeException(
                "Template file is too large: {$actualSizeMB}MB (max: {$maxSizeMB}MB)"
            );
        }
    }

    /**
     * Проверяет глубину вложенности в шаблоне
     * 
     * @param string $content Содержимое шаблона
     * @throws \RuntimeException Если превышена максимальная вложенность
     */
    private function validateNestingLevel(string $content): void
    {
        // Подсчитываем глубину вложенности блоков
        $maxDepth = 0;
        $currentDepth = 0;
        
        // Ищем все открывающие и закрывающие теги блоков
        preg_match_all('/\{\%\s*(for|if|while|block|spaceless|autoescape|verbatim)\s+.*?\%\}|\{\%\s*end(for|if|while|block|spaceless|autoescape|verbatim)\s*\%\}/s', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            if (str_starts_with($match[0], '{%') && !str_contains($match[0], 'end')) {
                $currentDepth++;
                $maxDepth = max($maxDepth, $currentDepth);
            } else {
                $currentDepth--;
            }
        }
        
        if ($maxDepth > self::MAX_NESTING_LEVEL) {
            throw new \RuntimeException(
                "Maximum template nesting level exceeded: {$maxDepth} (max: " . 
                self::MAX_NESTING_LEVEL . ")"
            );
        }
    }

    /**
     * Обрабатывает включения шаблонов
     */
    private function processInclude(string $template): string
    {
        // Проверяем безопасность пути
        $template = $this->sanitizeTemplatePath($template);
        
        $includePath = $this->templateDir . '/' . $template;

        if (!file_exists($includePath)) {
            Logger::warning("Include template not found: {$template}");
            return '';
        }

        // Проверяем размер файла
        $this->validateTemplateSize($includePath);

        $content = file_get_contents($includePath);
        return $this->compileTemplate($content);
    }

    /**
     * Парсит блоки из шаблона
     */
    private function parseBlocks(string $content): array
    {
        $blocks = [];

        // Находим все блоки в шаблоне
        if (preg_match_all('/\{\%\s*block\s+(\w+)\s*\%\}(.*?)\{\%\s*endblock\s*\%\}/s', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $blockName = $match[1];
                $blockContent = $match[2];
                $blocks[$blockName] = $blockContent;
            }
        }

        return $blocks;
    }

    /**
     * Компилирует родительский шаблон с заменой блоков
     */
    private function compileWithBlocks(string $parentContent, array $childBlocks, string $parentTemplate = ''): string
    {
        // Парсим блоки в родительском шаблоне
        $parentBlocks = $this->parseBlocks($parentContent);

        // Заменяем блоки родительского шаблона на блоки из дочернего
        foreach ($childBlocks as $blockName => $blockContent) {
            // Ищем блок в родительском шаблоне и заменяем его
            $pattern = '/\{\%\s*block\s+' . preg_quote($blockName, '/') . '\s*\%\}.*?\{\%\s*endblock\s*\%\}/s';
            $parentContent = preg_replace($pattern, $blockContent, $parentContent);
        }

        // Удаляем оставшиеся теги block (которые не были переопределены)
        $parentContent = preg_replace('/\{\%\s*block\s+\w+\s*\%\}/', '', $parentContent);
        $parentContent = preg_replace('/\{\%\s*endblock\s*\%\}/', '', $parentContent);

        // Проверяем, есть ли в родительском еще extends
        if (preg_match('/\{\%\s*extends\s+[\'"]([^\'"]+)[\'"]\s*\%\}/', $parentContent, $extendsMatch)) {
            $grandparentTemplate = $extendsMatch[1];
            $parentContent = preg_replace('/\{\%\s*extends\s+[\'"]([^\'"]+)[\'"]\s*\%\}/', '', $parentContent);

            // Объединяем блоки
            $mergedBlocks = $this->parseBlocks($parentContent);
            foreach ($childBlocks as $blockName => $blockContent) {
                $mergedBlocks[$blockName] = $blockContent;
            }

            // Читаем прародительский шаблон
            $grandparentPath = $this->templateDir . '/' . $grandparentTemplate;
            if (file_exists($grandparentPath)) {
                $grandparentContent = file_get_contents($grandparentPath);
                return $this->compileWithBlocks($grandparentContent, $mergedBlocks, $grandparentTemplate);
            }
        }

        // Компилируем финальный результат
        return $this->compileTemplateContent($parentContent);
    }

    /**
     * Компилирует содержимое шаблона (без обработки extends)
     */
    private function compileTemplateContent(string $content): string
    {
        // Защищаем {% verbatim %} блоки ПЕРВЫМ делом, до любой другой обработки
        $verbatimBlocks = [];
        $content = preg_replace_callback(
            '/\{\%\s*verbatim\s*\%\}(.*?)\{\%\s*endverbatim\s*\%\}/s',
            function ($matches) use (&$verbatimBlocks) {
                $placeholder = '___VERBATIM_' . count($verbatimBlocks) . '___';
                $verbatimBlocks[$placeholder] = $matches[1];
                return $placeholder;
            },
            $content
        );
        
        // Обрабатываем {% autoescape %} блоки
        // По умолчанию autoescape включен, но можно явно отключить через {% autoescape false %}
        $content = preg_replace_callback(
            '/\{\%\s*autoescape\s+(false|off|no)\s*\%\}(.*?)\{\%\s*endautoescape\s*\%\}/si',
            function ($matches) {
                // В этом блоке отключаем автоэкранирование - заменяем {{ }} на {! !}
                $innerContent = $matches[2];
                $innerContent = preg_replace('/\{\{(.*?)\}\}/', '{!$1!}', $innerContent);
                return $innerContent;
            },
            $content
        );
        
        // Удаляем теги autoescape для включенного режима (поведение по умолчанию)
        $content = preg_replace('/\{\%\s*autoescape(?:\s+(?:true|on|yes|html))?\s*\%\}/', '', $content);
        $content = preg_replace('/\{\%\s*endautoescape\s*\%\}/', '', $content);
        
        // Удаляем комментарии {# comment #}
        $content = preg_replace('/\{#.*?#\}/s', '', $content);

        // Экранируем PHP теги
        $content = str_replace(['<?php', '<?=', '?>'], ['&lt;?php', '&lt;?=', '?&gt;'], $content);

        // Обрабатываем {% set variable = value %}
        $content = preg_replace_callback('/\{\%\s*set\s+(\w+)\s*=\s*([^%]+)\s*\%\}/', function ($matches) {
            $varName = $matches[1];
            $value = trim($matches[2]);
            
            // Обрабатываем значение как выражение
            $processedValue = $this->processExpression($value);
            
            return '<?php $' . $varName . ' = ' . $processedValue . '; ?>';
        }, $content);

        // Защищаем {% if %}...{% else %}...{% endif %} блоки перед обработкой for...else
        $ifBlocks = [];
        $content = preg_replace_callback(
            '/\{\%\s*if\s+([^%]+)\s*\%\}(.*?)(?:\{\%\s*elseif\s+([^%]+)\s*\%\}(.*?))*(?:\{\%\s*else\s*\%\}(.*?))?\{\%\s*endif\s*\%\}/s',
            function ($matches) use (&$ifBlocks) {
                $placeholder = '___IFBLOCK_' . count($ifBlocks) . '___';
                $ifBlocks[$placeholder] = $matches[0];
                return $placeholder;
            },
            $content
        );

        // Обрабатываем циклы {% for %} с {% else %}
        $content = preg_replace_callback(
            '/\{\%\s*for\s+(\w+)(?:\s*,\s*(\w+))?\s+in\s+([^%]+)\s*\%\}(.*?)\{\%\s*else\s*\%\}(.*?)\{\%\s*endfor\s*\%\}/s',
            function ($matches) {
                $loopVars = [$matches[1], $matches[2] ?? null, $matches[3]];
                $forContent = $matches[4];
                $elseContent = $matches[5];
                return $this->compileForLoopWithElse($loopVars, $forContent, $elseContent);
            },
            $content
        );

        // Восстанавливаем if-блоки
        foreach ($ifBlocks as $placeholder => $block) {
            $content = str_replace($placeholder, $block, $content);
        }

        // Обрабатываем обычные циклы {% for item in items %} без else
        $content = preg_replace_callback('/\{\%\s*for\s+(\w+)(?:\s*,\s*(\w+))?\s+in\s+([^%]+)\s*\%\}/', function ($matches) {
            return $this->compileForLoop($matches);
        }, $content);
        $content = preg_replace('/\{\%\s*endfor\s*\%\}/', '<?php endforeach; ?>', $content);

        // Обрабатываем условия {% if condition %}
        $content = preg_replace_callback('/\{\%\s*if\s+([^%]+)\s*\%\}/', function ($matches) {
            return '<?php if (' . $this->processCondition($matches[1]) . '): ?>';
        }, $content);
        $content = preg_replace_callback('/\{\%\s*elseif\s+([^%]+)\s*\%\}/', function ($matches) {
            return '<?php elseif (' . $this->processCondition($matches[1]) . '): ?>';
        }, $content);
        $content = preg_replace('/\{\%\s*else\s*\%\}/', '<?php else: ?>', $content);
        $content = preg_replace('/\{\%\s*endif\s*\%\}/', '<?php endif; ?>', $content);

        // Обрабатываем циклы while {% while condition %}
        $content = preg_replace_callback('/\{\%\s*while\s+([^%]+)\s*\%\}/', function ($matches) {
            return '<?php while (' . $this->processCondition($matches[1]) . '): ?>';
        }, $content);
        $content = preg_replace('/\{\%\s*endwhile\s*\%\}/', '<?php endwhile; ?>', $content);

        // Обрабатываем {% spaceless %}
        $content = preg_replace_callback(
            '/\{\%\s*spaceless\s*\%\}(.*?)\{\%\s*endspaceless\s*\%\}/s',
            function ($matches) {
                $innerContent = $matches[1];
                // Удаляем пробелы между HTML-тегами, но сохраняем внутри <pre>, <textarea>, <script>, <style>
                return '<?php ob_start(); ?>' . $innerContent . '<?php echo $__tpl->applySpaceless(ob_get_clean()); ?>';
            },
            $content
        );

        // Обрабатываем {% debug %} и {% debug variable %}
        $content = preg_replace_callback(
            '/\{\%\s*debug(?:\s+([^%]+))?\s*\%\}/',
            function ($matches) {
                if (isset($matches[1]) && trim($matches[1])) {
                    // Debug конкретной переменной
                    $varName = trim($matches[1]);
                    $processedVar = $this->processVariable($varName);
                    return '<?php echo $__tpl->renderDebug(' . $processedVar . ', \'' . addslashes($varName) . '\'); ?>';
                } else {
                    // Debug всех переменных
                    return '<?php echo $__tpl->renderDebug(get_defined_vars(), \'all variables\'); ?>';
                }
            },
            $content
        );

        // Обрабатываем переменные {{ variable }} с поддержкой фильтров
        $content = preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function ($matches) {
            // Разделяем на переменную и фильтры
            $parts = $this->splitByPipe($matches[1]);
            $variableExpr = trim(array_shift($parts));
            $variable = $this->processVariable($variableExpr);

            // Применяем фильтры
            $compiled = $variable;
            foreach ($parts as $filter) {
                $filter = trim($filter);
                if (preg_match('/^(\w+)\s*\((.*)\)$/s', $filter, $filterMatches)) {
                    $filterName = $filterMatches[1];
                    $args = $filterMatches[2];
                    $compiled = '$__tpl->applyFilter(\'' . $filterName . '\', ' . $compiled . ($args ? ', ' . $args : '') . ')';
                } else {
                    $compiled = '$__tpl->applyFilter(\'' . $filter . '\', ' . $compiled . ')';
                }
            }

            // Для строгого режима добавляем проверку существования переменной
            // Проверяем, простая ли это переменная (вида $name)
            if (preg_match('/^\$(\w+)$/', $variable, $varMatch)) {
                $varName = $varMatch[1];
                $valueExpr = '(isset(' . $variable . ') ? ' . $compiled . ' : $__tpl->handleUndefinedVar(\'' . addslashes($variableExpr) . '\'))';
            } else {
                // Для сложных выражений используем ?? ''
                $valueExpr = '(' . $compiled . ' ?? \'\')';
            }

            return '<?= htmlspecialchars((string)(' . $valueExpr . '), ENT_QUOTES, \'UTF-8\') ?>';
        }, $content);

        // Обрабатываем неэкранированные переменные {! variable !} с поддержкой фильтров
        $content = preg_replace_callback('/\{\!\s*([^}]+)\s*\!\}/', function ($matches) {
            // Разделяем на переменную и фильтры
            $parts = $this->splitByPipe($matches[1]);
            $variableExpr = trim(array_shift($parts));
            $variable = $this->processVariable($variableExpr);

            // Применяем фильтры
            $compiled = $variable;
            foreach ($parts as $filter) {
                $filter = trim($filter);
                if (preg_match('/^(\w+)\s*\((.*)\)$/s', $filter, $filterMatches)) {
                    $filterName = $filterMatches[1];
                    $args = $filterMatches[2];
                    $compiled = '$__tpl->applyFilter(\'' . $filterName . '\', ' . $compiled . ($args ? ', ' . $args : '') . ')';
                } else {
                    $compiled = '$__tpl->applyFilter(\'' . $filter . '\', ' . $compiled . ')';
                }
            }

            // Для строгого режима добавляем проверку существования переменной
            if (preg_match('/^\$(\w+)$/', $variable, $varMatch)) {
                $varName = $varMatch[1];
                $valueExpr = '(isset(' . $variable . ') ? ' . $compiled . ' : $__tpl->handleUndefinedVar(\'' . addslashes($variableExpr) . '\'))';
            } else {
                $valueExpr = '(' . $compiled . ' ?? \'\')';
            }

            return '<?= ' . $valueExpr . ' ?>';
        }, $content);

        // Обрабатываем включения {% include 'template.twig' %}
        $content = preg_replace_callback('/\{\%\s*include\s+[\'"]([^\'"]+)[\'"]\s*\%\}/', function ($matches) {
            return $this->processInclude($matches[1]);
        }, $content);

        // Удаляем теги блоков (если шаблон используется без extends)
        // Оставляем только содержимое блоков
        $content = preg_replace('/\{\%\s*block\s+\w+\s*\%\}/', '', $content);
        $content = preg_replace('/\{\%\s*endblock\s*\%\}/', '', $content);

        // Восстанавливаем verbatim блоки В САМОМ КОНЦЕ (они не должны обрабатываться)
        foreach ($verbatimBlocks as $placeholder => $verbatimContent) {
            $content = str_replace($placeholder, $verbatimContent, $content);
        }

        return $content;
    }

    /**
     * Обрабатывает операторы starts with / ends with
     */
    private function processStartsEndsWith(string $condition, array &$startsEndsProtected): string
    {
        // Обрабатываем "starts with"
        $condition = preg_replace_callback('/(\S+)\s+starts\s+with\s+(\S+)/', function ($matches) use (&$startsEndsProtected) {
            $haystack = trim($matches[1]);
            $needle = trim($matches[2]);
            
            // Обрабатываем переменные (не трогаем плейсхолдеры ___STRING_N___)
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $haystack) && strpos($haystack, '___') !== 0) {
                $haystack = '$' . $haystack;
            }
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $needle) && strpos($needle, '___') !== 0) {
                $needle = '$' . $needle;
            }
            
            // Генерируем PHP код (str_starts_with для PHP 8+, substr для совместимости)
            $code = "(function_exists('str_starts_with') ? str_starts_with($haystack, $needle) : substr($haystack, 0, strlen($needle)) === $needle)";
            
            // Защищаем от дальнейшей обработки
            $placeholder = '___STARTS_' . count($startsEndsProtected) . '___';
            $startsEndsProtected[$placeholder] = $code;
            
            return $placeholder;
        }, $condition);
        
        // Обрабатываем "ends with"
        $condition = preg_replace_callback('/(\S+)\s+ends\s+with\s+(\S+)/', function ($matches) use (&$startsEndsProtected) {
            $haystack = trim($matches[1]);
            $needle = trim($matches[2]);
            
            // Обрабатываем переменные (не трогаем плейсхолдеры ___STRING_N___)
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $haystack) && strpos($haystack, '___') !== 0) {
                $haystack = '$' . $haystack;
            }
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $needle) && strpos($needle, '___') !== 0) {
                $needle = '$' . $needle;
            }
            
            // Генерируем PHP код (str_ends_with для PHP 8+, substr для совместимости)
            $code = "(function_exists('str_ends_with') ? str_ends_with($haystack, $needle) : substr($haystack, -strlen($needle)) === $needle)";
            
            // Защищаем от дальнейшей обработки
            $placeholder = '___ENDS_' . count($startsEndsProtected) . '___';
            $startsEndsProtected[$placeholder] = $code;
            
            return $placeholder;
        }, $condition);
        
        return $condition;
    }

    /**
     * Обрабатывает операторы in / not in
     */
    private function processInOperator(string $condition, array &$inProtected): string
    {
        // Обрабатываем "not in" - поддержка массивов с квадратными скобками
        $condition = preg_replace_callback('/([^\s]+)\s+not\s+in\s+(\[[^\]]+\]|[^\s]+)/', function ($matches) use (&$inProtected) {
            $needle = trim($matches[1]);
            $haystack = trim($matches[2]);
            
            // Обрабатываем переменные
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $needle)) {
                $needle = '$' . $needle;
            }
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $haystack)) {
                $haystack = '$' . $haystack;
            }
            
            // Генерируем PHP код для проверки
            // Для массивов используем in_array, для строк - strpos
            $inCode = "(is_array($haystack) ? !in_array($needle, $haystack, true) : (is_string($haystack) && strpos($haystack, $needle) === false))";
            
            // Защищаем от дальнейшей обработки
            $placeholder = '___IN_' . count($inProtected) . '___';
            $inProtected[$placeholder] = $inCode;
            
            return $placeholder;
        }, $condition);
        
        // Обрабатываем обычный "in" - поддержка массивов с квадратными скобками
        $condition = preg_replace_callback('/([^\s]+)\s+in\s+(\[[^\]]+\]|[^\s]+)/', function ($matches) use (&$inProtected) {
            $needle = trim($matches[1]);
            $haystack = trim($matches[2]);
            
            // Обрабатываем переменные
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $needle)) {
                $needle = '$' . $needle;
            }
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $haystack)) {
                $haystack = '$' . $haystack;
            }
            
            // Генерируем PHP код для проверки
            // Для массивов используем in_array, для строк - strpos
            $inCode = "(is_array($haystack) ? in_array($needle, $haystack, true) : (is_string($haystack) && strpos($haystack, $needle) !== false))";
            
            // Защищаем от дальнейшей обработки
            $placeholder = '___IN_' . count($inProtected) . '___';
            $inProtected[$placeholder] = $inCode;
            
            return $placeholder;
        }, $condition);
        
        return $condition;
    }

    /**
     * Обрабатывает тесты (is defined, is null, is empty, etc.)
     */
    private function processTests(string $condition, array &$testProtected): string
    {
        // Обрабатываем "is not" тесты (отрицание)
        $condition = preg_replace_callback('/(\w+)\s+is\s+not\s+(\w+)/', function ($matches) use (&$testProtected) {
            $variable = '$' . $matches[1];
            $test = strtolower($matches[2]);
            
            $compiledTest = $this->compileTest($variable, $test, true);
            
            // Защищаем от дальнейшей обработки
            $placeholder = '___TEST_' . count($testProtected) . '___';
            $testProtected[$placeholder] = $compiledTest;
            return $placeholder;
        }, $condition);
        
        // Обрабатываем обычные "is" тесты
        $condition = preg_replace_callback('/(\w+)\s+is\s+(\w+)/', function ($matches) use (&$testProtected) {
            $variable = '$' . $matches[1];
            $test = strtolower($matches[2]);
            
            $compiledTest = $this->compileTest($variable, $test, false);
            
            // Защищаем от дальнейшей обработки
            $placeholder = '___TEST_' . count($testProtected) . '___';
            $testProtected[$placeholder] = $compiledTest;
            return $placeholder;
        }, $condition);
        
        return $condition;
    }
    
    /**
     * Компилирует тест в PHP код
     */
    private function compileTest(string $variable, string $test, bool $negate): string
    {
        $result = '';
        
        switch ($test) {
            case 'defined':
                $result = "isset($variable)";
                break;
                
            case 'null':
                $result = "($variable === null)";
                break;
                
            case 'empty':
                $result = "empty($variable)";
                break;
                
            case 'even':
                $result = "($variable % 2 === 0)";
                break;
                
            case 'odd':
                $result = "($variable % 2 != 0)";
                break;
                
            case 'iterable':
                $result = "(is_array($variable) || $variable instanceof \\Traversable)";
                break;
                
            case 'string':
                $result = "is_string($variable)";
                break;
                
            case 'number':
            case 'numeric':
                $result = "is_numeric($variable)";
                break;
                
            case 'integer':
            case 'int':
                $result = "is_int($variable)";
                break;
                
            case 'float':
                $result = "is_float($variable)";
                break;
                
            case 'bool':
            case 'boolean':
                $result = "is_bool($variable)";
                break;
                
            case 'array':
                $result = "is_array($variable)";
                break;
                
            case 'object':
                $result = "is_object($variable)";
                break;
                
            default:
                // Неизвестный тест - оставляем как есть
                $result = "$variable is $test";
                break;
        }
        
        // Если нужно отрицание
        if ($negate) {
            $result = "!($result)";
        }
        
        return $result;
    }

    /**
     * Обрабатывает условия (для if, elseif, while)
     */
    private function processCondition(string $condition): string
    {
        $condition = trim($condition);

        // Защищаем строки в кавычках
        $strings = [];
        $condition = preg_replace_callback('/"([^"]*)"|\'([^\']*)\'/', function ($matches) use (&$strings) {
            $strings[] = $matches[0];
            return '___STRING_' . (count($strings) - 1) . '___';
        }, $condition);

        // Обрабатываем тесты (is defined, is null, is empty, etc.) ПЕРЕД обработкой логических операторов
        $testProtected = [];
        $condition = $this->processTests($condition, $testProtected);
        
        // Обрабатываем операторы in / not in
        $inProtected = [];
        $condition = $this->processInOperator($condition, $inProtected);
        
        // Обрабатываем операторы starts with / ends with
        $startsEndsProtected = [];
        $condition = $this->processStartsEndsWith($condition, $startsEndsProtected);

        // Защищаем логические операторы ДО обработки функций (но НЕ not - его обработаем позже)
        $logicalOperators = [];
        
        // Обрабатываем 'and' и 'or' между выражениями
        $condition = preg_replace_callback('/\s+(and|or)\s+/i', function ($matches) use (&$logicalOperators) {
            $logicalOperators[] = ['type' => strtolower(trim($matches[1])), 'original' => $matches[0]];
            return '___LOGICAL_' . (count($logicalOperators) - 1) . '___';
        }, $condition);

        // Обрабатываем вызовы функций ПЕРЕД обработкой свойств
        $functionProtected = [];
        $condition = $this->processFunctionCalls($condition, $strings, $functionProtected);

        // Обрабатываем комплексные выражения с точками и массивами
        $result = $this->processPropertyAccess($condition);
        $condition = $result['expression'];
        $protected = $result['protected'];

        // Проверяем, это простое условие (только переменная) или сложное выражение
        $trimmedCondition = trim($condition);
        $isSimpleVariable = preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $trimmedCondition);

        // Обрабатываем простые переменные
        $phpKeywords = ['true', 'false', 'null', 'and', 'or', 'not', 'isset', 'empty'];
        $condition = preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', function ($matches) use ($phpKeywords, $isSimpleVariable) {
            $var = $matches[1];
            // Пропускаем ключевые слова и защищенные фрагменты
            if (in_array(strtolower($var), $phpKeywords) || strpos($var, '___') === 0) {
                return $var;
            }

            // Только для простых условий (одна переменная) добавляем isset()
            // Для сложных выражений просто добавляем $
            if ($isSimpleVariable) {
                return '(isset($' . $var . ') && $' . $var . ')';
            } else {
                return '$' . $var;
            }
        }, $condition);

        // Восстанавливаем защищенные фрагменты функций ПОСЛЕ обработки переменных
        foreach ($functionProtected as $placeholder => $value) {
            $condition = str_replace($placeholder, $value, $condition);
        }

        // Восстанавливаем защищенные фрагменты ПЕРЕД обработкой логических операторов
        foreach ($protected as $placeholder => $value) {
            $condition = str_replace($placeholder, $value, $condition);
        }

        // Восстанавливаем и заменяем логические операторы ПОСЛЕ восстановления защищённых фрагментов
        foreach ($logicalOperators as $index => $operator) {
            $placeholder = '___LOGICAL_' . $index . '___';
            
            if ($operator['type'] === 'and') {
                $condition = str_replace($placeholder, ' && ', $condition);
            } elseif ($operator['type'] === 'or') {
                $condition = str_replace($placeholder, ' || ', $condition);
            }
        }
        
        // Обрабатываем 'not' В САМОМ КОНЦЕ, после всех восстановлений
        // Заменяем 'not выражение' на '!(выражение)'
        $condition = preg_replace_callback('/\bnot\s+(.+?)(?=\s+(?:&&|\|\||$)|$)/i', function ($matches) {
            return '!(' . trim($matches[1]) . ')';
        }, $condition);

        // Восстанавливаем тесты
        foreach ($testProtected as $placeholder => $value) {
            $condition = str_replace($placeholder, $value, $condition);
        }
        
        // Восстанавливаем операторы in
        foreach ($inProtected as $placeholder => $value) {
            $condition = str_replace($placeholder, $value, $condition);
        }
        
        // Восстанавливаем операторы starts with / ends with
        foreach ($startsEndsProtected as $placeholder => $value) {
            $condition = str_replace($placeholder, $value, $condition);
        }

        // Восстанавливаем строки
        foreach ($strings as $index => $string) {
            $condition = str_replace('___STRING_' . $index . '___', $string, $condition);
        }

        return $condition;
    }

    /**
     * Разделяет выражение по символу | с учетом строк и скобок
     */
    private function splitByPipe(string $expression): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = null;
        $length = strlen($expression);

        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];
            $prevChar = $i > 0 ? $expression[$i - 1] : '';

            // Проверяем открытие/закрытие строки
            if (($char === '"' || $char === "'") && $prevChar !== '\\') {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                    $stringChar = null;
                }
                $current .= $char;
                continue;
            }

            // Внутри строки просто добавляем символ
            if ($inString) {
                $current .= $char;
                continue;
            }

            // Отслеживаем вложенность скобок
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }

            // Разделяем по | только на верхнем уровне
            if ($char === '|' && $depth === 0) {
                $parts[] = $current;
                $current = '';
                continue;
            }

            $current .= $char;
        }

        // Добавляем последнюю часть
        if ($current !== '') {
            $parts[] = $current;
        }

        return $parts;
    }

    /**
     * Обрабатывает переменные в выражениях (для {{ }} и {! !})
     */
    private function processVariable(string $expression): string
    {
        $expression = trim($expression);

        // Если это уже PHP-переменная, возвращаем как есть
        if (strpos($expression, '$') === 0) {
            return $expression;
        }

        // Защищаем строки в кавычках
        $strings = [];
        $expression = preg_replace_callback('/"([^"]*)"|\'([^\']*)\'/', function ($matches) use (&$strings) {
            $strings[] = $matches[0];
            return '___STRING_' . (count($strings) - 1) . '___';
        }, $expression);
        
        // Обрабатываем диапазоны (1..10 => range(1, 10))
        $expression = preg_replace_callback('/(\d+)\.\.(\d+)/', function ($matches) {
            return 'range(' . $matches[1] . ', ' . $matches[2] . ')';
        }, $expression);

        // Обрабатываем вызовы функций ПЕРЕД обработкой свойств
        $functionProtected = [];
        $expression = $this->processFunctionCalls($expression, $strings, $functionProtected);

        // Обрабатываем комплексные выражения с точками и массивами
        $result = $this->processPropertyAccess($expression);
        $expression = $result['expression'];
        $protected = $result['protected'];

        // Обрабатываем простые переменные (которые еще не обработаны)
        // ВАЖНО: Пропускаем плейсхолдеры функций (___FUNC_N___)
        $expression = preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', function ($matches) {
            $var = $matches[1];
            // Пропускаем защищенные фрагменты и строки
            if (strpos($var, '___') === 0) {
                return $var;
            }
            return '$' . $var;
        }, $expression);

        // Восстанавливаем защищенные фрагменты функций ПОСЛЕ обработки переменных
        foreach ($functionProtected as $placeholder => $value) {
            $expression = str_replace($placeholder, $value, $expression);
        }

        // Восстанавливаем защищенные фрагменты
        foreach ($protected as $placeholder => $value) {
            $expression = str_replace($placeholder, $value, $expression);
        }

        // Восстанавливаем строки
        foreach ($strings as $index => $string) {
            $expression = str_replace('___STRING_' . $index . '___', $string, $expression);
        }

        return $expression;
    }

    /**
     * Обрабатывает вызовы функций в выражениях
     */
    private function processFunctionCalls(string $expression, array &$strings, array &$protected): string
    {
        // Проверяем, есть ли вообще вызовы функций
        if (!preg_match('/\b[a-zA-Z_][a-zA-Z0-9_]*\s*\(/', $expression)) {
            return $expression;
        }
        
        // Обрабатываем вызовы функций, начиная с самых вложенных
        // Используем итеративный подход с ограничением итераций для предотвращения бесконечного цикла
        $maxIterations = 10;
        $iteration = 0;
        
        while ($iteration < $maxIterations) {
            $oldExpression = $expression;
            $replacementCount = 0;
            
            // Ищем самые внутренние вызовы функций (без вложенных скобок в аргументах)
            // Также находим плейсхолдеры ___FUNC_N___
            $expression = preg_replace_callback(
                '/\b([a-zA-Z_][a-zA-Z0-9_]*|___FUNC_\d+___)\s*\(([^()]*)\)/',
                function ($matches) use (&$strings, &$replacementCount, &$protected) {
                    $fullMatch = $matches[0];
                    $funcName = $matches[1];
                    $argsString = $matches[2];
                    
                    // Пропускаем плейсхолдеры логических операторов
                    if (strpos($funcName, '___LOGICAL_') === 0 || strpos($funcName, '___STRING_') === 0 || 
                        strpos($funcName, '___PROTECTED_') === 0) {
                        return $fullMatch;
                    }
                    
                    // Если это плейсхолдер функции - восстанавливаем его
                    if (strpos($funcName, '___FUNC_') === 0) {
                        // Ищем соответствующий вызов в protected
                        foreach ($protected as $key => $value) {
                            if ($key === $funcName) {
                                // Заменяем плейсхолдер на реальный вызов функции
                                return $value;
                            }
                        }
                        return $fullMatch; // На всякий случай
                    }
                    
                    // Пропускаем уже обработанные вызовы или защищенные фрагменты
                    if ($funcName === 'callFunction' || strpos($fullMatch, '$__tpl') !== false || 
                        strpos($fullMatch, '->') !== false) {
                        return $fullMatch;
                    }
                    
                    // Пропускаем, если это уже начинается с $
                    if (isset($matches[0][0]) && $matches[0][0] === '$') {
                        return $fullMatch;
                    }
                    
                    // Обрабатываем аргументы
                    $processedArgs = $this->processFunctionArguments($argsString, $strings, $protected);
                    
                    $replacementCount++;
                    
                    // Генерируем вызов через callFunction и защищаем его
                    $functionCall = '$__tpl->callFunction(\'' . $funcName . '\'' . 
                                   ($processedArgs ? ', ' . $processedArgs : '') . ')';
                    
                    // Создаем плейсхолдер для защиты от дальнейшей обработки
                    $placeholder = '___FUNC_' . count($protected) . '___';
                    $protected[$placeholder] = $functionCall;
                    
                    return $placeholder;
                },
                $expression
            );
            
            // Если строка не изменилась или не было замен, выходим из цикла
            if ($expression === $oldExpression || $replacementCount === 0) {
                break;
            }
            
            $iteration++;
        }
        
        return $expression;
    }

    /**
     * Обрабатывает аргументы функций
     */
    private function processFunctionArguments(string $argsString, array &$strings, array &$functionProtected): string
    {
        $argsString = trim($argsString);
        
        if ($argsString === '') {
            return '';
        }
        
        // Разделяем аргументы по запятым (с учетом вложенности)
        $args = $this->splitArguments($argsString);
        $processedArgs = [];
        
        foreach ($args as $arg) {
            $arg = trim($arg);
            
            if ($arg === '') {
                continue;
            }
            
            // Если это placeholder строки, восстанавливаем её
            if (preg_match('/^___STRING_(\d+)___$/', $arg, $match)) {
                $processedArgs[] = $strings[(int)$match[1]];
            }
            // Если это placeholder функции, восстанавливаем его
            elseif (preg_match('/^___FUNC_\d+___$/', $arg)) {
                // Ищем соответствующий вызов в protected и восстанавливаем
                if (isset($functionProtected[$arg])) {
                    $processedArgs[] = $functionProtected[$arg];
                } else {
                    // На всякий случай оставляем как есть
                    $processedArgs[] = $arg;
                }
            }
            // Если это число
            elseif (is_numeric($arg)) {
                $processedArgs[] = $arg;
            }
            // Если это уже обработанный вызов функции или содержит $__tpl
            elseif (strpos($arg, '$__tpl') !== false) {
                $processedArgs[] = $arg;
            }
            // Иначе обрабатываем как переменную
            else {
                // Проверяем, не является ли это простой переменной
                if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $arg)) {
                    $processedArgs[] = '$' . $arg;
                } else {
                    // Сложное выражение - обрабатываем рекурсивно
                    $result = $this->processPropertyAccess($arg);
                    
                    if (!is_array($result) || !isset($result['expression'])) {
                        // Если что-то пошло не так, используем исходный аргумент
                        $processedArgs[] = $arg;
                        continue;
                    }
                    
                    $processed = $result['expression'];
                    $protected = $result['protected'] ?? [];
                    
                    // Если остались необработанные переменные, добавляем $
                    $processed = preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', function ($m) {
                        if (strpos($m[1], '___') === 0) {
                            return $m[1];
                        }
                        return '$' . $m[1];
                    }, $processed);
                    
                    // Восстанавливаем защищенные фрагменты
                    foreach ($protected as $placeholder => $value) {
                        $processed = str_replace($placeholder, $value, $processed);
                    }
                    
                    $processedArgs[] = $processed;
                }
            }
        }
        
        return implode(', ', $processedArgs);
    }

    /**
     * Разделяет строку аргументов по запятым с учетом вложенности скобок
     */
    private function splitArguments(string $argsString): array
    {
        $args = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = null;
        $length = strlen($argsString);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $argsString[$i];
            $prevChar = $i > 0 ? $argsString[$i - 1] : '';
            
            // Проверяем открытие/закрытие строки
            if (($char === '"' || $char === "'") && $prevChar !== '\\') {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                    $stringChar = null;
                }
                $current .= $char;
                continue;
            }
            
            // Внутри строки просто добавляем символ
            if ($inString) {
                $current .= $char;
                continue;
            }
            
            // Отслеживаем вложенность скобок
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }
            
            // Разделяем по запятой только на верхнем уровне
            if ($char === ',' && $depth === 0) {
                $args[] = $current;
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        // Добавляем последний аргумент
        if ($current !== '') {
            $args[] = $current;
        }
        
        return $args;
    }

    /**
     * Обрабатывает тернарный оператор (condition ? true_val : false_val)
     */
    private function processTernary(string $expression, array &$strings, array &$ternaryProtected): string
    {
        // Ищем тернарные операторы (condition ? true_value : false_value)
        // Используем нежадный поиск для правильной обработки вложенных тернарников
        $expression = preg_replace_callback(
            '/([^?:]+)\s*\?\s*([^:]+)\s*:\s*(.+?)(?=\s*[\),\]]|$)/s',
            function ($matches) use (&$strings, &$ternaryProtected) {
                $condition = trim($matches[1]);
                $trueValue = trim($matches[2]);
                $falseValue = trim($matches[3]);
                
                // Обрабатываем каждую часть
                $processedCondition = $this->processExpressionPart($condition, $strings);
                $processedTrue = $this->processExpressionPart($trueValue, $strings);
                $processedFalse = $this->processExpressionPart($falseValue, $strings);
                
                // Создаем PHP тернарник
                $ternary = '(' . $processedCondition . ' ? ' . $processedTrue . ' : ' . $processedFalse . ')';
                
                // Защищаем от дальнейшей обработки
                $placeholder = '___TERNARY_' . count($ternaryProtected) . '___';
                $ternaryProtected[$placeholder] = $ternary;
                
                return $placeholder;
            },
            $expression
        );
        
        return $expression;
    }
    
    /**
     * Обрабатывает часть выражения для тернарного оператора
     */
    private function processExpressionPart(string $part, array &$strings): string
    {
        $part = trim($part);
        
        // Если это placeholder строки
        if (preg_match('/^___STRING_(\d+)___$/', $part, $match)) {
            return $strings[(int)$match[1]];
        }
        
        // Если это число
        if (is_numeric($part)) {
            return $part;
        }
        
        // Если это boolean или null
        if (in_array(strtolower($part), ['true', 'false', 'null'])) {
            return strtolower($part);
        }
        
        // Если это простая переменная
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $part)) {
            return '$' . $part;
        }
        
        // Для сложных выражений (с точками, скобками и т.д.)
        // просто добавляем $ перед переменными
        $part = preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', function ($m) {
            if (in_array(strtolower($m[1]), ['true', 'false', 'null'])) {
                return $m[1];
            }
            return '$' . $m[1];
        }, $part);
        
        return $part;
    }

    /**
     * Обрабатывает выражения (для set, условий, вычислений)
     */
    private function processExpression(string $expression): string
    {
        $expression = trim($expression);
        
        // Защищаем строки в кавычках
        $strings = [];
        $expression = preg_replace_callback('/"([^"]*)"|\'([^\']*)\'/', function ($matches) use (&$strings) {
            $strings[] = $matches[0];
            return '___STRING_' . (count($strings) - 1) . '___';
        }, $expression);
        
        // Обрабатываем тернарный оператор (condition ? true_value : false_value)
        $ternaryProtected = [];
        $expression = $this->processTernary($expression, $strings, $ternaryProtected);
        
        // Обрабатываем оператор конкатенации ~ (как в Twig)
        $expression = str_replace('~', '.', $expression);
        
        // Защищаем массивы-литералы [1, 2, 3] ДО обработки доступа к элементам
        $arrayLiterals = [];
        $expression = preg_replace_callback('/\[([^\[\]]*)\]/', function ($matches) use (&$strings, &$arrayLiterals) {
            $content = trim($matches[1]);
            if (empty($content)) {
                return '[]';
            }
            
            // Разбиваем элементы по запятым
            $elements = $this->splitArguments($content);
            $processedElements = [];
            
            foreach ($elements as $element) {
                $element = trim($element);
                
                // Если это placeholder строки
                if (preg_match('/^___STRING_(\d+)___$/', $element, $match)) {
                    $processedElements[] = $strings[(int)$match[1]];
                }
                // Если это число
                elseif (is_numeric($element)) {
                    $processedElements[] = $element;
                }
                // Если это boolean или null
                elseif (in_array(strtolower($element), ['true', 'false', 'null'])) {
                    $processedElements[] = strtolower($element);
                }
                // Иначе это переменная
                else {
                    if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $element)) {
                        $processedElements[] = '$' . $element;
                    } else {
                        // Пропускаем сложные выражения - они будут обработаны позже
                        $processedElements[] = $element;
                    }
                }
            }
            
            $arrayCode = '[' . implode(', ', $processedElements) . ']';
            $placeholder = '___ARRAY_' . count($arrayLiterals) . '___';
            $arrayLiterals[$placeholder] = $arrayCode;
            return $placeholder;
        }, $expression);
        
        // Обрабатываем вызовы функций ПЕРЕД обработкой свойств
        $functionProtected = [];
        $expression = $this->processFunctionCalls($expression, $strings, $functionProtected);
        
        // Обрабатываем доступ к свойствам (user.name, items[0])
        $result = $this->processPropertyAccess($expression);
        $expression = $result['expression'];
        $protected = $result['protected'];
        
        // Обрабатываем простые переменные (которые еще не обработаны)
        $phpKeywords = ['true', 'false', 'null', 'and', 'or', 'not', 'isset', 'empty'];
        $expression = preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', function ($matches) use ($phpKeywords) {
            $var = $matches[1];
            // Пропускаем ключевые слова и защищенные фрагменты
            if (in_array(strtolower($var), $phpKeywords) || strpos($var, '___') === 0) {
                return $var;
            }
            return '$' . $var;
        }, $expression);
        
        // Восстанавливаем защищенные фрагменты функций
        foreach ($functionProtected as $placeholder => $value) {
            $expression = str_replace($placeholder, $value, $expression);
        }
        
        // Восстанавливаем защищенные фрагменты
        foreach ($protected as $placeholder => $value) {
            $expression = str_replace($placeholder, $value, $expression);
        }
        
        // Восстанавливаем массивы-литералы
        foreach ($arrayLiterals as $placeholder => $value) {
            $expression = str_replace($placeholder, $value, $expression);
        }
        
        // Восстанавливаем тернарные операторы
        foreach ($ternaryProtected as $placeholder => $value) {
            $expression = str_replace($placeholder, $value, $expression);
        }
        
        // Восстанавливаем строки
        foreach ($strings as $index => $string) {
            $expression = str_replace('___STRING_' . $index . '___', $string, $expression);
        }
        
        return $expression;
    }

    /**
     * Компилирует for-цикл с блоком else
     */
    private function compileForLoopWithElse(array $loopVars, string $forContent, string $elseContent): string
    {
        $var1 = $loopVars[0];
        $var2 = $loopVars[1];
        
        // Обрабатываем фильтры в выражении (например, items|batch(3))
        $iterableExpr = trim($loopVars[2]);
        $parts = $this->splitByPipe($iterableExpr);
        $iterable = $this->processVariable(array_shift($parts));
        
        // Применяем фильтры
        foreach ($parts as $filter) {
            $filter = trim($filter);
            if (preg_match('/^(\w+)\s*\((.*)\)$/s', $filter, $filterMatches)) {
                $filterName = $filterMatches[1];
                $args = $filterMatches[2];
                $iterable = '$__tpl->applyFilter(\'' . $filterName . '\', ' . $iterable . ($args ? ', ' . $args : '') . ')';
            } else {
                $iterable = '$__tpl->applyFilter(\'' . $filter . '\', ' . $iterable . ')';
            }
        }
        
        // Генерируем уникальный ID для переменных цикла (используем счётчик вместо uniqid для производительности)
        $loopId = 'loop_' . (++self::$loopCounter);
        
        $code = '<?php ';
        // Сохраняем родительский loop
        $code .= '$__loop_parent_' . $loopId . ' = isset($loop) ? $loop : null; ';
        // Инициализируем массив итераций
        $code .= '$__loop_items_' . $loopId . ' = ' . $iterable . '; ';
        // Получаем общее количество элементов
        $code .= '$__loop_length_' . $loopId . ' = is_array($__loop_items_' . $loopId . ') || $__loop_items_' . $loopId . ' instanceof \Countable ? count($__loop_items_' . $loopId . ') : 0; ';
        
        // Проверяем, есть ли элементы
        $code .= 'if ($__loop_length_' . $loopId . ' > 0): ';
        
        // Инициализируем счетчик
        $code .= '$__loop_index_' . $loopId . ' = 0; ';
        
        // Если указана вторая переменная - это деструктуризация (key, value)
        if (!empty($var2)) {
            $code .= 'foreach ($__loop_items_' . $loopId . ' as $' . $var1 . ' => $' . $var2 . '): ';
        } else {
            // Иначе обычный цикл (только value)
            $code .= 'foreach ($__loop_items_' . $loopId . ' as $' . $var1 . '): ';
        }
        
        // Создаем переменную loop
        $code .= '$loop = [';
        $code .= '"index" => $__loop_index_' . $loopId . ' + 1, ';
        $code .= '"index0" => $__loop_index_' . $loopId . ', ';
        $code .= '"revindex" => $__loop_length_' . $loopId . ' - $__loop_index_' . $loopId . ', ';
        $code .= '"revindex0" => $__loop_length_' . $loopId . ' - $__loop_index_' . $loopId . ' - 1, ';
        $code .= '"first" => $__loop_index_' . $loopId . ' === 0, ';
        $code .= '"last" => $__loop_index_' . $loopId . ' === $__loop_length_' . $loopId . ' - 1, ';
        $code .= '"length" => $__loop_length_' . $loopId . ', ';
        $code .= '"parent" => $__loop_parent_' . $loopId;
        $code .= ']; ';
        $code .= '$__loop_index_' . $loopId . '++; ';
        $code .= '?>';
        
        // Добавляем содержимое цикла
        $code .= $forContent;
        
        // Закрываем foreach
        $code .= '<?php endforeach; ?>';
        
        // Закрываем if и добавляем else
        $code .= '<?php else: ?>';
        
        // Добавляем else блок
        $code .= $elseContent;
        
        // Закрываем if
        $code .= '<?php endif; ?>';
        
        return $code;
    }

    /**
     * Компилирует for-цикл с поддержкой loop переменной
     */
    private function compileForLoop(array $matches): string
    {
        // Обрабатываем фильтры в выражении (например, items|batch(3))
        $iterableExpr = trim($matches[3]);
        $parts = $this->splitByPipe($iterableExpr);
        $iterable = $this->processVariable(array_shift($parts));
        
        // Применяем фильтры
        foreach ($parts as $filter) {
            $filter = trim($filter);
            if (preg_match('/^(\w+)\s*\((.*)\)$/s', $filter, $filterMatches)) {
                $filterName = $filterMatches[1];
                $args = $filterMatches[2];
                $iterable = '$__tpl->applyFilter(\'' . $filterName . '\', ' . $iterable . ($args ? ', ' . $args : '') . ')';
            } else {
                $iterable = '$__tpl->applyFilter(\'' . $filter . '\', ' . $iterable . ')';
            }
        }
        
        // Генерируем уникальный ID для переменных цикла (используем счётчик вместо uniqid для производительности)
        $loopId = 'loop_' . (++self::$loopCounter);
        
        $code = '<?php ';
        // Сохраняем родительский loop (для вложенных циклов)
        $code .= '$__loop_parent_' . $loopId . ' = isset($loop) ? $loop : null; ';
        // Инициализируем массив итераций
        $code .= '$__loop_items_' . $loopId . ' = ' . $iterable . '; ';
        // Получаем общее количество элементов
        $code .= '$__loop_length_' . $loopId . ' = is_array($__loop_items_' . $loopId . ') || $__loop_items_' . $loopId . ' instanceof \Countable ? count($__loop_items_' . $loopId . ') : 0; ';
        // Инициализируем счетчик
        $code .= '$__loop_index_' . $loopId . ' = 0; ';
        
        // Если указана вторая переменная - это деструктуризация (key, value)
        if (!empty($matches[2])) {
            $code .= 'foreach ($__loop_items_' . $loopId . ' as $' . $matches[1] . ' => $' . $matches[2] . '): ';
        } else {
            // Иначе обычный цикл (только value)
            $code .= 'foreach ($__loop_items_' . $loopId . ' as $' . $matches[1] . '): ';
        }
        
        // Создаем переменную loop с информацией о текущей итерации
        $code .= '$loop = [';
        $code .= '"index" => $__loop_index_' . $loopId . ' + 1, '; // 1-based index
        $code .= '"index0" => $__loop_index_' . $loopId . ', '; // 0-based index
        $code .= '"revindex" => $__loop_length_' . $loopId . ' - $__loop_index_' . $loopId . ', '; // обратный индекс (1-based)
        $code .= '"revindex0" => $__loop_length_' . $loopId . ' - $__loop_index_' . $loopId . ' - 1, '; // обратный индекс (0-based)
        $code .= '"first" => $__loop_index_' . $loopId . ' === 0, ';
        $code .= '"last" => $__loop_index_' . $loopId . ' === $__loop_length_' . $loopId . ' - 1, ';
        $code .= '"length" => $__loop_length_' . $loopId . ', ';
        $code .= '"parent" => $__loop_parent_' . $loopId;
        $code .= ']; ';
        $code .= '$__loop_index_' . $loopId . '++; ';
        $code .= '?>';
        
        return $code;
    }

    /**
     * Обрабатывает доступ к свойствам и элементам (унифицированно для массивов и объектов)
     * @return array{expression: string, protected: array<string, string>}
     */
    private function processPropertyAccess(string $expression): array
    {
        // Массив для хранения защищенных фрагментов
        $protected = [];

        // Регулярное выражение для поиска цепочек вида: variable.property[index].another
        // Ищем паттерн: начало_имени[индекс или .свойство]*
        $pattern = '/\b([a-zA-Z_][a-zA-Z0-9_]*)([.\[][\w\[\]."\']+)?/';

        $expression = preg_replace_callback($pattern, function ($matches) use (&$protected) {
            $baseName = $matches[1];
            $accessors = $matches[2] ?? '';

            if (empty($accessors)) {
                // Простая переменная без доступа
                return $matches[0];
            }

            // Начинаем с базовой переменной
            $result = '$' . $baseName;

            // Разбираем цепочку доступов
            $remaining = $accessors;
            while (!empty($remaining)) {
                // Проверяем доступ через точку: .property
                if (preg_match('/^\.([a-zA-Z_][a-zA-Z0-9_]*)/', $remaining, $propMatch)) {
                    $property = $propMatch[1];
                    $result = '$__tpl->getValue(' . $result . ', "' . $property . '")';
                    $remaining = substr($remaining, strlen($propMatch[0]));
                } // Проверяем доступ через квадратные скобки: [index] или ["key"]
                elseif (preg_match('/^\[([^\]]+)\]/', $remaining, $arrMatch)) {
                    $index = $arrMatch[1];
                    // Убираем кавычки если они есть, так как мы работаем с числами напрямую
                    $result = $result . '[' . $index . ']';
                    $remaining = substr($remaining, strlen($arrMatch[0]));
                } else {
                    break;
                }
            }

            // Защищаем результат от дальнейшей обработки
            $placeholder = '___PROTECTED_' . count($protected) . '___';
            $protected[$placeholder] = $result;
            return $placeholder;
        }, $expression);

        // Возвращаем выражение вместе с защищенными фрагментами
        return ['expression' => $expression, 'protected' => $protected];
    }

    /**
     * Регистрирует встроенные фильтры
     */
    private function registerBuiltInFilters(): void
    {
        // Фильтры для текста
        $this->addFilter('upper', fn($value) => mb_strtoupper((string)$value, 'UTF-8'));
        $this->addFilter('lower', fn($value) => mb_strtolower((string)$value, 'UTF-8'));
        $this->addFilter('capitalize', fn($value) => mb_convert_case((string)$value, MB_CASE_TITLE, 'UTF-8'));
        $this->addFilter('trim', fn($value) => trim((string)$value));

        // Фильтры для HTML
        $this->addFilter('escape', fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'));
        $this->addFilter('e', fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8')); // алиас
        $this->addFilter('striptags', fn($value) => strip_tags((string)$value));
        $this->addFilter('nl2br', fn($value) => nl2br((string)$value));

        // Фильтры для чисел
        $this->addFilter('abs', fn($value) => abs((float)$value));
        $this->addFilter('round', fn($value, $precision = 0) => round((float)$value, (int)$precision));
        $this->addFilter('number_format', function ($value, $decimals = 0, $decPoint = '.', $thousandsSep = ',') {
            return number_format((float)$value, (int)$decimals, $decPoint, $thousandsSep);
        });

        // Фильтры для массивов
        $this->addFilter('length', function ($value) {
            if (is_array($value) || $value instanceof \Countable) {
                return count($value);
            }
            return mb_strlen((string)$value, 'UTF-8');
        });
        $this->addFilter('count', fn($value) => is_array($value) || $value instanceof \Countable ? count($value) : 0);
        $this->addFilter('join', fn($value, $separator = '') => is_array($value) ? implode($separator, $value) : $value);
        $this->addFilter('first', fn($value) => is_array($value) && !empty($value) ? reset($value) : null);
        $this->addFilter('last', fn($value) => is_array($value) && !empty($value) ? end($value) : null);
        $this->addFilter('keys', fn($value) => is_array($value) ? array_keys($value) : []);
        $this->addFilter('values', fn($value) => is_array($value) ? array_values($value) : []);

        // Фильтры для строк
        $this->addFilter('truncate', function ($value, $length = 80, $suffix = '...') {
            $str = (string)$value;
            if (mb_strlen($str, 'UTF-8') <= $length) {
                return $str;
            }
            return mb_substr($str, 0, $length, 'UTF-8') . $suffix;
        });
        $this->addFilter('replace', fn($value, $search, $replace) => str_replace($search, $replace, (string)$value));
        $this->addFilter('split', fn($value, $delimiter = ',') => explode($delimiter, (string)$value));
        $this->addFilter('reverse', fn($value) => is_array($value) ? array_reverse($value) : strrev((string)$value));
        
        // Фильтр batch - разбивает массив на части (chunks)
        $this->addFilter('batch', function ($value, $size, $fill = null) {
            if (!is_array($value)) {
                return $value;
            }
            
            $size = max(1, (int)$size);
            $result = array_chunk($value, $size, true);
            
            // Если задан fill и последняя группа неполная - дополняем её
            if ($fill !== null && !empty($result)) {
                $lastIndex = count($result) - 1;
                $lastChunk = $result[$lastIndex];
                
                if (count($lastChunk) < $size) {
                    while (count($lastChunk) < $size) {
                        $lastChunk[] = $fill;
                    }
                    $result[$lastIndex] = $lastChunk;
                }
            }
            
            return $result;
        });
        
        // Фильтр slice - извлекает срез массива или строки
        $this->addFilter('slice', function ($value, $start, $length = null, $preserveKeys = false) {
            if (is_array($value)) {
                return array_slice($value, (int)$start, $length, $preserveKeys);
            }
            
            if (is_string($value)) {
                return mb_substr($value, (int)$start, $length, 'UTF-8');
            }
            
            return $value;
        });

        // Фильтры для форматирования
        $this->addFilter('date', function ($value, $format = 'Y-m-d H:i:s') {
            if ($value instanceof \DateTimeInterface) {
                return $value->format($format);
            }
            if (is_numeric($value)) {
                return date($format, (int)$value);
            }
            if (is_string($value)) {
                $timestamp = strtotime($value);
                return $timestamp ? date($format, $timestamp) : $value;
            }
            return $value;
        });

        // Фильтры для значений по умолчанию
        $this->addFilter('default', fn($value, $default = '') => empty($value) ? $default : $value);

        // Фильтры для JSON
        $this->addFilter('json', fn($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->addFilter('json_decode', fn($value) => json_decode((string)$value, true));

        // Фильтры для URL
        $this->addFilter('url_encode', fn($value) => urlencode((string)$value));
        $this->addFilter('url_decode', fn($value) => urldecode((string)$value));

        // Фильтры для отладки
        $this->addFilter('dump', function ($value) {
            ob_start();
            var_dump($value);
            return '<pre>' . htmlspecialchars(ob_get_clean(), ENT_QUOTES, 'UTF-8') . '</pre>';
        });
    }

    /**
     * Регистрирует встроенные функции
     */
    private function registerBuiltInFunctions(): void
    {
        // Регистрируем функцию vite (если она существует)
        if (function_exists('vite')) {
            $this->addFunction('vite', function (?string $entry = 'app') {
                return vite($entry);
            });
        }

        // Регистрируем функцию vite_asset (если она существует)
        if (function_exists('vite_asset')) {
            $this->addFunction('vite_asset', function (string $entry, string $type = 'js') {
                return vite_asset($entry, $type);
            });
        }

        // Регистрируем функцию asset (если она существует)
        if (function_exists('asset')) {
            $this->addFunction('asset', function (string $path) {
                return asset($path);
            });
        }

        // Регистрируем функцию url (если она существует)
        if (function_exists('url')) {
            $this->addFunction('url', function (string $path = '') {
                return url($path);
            });
        }

        // Регистрируем функцию route (если она существует)
        if (function_exists('route')) {
            $this->addFunction('route', function (string $name, array $params = []) {
                return route($name, $params);
            });
        }

        // Регистрируем функцию csrf_token (если она существует)
        if (function_exists('csrf_token')) {
            $this->addFunction('csrf_token', function () {
                return csrf_token();
            });
        }

        // Регистрируем функцию csrf_field (если она существует)
        if (function_exists('csrf_field')) {
            $this->addFunction('csrf_field', function () {
                return csrf_field();
            });
        }

        // Регистрируем функцию old (если она существует)
        if (function_exists('old')) {
            $this->addFunction('old', function (string $key, mixed $default = null) {
                return old($key, $default);
            });
        }

        // Регистрируем функцию config (если она существует)
        if (function_exists('config')) {
            $this->addFunction('config', function (string $key, mixed $default = null) {
                return config($key, $default);
            });
        }

        // Регистрируем функцию env (если она существует)
        if (function_exists('env')) {
            $this->addFunction('env', function (string $key, mixed $default = null) {
                return env($key, $default);
            });
        }

        // Регистрируем функцию trans (если она существует)
        if (function_exists('trans')) {
            $this->addFunction('trans', function (string $key, array $params = []) {
                return trans($key, $params);
            });
        }
        
        // Регистрируем функцию range для создания диапазонов
        $this->addFunction('range', function ($start, $end, $step = 1) {
            if ($step == 0) {
                throw new \InvalidArgumentException('Step cannot be zero');
            }
            
            $result = [];
            if ($step > 0) {
                for ($i = $start; $i <= $end; $i += $step) {
                    $result[] = $i;
                }
            } else {
                for ($i = $start; $i >= $end; $i += $step) {
                    $result[] = $i;
                }
            }
            
            return $result;
        });
    }
}
