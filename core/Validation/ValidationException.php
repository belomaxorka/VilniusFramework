<?php declare(strict_types=1);

namespace Core\Validation;

/**
 * Validation Exception
 * 
 * Исключение при ошибках валидации
 */
class ValidationException extends \Exception
{
    protected array $errors = [];

    public function __construct(string $message = "", array $errors = [], int $code = 422)
    {
        parent::__construct($message, $code);
        $this->errors = $errors ?: [$message];
    }

    /**
     * Получить ошибки валидации
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Установить ошибки валидации
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    /**
     * Добавить ошибку
     */
    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }
}

