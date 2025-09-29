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

    public function __construct(?string $templateDir = null, ?string $cacheDir = null)
    {
        $root = defined('ROOT') ? ROOT : dirname(__DIR__, 2);
        $this->templateDir = $templateDir ?? $root . '/resources/views';
        $this->cacheDir = $cacheDir ?? $root . '/storage/cache/templates';
        
        // Создаем директорию кэша если её нет
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
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
        echo $this->render($template, $variables);
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
     * Компилирует шаблон в PHP код
     */
    private function compileTemplate(string $content): string
    {
        // Экранируем PHP теги
        $content = str_replace(['<?php', '<?=', '?>'], ['&lt;?php', '&lt;?=', '?&gt;'], $content);

        // Обрабатываем переменные {{ variable }}
        $content = preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function($matches) {
            return '<?= htmlspecialchars(' . $this->processVariableInExpression($matches[1]) . ' ?? \'\', ENT_QUOTES, \'UTF-8\') ?>';
        }, $content);

        // Обрабатываем неэкранированные переменные {! variable !}
        $content = preg_replace_callback('/\{\!\s*([^}]+)\s*\!\}/', function($matches) {
            return '<?= ' . $this->processVariableInExpression($matches[1]) . ' ?? \'\' ?>';
        }, $content);

        // Обрабатываем условия {% if condition %}
        $content = preg_replace_callback('/\{\%\s*if\s+([^%]+)\s*\%\}/', function($matches) {
            return '<?php if (' . $this->processVariableInCondition($matches[1]) . '): ?>';
        }, $content);
        $content = preg_replace_callback('/\{\%\s*elseif\s+([^%]+)\s*\%\}/', function($matches) {
            return '<?php elseif (' . $this->processVariableInCondition($matches[1]) . '): ?>';
        }, $content);
        $content = preg_replace('/\{\%\s*else\s*\%\}/', '<?php else: ?>', $content);
        $content = preg_replace('/\{\%\s*endif\s*\%\}/', '<?php endif; ?>', $content);

        // Обрабатываем циклы {% for item in items %}
        $content = preg_replace_callback('/\{\%\s*for\s+(\w+)\s+in\s+([^%]+)\s*\%\}/', function($matches) {
            return '<?php foreach (' . $this->processVariableInCondition($matches[2]) . ' as $' . $matches[1] . '): ?>';
        }, $content);
        $content = preg_replace('/\{\%\s*endfor\s*\%\}/', '<?php endforeach; ?>', $content);

        // Обрабатываем циклы while {% while condition %}
        $content = preg_replace('/\{\%\s*while\s+([^%]+)\s*\%\}/', '<?php while (\\1): ?>', $content);
        $content = preg_replace('/\{\%\s*endwhile\s*\%\}/', '<?php endwhile; ?>', $content);

        // Обрабатываем включения {% include 'template.tpl' %}
        $content = preg_replace_callback('/\{\%\s*include\s+[\'"]([^\'"]+)[\'"]\s*\%\}/', function($matches) {
            return $this->processInclude($matches[1]);
        }, $content);

        // Обрабатываем расширения {% extends 'base.tpl' %}
        $content = preg_replace_callback('/\{\%\s*extends\s+[\'"]([^\'"]+)[\'"]\s*\%\}/', function($matches) {
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
        
        ob_start();
        eval('?>' . $compiledContent);
        $output = ob_get_clean();
        
        return $output;
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
     * Обрабатывает переменные в условиях и циклах
     */
    private function processVariableInCondition(string $condition): string
    {
        // Обрабатываем простые переменные (без точек и скобок)
        // Исключаем ключевые слова PHP и уже обработанные переменные
        $phpKeywords = ['true', 'false', 'null', 'and', 'or', 'not', 'if', 'else', 'elseif', 'endif', 'for', 'endfor', 'while', 'endwhile'];
        
        $condition = preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/', function($matches) use ($phpKeywords) {
            $var = $matches[1];
            if (in_array(strtolower($var), $phpKeywords)) {
                return $var;
            }
            // Проверяем, не является ли это уже переменной PHP
            if (strpos($var, '$') === 0) {
                return $var;
            }
            return '$' . $var;
        }, $condition);
        
        // Обрабатываем доступ к свойствам объектов и элементам массивов
        $condition = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)/', '$$1["$2"]', $condition);
        
        return $condition;
    }

    /**
     * Обрабатывает переменные в выражениях (для {{ }} и {! !})
     */
    private function processVariableInExpression(string $expression): string
    {
        // Обрабатываем все переменные сразу, включая доступ к объектам и массивам
        $expression = preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*(\.[a-zA-Z_][a-zA-Z0-9_]*|\[[^\]]+\])*/', function($matches) {
            $variable = $matches[0];
            
            // Разбираем переменную на части
            $parts = preg_split('/(\.|\[|\])/', $variable, -1, PREG_SPLIT_DELIM_CAPTURE);
            $result = '$' . $parts[0]; // Первая часть - имя переменной
            
            for ($i = 1; $i < count($parts); $i += 2) {
                if ($parts[$i] === '.') {
                    // Доступ к свойству объекта - используем -> для объектов
                    $result .= '->' . $parts[$i + 1];
                } elseif ($parts[$i] === '[') {
                    // Доступ к элементу массива
                    $result .= '[' . $parts[$i + 1] . ']';
                }
            }
            
            return $result;
        }, $expression);
        
        return $expression;
    }

    /**
     * Обрабатывает доступ к объектам и массивам
     */
    private function processObjectAndArrayAccess(string $expression): string
    {
        // Обрабатываем доступ к свойствам объектов через точку (например: user.name)
        $expression = preg_replace('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)\b/', '$1["$2"]', $expression);
        
        // Обрабатываем доступ к элементам массива через квадратные скобки (например: items[0])
        $expression = preg_replace('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*\[\s*([^\]]+)\s*\]/', '$1[$2]', $expression);
        
        // Обрабатываем вложенный доступ (например: user["address"].city)
        $expression = preg_replace('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*\[\s*["\']([^"\']+)["\']\s*\]\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)/', '$1["$2"]["$3"]', $expression);
        
        // Обрабатываем смешанный доступ (например: users[0].name)
        $expression = preg_replace('/\b([a-zA-Z_][a-zA-Z0-9_]*)\s*\[\s*([^\]]+)\s*\]\s*\.\s*([a-zA-Z_][a-zA-Z0-9_]*)/', '$1[$2]["$3"]', $expression);
        
        return $expression;
    }
}
