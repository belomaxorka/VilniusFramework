<?php declare(strict_types=1);

namespace Core\Validation;

/**
 * Route Parameter Validator
 * 
 * Валидация и преобразование параметров роутов
 */
class RouteParameterValidator
{
    /**
     * Зарегистрированные правила валидации
     */
    protected array $rules = [];

    /**
     * Зарегистрированные преобразователи типов
     */
    protected array $transformers = [];

    public function __construct()
    {
        $this->registerDefaultRules();
        $this->registerDefaultTransformers();
    }

    /**
     * Зарегистрировать правило валидации
     */
    public function registerRule(string $name, \Closure $validator): void
    {
        $this->rules[$name] = $validator;
    }

    /**
     * Зарегистрировать преобразователь типа
     */
    public function registerTransformer(string $type, \Closure $transformer): void
    {
        $this->transformers[$type] = $transformer;
    }

    /**
     * Валидировать и преобразовать параметры
     *
     * @param array $params Параметры из роута
     * @param array $constraints Ограничения для параметров
     * @return array Валидированные и преобразованные параметры
     * @throws ValidationException
     */
    public function validate(array $params, array $constraints): array
    {
        $validated = [];

        foreach ($constraints as $name => $constraint) {
            if (!isset($params[$name])) {
                // Если параметр обязательный, но отсутствует
                if (!($constraint['optional'] ?? false)) {
                    throw new ValidationException("Required parameter '{$name}' is missing.");
                }
                
                // Используем значение по умолчанию
                $validated[$name] = $constraint['default'] ?? null;
                continue;
            }

            $value = $params[$name];

            // Применяем правила валидации
            if (isset($constraint['rules'])) {
                foreach ((array)$constraint['rules'] as $rule) {
                    $this->applyRule($name, $value, $rule);
                }
            }

            // Преобразуем тип
            if (isset($constraint['type'])) {
                $value = $this->transformType($value, $constraint['type']);
            }

            $validated[$name] = $value;
        }

        return $validated;
    }

    /**
     * Применить правило валидации
     */
    protected function applyRule(string $name, mixed $value, string $rule): void
    {
        // Парсим правило (например: "min:5" или "between:1,10")
        [$ruleName, $params] = $this->parseRule($rule);

        if (!isset($this->rules[$ruleName])) {
            throw new \RuntimeException("Validation rule '{$ruleName}' not found.");
        }

        $validator = $this->rules[$ruleName];

        if (!$validator($value, ...$params)) {
            throw new ValidationException(
                $this->formatErrorMessage($name, $ruleName, $params)
            );
        }
    }

    /**
     * Преобразовать значение в указанный тип
     */
    protected function transformType(mixed $value, string $type): mixed
    {
        if (isset($this->transformers[$type])) {
            return $this->transformers[$type]($value);
        }

        // Встроенные типы PHP
        return match($type) {
            'int', 'integer' => (int)$value,
            'float', 'double' => (float)$value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string)$value,
            'array' => (array)$value,
            default => $value,
        };
    }

    /**
     * Парсить правило на имя и параметры
     */
    protected function parseRule(string $rule): array
    {
        if (!str_contains($rule, ':')) {
            return [$rule, []];
        }

        [$name, $paramsStr] = explode(':', $rule, 2);
        $params = array_map('trim', explode(',', $paramsStr));

        return [$name, $params];
    }

    /**
     * Форматировать сообщение об ошибке
     */
    protected function formatErrorMessage(string $param, string $rule, array $params): string
    {
        $messages = [
            'required' => "Parameter '{$param}' is required.",
            'min' => "Parameter '{$param}' must be at least {$params[0]}.",
            'max' => "Parameter '{$param}' must not exceed {$params[0]}.",
            'between' => "Parameter '{$param}' must be between {$params[0]} and {$params[1]}.",
            'email' => "Parameter '{$param}' must be a valid email address.",
            'url' => "Parameter '{$param}' must be a valid URL.",
            'uuid' => "Parameter '{$param}' must be a valid UUID.",
            'alpha' => "Parameter '{$param}' must contain only letters.",
            'alphanumeric' => "Parameter '{$param}' must contain only letters and numbers.",
            'numeric' => "Parameter '{$param}' must be numeric.",
            'in' => "Parameter '{$param}' must be one of: " . implode(', ', $params),
        ];

        return $messages[$rule] ?? "Parameter '{$param}' failed validation rule '{$rule}'.";
    }

    /**
     * Зарегистрировать стандартные правила
     */
    protected function registerDefaultRules(): void
    {
        // Required
        $this->registerRule('required', fn($value) => !empty($value));

        // Min length/value
        $this->registerRule('min', function($value, $min) {
            if (is_numeric($value)) {
                return $value >= (int)$min;
            }
            return mb_strlen((string)$value) >= (int)$min;
        });

        // Max length/value
        $this->registerRule('max', function($value, $max) {
            if (is_numeric($value)) {
                return $value <= (int)$max;
            }
            return mb_strlen((string)$value) <= (int)$max;
        });

        // Between
        $this->registerRule('between', function($value, $min, $max) {
            if (is_numeric($value)) {
                return $value >= (int)$min && $value <= (int)$max;
            }
            $len = mb_strlen((string)$value);
            return $len >= (int)$min && $len <= (int)$max;
        });

        // Email
        $this->registerRule('email', fn($value) => filter_var($value, FILTER_VALIDATE_EMAIL) !== false);

        // URL
        $this->registerRule('url', fn($value) => filter_var($value, FILTER_VALIDATE_URL) !== false);

        // UUID
        $this->registerRule('uuid', function($value) {
            $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
            return preg_match($pattern, $value) === 1;
        });

        // Alpha (только буквы)
        $this->registerRule('alpha', fn($value) => ctype_alpha((string)$value));

        // Alphanumeric (буквы и цифры)
        $this->registerRule('alphanumeric', fn($value) => ctype_alnum((string)$value));

        // Numeric
        $this->registerRule('numeric', fn($value) => is_numeric($value));

        // In (значение в списке)
        $this->registerRule('in', fn($value, ...$allowed) => in_array($value, $allowed));

        // Regex pattern
        $this->registerRule('regex', fn($value, $pattern) => preg_match($pattern, $value) === 1);
    }

    /**
     * Зарегистрировать стандартные преобразователи
     */
    protected function registerDefaultTransformers(): void
    {
        // Slug transformer (для SEO-friendly URLs)
        $this->registerTransformer('slug', function($value) {
            $value = mb_strtolower($value);
            $value = preg_replace('/[^a-z0-9-]/', '-', $value);
            $value = preg_replace('/-+/', '-', $value);
            return trim($value, '-');
        });

        // Trim transformer
        $this->registerTransformer('trim', fn($value) => trim((string)$value));

        // Upper case
        $this->registerTransformer('upper', fn($value) => mb_strtoupper((string)$value));

        // Lower case
        $this->registerTransformer('lower', fn($value) => mb_strtolower((string)$value));

        // JSON decode
        $this->registerTransformer('json', fn($value) => json_decode($value, true));
    }
}

