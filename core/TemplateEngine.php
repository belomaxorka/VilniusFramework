<?php declare(strict_types=1);

namespace Core;

use Core\Logger;

class TemplateEngine
{
    private static ?TemplateEngine $instance = null;
    private string $templateDir;
    private string $cacheDir;
    private array $variables = [];
    private bool $cacheEnabled = true;
    private int $cacheLifetime = 3600; // 1 час
    private array $filters = [];
    private array $functions = []; // Зарегистрированные функции для использования в шаблонах
    private bool $logUndefinedVars = true; // Логировать неопределенные переменные в production
    private static array $undefinedVars = []; // Сбор неопределенных переменных
    private static array $renderedTemplates = []; // История рендеринга шаблонов для Debug Toolbar

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

        $templatePath = $this->templateDir . '/' . $template;

        if (!file_exists($templatePath)) {
            throw new \InvalidArgumentException("Template not found: {$template}");
        }

        // Сбрасываем блоки для нового рендеринга
        $this->blocks = [];
        $this->currentBlock = null;
        $this->parentTemplate = null;

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
     * Регистрирует пользовательский фильтр
     */
    public function addFilter(string $name, callable $callback): self
    {
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

        // Удаляем комментарии {# comment #}
        $content = preg_replace('/\{#.*?#\}/s', '', $content);

        // Экранируем PHP теги
        $content = str_replace(['<?php', '<?=', '?>'], ['&lt;?php', '&lt;?=', '?&gt;'], $content);

        // Обрабатываем условия {% if condition %} ПЕРЕД обработкой переменных
        $content = preg_replace_callback('/\{\%\s*if\s+([^%]+)\s*\%\}/', function ($matches) {
            return '<?php if (' . $this->processCondition($matches[1]) . '): ?>';
        }, $content);
        $content = preg_replace_callback('/\{\%\s*elseif\s+([^%]+)\s*\%\}/', function ($matches) {
            return '<?php elseif (' . $this->processCondition($matches[1]) . '): ?>';
        }, $content);
        $content = preg_replace('/\{\%\s*else\s*\%\}/', '<?php else: ?>', $content);
        $content = preg_replace('/\{\%\s*endif\s*\%\}/', '<?php endif; ?>', $content);

        // Обрабатываем циклы {% for item in items %} и {% for key, value in items %}
        $content = preg_replace_callback('/\{\%\s*for\s+(\w+)(?:\s*,\s*(\w+))?\s+in\s+([^%]+)\s*\%\}/', function ($matches) {
            return $this->compileForLoop($matches);
        }, $content);
        $content = preg_replace('/\{\%\s*endfor\s*\%\}/', '<?php endforeach; ?>', $content);

        // Обрабатываем циклы while {% while condition %}
        $content = preg_replace_callback('/\{\%\s*while\s+([^%]+)\s*\%\}/', function ($matches) {
            return '<?php while (' . $this->processCondition($matches[1]) . '): ?>';
        }, $content);
        $content = preg_replace('/\{\%\s*endwhile\s*\%\}/', '<?php endwhile; ?>', $content);

        // Обрабатываем переменные {{ variable }} с поддержкой фильтров
        $content = preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function ($matches) {
            // Разделяем на переменную и фильтры
            $parts = $this->splitByPipe($matches[1]);
            $variable = $this->processVariable(array_shift($parts));

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

            return '<?= htmlspecialchars((string)(' . $compiled . ' ?? \'\'), ENT_QUOTES, \'UTF-8\') ?>';
        }, $content);

        // Обрабатываем неэкранированные переменные {! variable !} с поддержкой фильтров
        $content = preg_replace_callback('/\{\!\s*([^}]+)\s*\!\}/', function ($matches) {
            // Разделяем на переменную и фильтры
            $parts = $this->splitByPipe($matches[1]);
            $variable = $this->processVariable(array_shift($parts));

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

            return '<?= ' . $compiled . ' ?? \'\' ?>';
        }, $content);

        // Обрабатываем включения {% include 'template.twig' %}
        $content = preg_replace_callback('/\{\%\s*include\s+[\'"]([^\'"]+)[\'"]\s*\%\}/', function ($matches) {
            return $this->processInclude($matches[1]);
        }, $content);

        // Удаляем теги блоков (если шаблон используется без extends)
        // Оставляем только содержимое блоков
        $content = preg_replace('/\{\%\s*block\s+\w+\s*\%\}/', '', $content);
        $content = preg_replace('/\{\%\s*endblock\s*\%\}/', '', $content);

        return $content;
    }

    /**
     * Выполняет скомпилированный шаблон
     */
    private function executeTemplate(string $compiledContent, array $variables, string $templateName = ''): string
    {
        extract($variables);

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
                        self::$undefinedVars[$varName] = [
                            'count' => 0,
                            'message' => $message,
                            'file' => $file,
                            'line' => $line
                        ];
                    }
                    self::$undefinedVars[$varName]['count']++;

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
     * Обрабатывает включения шаблонов
     */
    private function processInclude(string $template): string
    {
        $includePath = $this->templateDir . '/' . $template;

        if (!file_exists($includePath)) {
            Logger::warning("Include template not found: {$template}");
            return '';
        }

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
        // Удаляем комментарии {# comment #}
        $content = preg_replace('/\{#.*?#\}/s', '', $content);

        // Экранируем PHP теги
        $content = str_replace(['<?php', '<?=', '?>'], ['&lt;?php', '&lt;?=', '?&gt;'], $content);

        // Обрабатываем условия {% if condition %} ПЕРЕД обработкой переменных
        $content = preg_replace_callback('/\{\%\s*if\s+([^%]+)\s*\%\}/', function ($matches) {
            return '<?php if (' . $this->processCondition($matches[1]) . '): ?>';
        }, $content);
        $content = preg_replace_callback('/\{\%\s*elseif\s+([^%]+)\s*\%\}/', function ($matches) {
            return '<?php elseif (' . $this->processCondition($matches[1]) . '): ?>';
        }, $content);
        $content = preg_replace('/\{\%\s*else\s*\%\}/', '<?php else: ?>', $content);
        $content = preg_replace('/\{\%\s*endif\s*\%\}/', '<?php endif; ?>', $content);

        // Обрабатываем циклы {% for item in items %} и {% for key, value in items %}
        $content = preg_replace_callback('/\{\%\s*for\s+(\w+)(?:\s*,\s*(\w+))?\s+in\s+([^%]+)\s*\%\}/', function ($matches) {
            return $this->compileForLoop($matches);
        }, $content);
        $content = preg_replace('/\{\%\s*endfor\s*\%\}/', '<?php endforeach; ?>', $content);

        // Обрабатываем циклы while {% while condition %}
        $content = preg_replace_callback('/\{\%\s*while\s+([^%]+)\s*\%\}/', function ($matches) {
            return '<?php while (' . $this->processCondition($matches[1]) . '): ?>';
        }, $content);
        $content = preg_replace('/\{\%\s*endwhile\s*\%\}/', '<?php endwhile; ?>', $content);

        // Обрабатываем переменные {{ variable }} с поддержкой фильтров
        $content = preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function ($matches) {
            // Разделяем на переменную и фильтры
            $parts = $this->splitByPipe($matches[1]);
            $variable = $this->processVariable(array_shift($parts));

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

            return '<?= htmlspecialchars((string)(' . $compiled . ' ?? \'\'), ENT_QUOTES, \'UTF-8\') ?>';
        }, $content);

        // Обрабатываем неэкранированные переменные {! variable !} с поддержкой фильтров
        $content = preg_replace_callback('/\{\!\s*([^}]+)\s*\!\}/', function ($matches) {
            // Разделяем на переменную и фильтры
            $parts = $this->splitByPipe($matches[1]);
            $variable = $this->processVariable(array_shift($parts));

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

            return '<?= ' . $compiled . ' ?? \'\' ?>';
        }, $content);

        // Обрабатываем включения {% include 'template.twig' %}
        $content = preg_replace_callback('/\{\%\s*include\s+[\'"]([^\'"]+)[\'"]\s*\%\}/', function ($matches) {
            return $this->processInclude($matches[1]);
        }, $content);

        // Удаляем теги блоков (если шаблон используется без extends)
        // Оставляем только содержимое блоков
        $content = preg_replace('/\{\%\s*block\s+\w+\s*\%\}/', '', $content);
        $content = preg_replace('/\{\%\s*endblock\s*\%\}/', '', $content);

        return $content;
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

        // Защищаем логические операторы ДО обработки функций
        $logicalOperators = [];
        
        // Обрабатываем 'not' особым образом: 
        // если после 'not' идёт скобка - просто заменяем на !
        // если после 'not' идёт выражение без скобок - добавляем скобки до конца выражения или до and/or
        $condition = preg_replace_callback('/(?:^|\s+)(not)\s+(?!\()/i', function ($matches) use (&$logicalOperators) {
            // Сохраняем 'not ' с маркером, что нужно добавить скобки
            $logicalOperators[] = ['type' => 'not_no_parens', 'original' => $matches[0]];
            return '___LOGICAL_' . (count($logicalOperators) - 1) . '___';
        }, $condition);
        
        $condition = preg_replace_callback('/(?:^|\s+)(not)\s+(?=\()/i', function ($matches) use (&$logicalOperators) {
            // Сохраняем 'not ' как есть
            $logicalOperators[] = ['type' => 'not_with_parens', 'original' => $matches[0]];
            return '___LOGICAL_' . (count($logicalOperators) - 1) . '___';
        }, $condition);
        
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

        // Восстанавливаем и заменяем логические операторы
        foreach ($logicalOperators as $index => $operator) {
            $placeholder = '___LOGICAL_' . $index . '___';
            
            if ($operator['type'] === 'and') {
                $condition = str_replace($placeholder, ' && ', $condition);
            } elseif ($operator['type'] === 'or') {
                $condition = str_replace($placeholder, ' || ', $condition);
            } elseif ($operator['type'] === 'not_with_parens') {
                $condition = str_replace($placeholder, '!', $condition);
            } elseif ($operator['type'] === 'not_no_parens') {
                // Для 'not' без скобок нужно обернуть следующее выражение
                // Находим выражение после плейсхолдера до следующего логического оператора
                $pattern = '/' . preg_quote($placeholder, '/') . '([^&|]+?)(?=\s+(?:&&|\|\|)|$)/';
                $condition = preg_replace($pattern, '!($1)', $condition);
            }
        }

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

        // Восстанавливаем защищенные фрагменты
        foreach ($protected as $placeholder => $value) {
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
     * Компилирует for-цикл с поддержкой loop переменной
     */
    private function compileForLoop(array $matches): string
    {
        $iterable = $this->processVariable($matches[3]);
        
        // Генерируем уникальный ID для переменных цикла
        $loopId = uniqid('loop_');
        
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
    }
}
