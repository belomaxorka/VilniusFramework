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

    public function __construct(?string $templateDir = null, ?string $cacheDir = null)
    {
        $root = defined('ROOT') ? ROOT : dirname(__DIR__, 2);
        $this->templateDir = $templateDir ?? $root . '/resources/views';
        $this->cacheDir = $cacheDir ?? $root . '/storage/cache/templates';

        // Создаем директорию кэша если её нет
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        // Регистрируем встроенные фильтры
        $this->registerBuiltInFilters();
    }

    /**
     * Получает единственный экземпляр шаблонизатора (Singleton)
     */
    public static function getInstance(): TemplateEngine
    {
        if (self::$instance === null) {
            self::$instance = new self();
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
        $templatePath = $this->templateDir . '/' . $template;

        if (!file_exists($templatePath)) {
            throw new \InvalidArgumentException("Template not found: {$template}");
        }

        // Объединяем переменные
        $allVariables = array_merge($this->variables, $variables);

        // Проверяем кэш
        if ($this->cacheEnabled) {
            $cachedContent = $this->getCachedContent($templatePath);
            if ($cachedContent !== null) {
                return $this->executeTemplate($cachedContent, $allVariables);
            }
        }

        // Читаем и компилируем шаблон
        $templateContent = file_get_contents($templatePath);
        $compiledContent = $this->compileTemplate($templateContent);

        // Сохраняем в кэш
        if ($this->cacheEnabled) {
            $this->saveCachedContent($templatePath, $compiledContent);
        }

        return $this->executeTemplate($compiledContent, $allVariables);
    }

    /**
     * Рендерит шаблон и выводит результат
     */
    public function display(string $template, array $variables = []): void
    {
        $output = $this->render($template, $variables);
        
        // Автоматически добавляем Debug Toolbar в development режиме
        if (class_exists('\Core\Environment') && \Core\Environment::isDebug()) {
            // Если это HTML с закрывающим </body>, вставляем toolbar перед ним
            if (stripos($output, '</body>') !== false) {
                $toolbar = '';
                if (function_exists('render_debug_toolbar')) {
                    $toolbar = render_debug_toolbar();
                }
                $output = str_ireplace('</body>', $toolbar . '</body>', $output);
            }
        }
        
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
     * Компилирует шаблон в PHP код
     */
    private function compileTemplate(string $content): string
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

        // Обрабатываем циклы {% for item in items %}
        $content = preg_replace_callback('/\{\%\s*for\s+(\w+)\s+in\s+([^%]+)\s*\%\}/', function ($matches) {
            return '<?php foreach (' . $this->processVariable($matches[2]) . ' as $' . $matches[1] . '): ?>';
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

        // Обрабатываем включения {% include 'template.tpl' %}
        $content = preg_replace_callback('/\{\%\s*include\s+[\'"]([^\'"]+)[\'"]\s*\%\}/', function ($matches) {
            return $this->processInclude($matches[1]);
        }, $content);

        // Обрабатываем расширения {% extends 'base.tpl' %}
        $content = preg_replace_callback('/\{\%\s*extends\s+[\'"]([^\'"]+)[\'"]\s*\%\}/', function ($matches) {
            return $this->processExtends($matches[1]);
        }, $content);

        // Обрабатываем блоки {% block name %}...{% endblock %}
        $content = preg_replace('/\{\%\s*block\s+(\w+)\s*\%\}/', '<?php $this->startBlock(\'\\1\'); ?>', $content);
        $content = preg_replace('/\{\%\s*endblock\s*\%\}/', '<?php $this->endBlock(); ?>', $content);

        return $content;
    }

    /**
     * Выполняет скомпилированный шаблон
     */
    private function executeTemplate(string $compiledContent, array $variables): string
    {
        extract($variables);

        // Передаем ссылку на движок шаблонов для доступа к helper-методам
        $__tpl = $this;

        ob_start();
        try {
            eval('?>' . $compiledContent);
            return ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean(); // Очищаем буфер в случае ошибки
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
     * Обрабатывает расширения шаблонов
     */
    private function processExtends(string $template): string
    {
        $extendsPath = $this->templateDir . '/' . $template;

        if (!file_exists($extendsPath)) {
            Logger::warning("Extends template not found: {$template}");
            return '';
        }

        $content = file_get_contents($extendsPath);
        return $this->compileTemplate($content);
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

        // Обрабатываем комплексные выражения с точками и массивами
        $result = $this->processPropertyAccess($condition);
        $condition = $result['expression'];
        $protected = $result['protected'];

        // Заменяем логические операторы
        $condition = str_replace(' and ', ' && ', $condition);
        $condition = str_replace(' or ', ' || ', $condition);
        $condition = str_replace(' not ', ' ! ', $condition);

        // Обрабатываем простые переменные (которые еще не обработаны)
        $phpKeywords = ['true', 'false', 'null', 'and', 'or', 'not'];
        $condition = preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', function ($matches) use ($phpKeywords) {
            $var = $matches[1];
            // Пропускаем ключевые слова и защищенные фрагменты
            if (in_array(strtolower($var), $phpKeywords) || strpos($var, '___') === 0) {
                return $var;
            }
            return '$' . $var;
        }, $condition);

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

        // Обрабатываем комплексные выражения с точками и массивами
        $result = $this->processPropertyAccess($expression);
        $expression = $result['expression'];
        $protected = $result['protected'];

        // Обрабатываем простые переменные (которые еще не обработаны)
        $expression = preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', function ($matches) {
            $var = $matches[1];
            // Пропускаем защищенные фрагменты и строки
            if (strpos($var, '___') === 0) {
                return $var;
            }
            return '$' . $var;
        }, $expression);

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
}
