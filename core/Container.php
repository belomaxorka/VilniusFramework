<?php declare(strict_types=1);

namespace Core;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

/**
 * Dependency Injection Container
 * 
 * Простой контейнер зависимостей с автоматическим разрешением
 */
class Container
{
    /**
     * Зарегистрированные привязки
     */
    protected array $bindings = [];

    /**
     * Синглтоны (shared instances)
     */
    protected array $instances = [];

    /**
     * Алиасы
     */
    protected array $aliases = [];

    /**
     * Глобальный экземпляр контейнера
     */
    protected static ?Container $instance = null;

    /**
     * Получить глобальный экземпляр контейнера
     */
    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Установить глобальный экземпляр контейнера
     */
    public static function setInstance(?Container $container = null): void
    {
        self::$instance = $container;
    }

    /**
     * Привязать интерфейс к реализации
     *
     * @param string $abstract Абстракция (интерфейс или класс)
     * @param \Closure|string|null $concrete Реализация (closure, класс или null для авторазрешения)
     * @param bool $shared Создавать как singleton
     * @return void
     */
    public function bind(string $abstract, \Closure|string|null $concrete = null, bool $shared = false): void
    {
        $concrete = $concrete ?? $abstract;

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    /**
     * Привязать как singleton (одна инстанция на всё приложение)
     *
     * @param string $abstract
     * @param \Closure|string|null $concrete
     * @return void
     */
    public function singleton(string $abstract, \Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Зарегистрировать существующий экземпляр как singleton
     *
     * @param string $abstract
     * @param mixed $instance
     * @return void
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Создать алиас для существующей привязки
     *
     * @param string $alias
     * @param string $abstract
     * @return void
     */
    public function alias(string $alias, string $abstract): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Разрешить зависимость из контейнера
     *
     * @param string $abstract
     * @param array $parameters Дополнительные параметры
     * @return mixed
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        // Разрешаем алиасы
        $abstract = $this->getAlias($abstract);

        // Если уже есть singleton экземпляр
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Получаем конкретную реализацию
        $concrete = $this->getConcrete($abstract);

        // Строим объект
        $object = $this->build($concrete, $parameters);

        // Если это shared binding, сохраняем как singleton
        if (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Вызвать метод с автоматическим внедрением зависимостей
     *
     * @param callable|array $callback
     * @param array $parameters
     * @return mixed
     */
    public function call(callable|array $callback, array $parameters = []): mixed
    {
        if (is_array($callback)) {
            [$class, $method] = $callback;
            
            if (is_string($class)) {
                $class = $this->make($class);
            }
            
            $callback = [$class, $method];
        }

        return $callback(...$this->resolveMethodDependencies($callback, $parameters));
    }

    /**
     * Получить конкретную реализацию
     */
    protected function getConcrete(string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Построить экземпляр класса
     */
    protected function build(mixed $concrete, array $parameters = []): mixed
    {
        // Если это closure, выполняем его
        if ($concrete instanceof \Closure) {
            return $concrete($this, $parameters);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new \RuntimeException("Target class [{$concrete}] does not exist.", 0, $e);
        }

        // Проверяем, можно ли создать экземпляр
        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException("Target [{$concrete}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // Если нет конструктора, создаем без параметров
        if ($constructor === null) {
            return new $concrete();
        }

        // Разрешаем зависимости конструктора
        $dependencies = $this->resolveConstructorDependencies($constructor, $parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Разрешить зависимости конструктора
     */
    protected function resolveConstructorDependencies(\ReflectionMethod $constructor, array $parameters = []): array
    {
        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();

            // Если параметр передан явно
            if (array_key_exists($name, $parameters)) {
                $dependencies[] = $parameters[$name];
                continue;
            }

            // Получаем тип параметра
            $type = $parameter->getType();

            // Если тип указан и это класс, резолвим из контейнера
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
                continue;
            }

            // Если есть значение по умолчанию
            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            // Если параметр nullable
            if ($type && $type->allowsNull()) {
                $dependencies[] = null;
                continue;
            }

            throw new \RuntimeException(
                "Unable to resolve dependency [{$name}] in class [{$constructor->getDeclaringClass()->getName()}]"
            );
        }

        return $dependencies;
    }

    /**
     * Разрешить зависимости метода
     */
    protected function resolveMethodDependencies(callable $callback, array $parameters = []): array
    {
        if (is_array($callback)) {
            $reflector = new \ReflectionMethod($callback[0], $callback[1]);
        } else {
            $reflector = new \ReflectionFunction($callback);
        }

        $dependencies = [];

        foreach ($reflector->getParameters() as $parameter) {
            $name = $parameter->getName();

            // Если параметр передан явно
            if (array_key_exists($name, $parameters)) {
                $dependencies[] = $parameters[$name];
                continue;
            }

            // Получаем тип параметра
            $type = $parameter->getType();

            // Если тип указан и это класс, резолвим из контейнера
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
                continue;
            }

            // Если есть значение по умолчанию
            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            // Если параметр nullable
            if ($type && $type->allowsNull()) {
                $dependencies[] = null;
                continue;
            }

            throw new \RuntimeException(
                "Unable to resolve dependency [{$name}]"
            );
        }

        return $dependencies;
    }

    /**
     * Получить настоящее имя по алиасу
     */
    protected function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    /**
     * Проверить, зарегистрирована ли привязка
     */
    public function has(string $abstract): bool
    {
        $abstract = $this->getAlias($abstract);
        
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Удалить привязку
     */
    public function forget(string $abstract): void
    {
        $abstract = $this->getAlias($abstract);
        
        unset($this->bindings[$abstract], $this->instances[$abstract], $this->aliases[$abstract]);
    }

    /**
     * Очистить контейнер
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
    }
}

