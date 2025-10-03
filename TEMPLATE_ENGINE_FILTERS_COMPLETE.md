# Полная поддержка фильтров в условиях и выражениях - Итоговый отчет

## Дата: 2025-10-03

## 🎯 Цель

Добавить полную поддержку фильтров Twig в:
- Условиях `{% if %}`, `{% elseif %}`
- Выражениях `{% set %}`
- Циклах `{% for %}`
- Любых других местах, где используются выражения

## ✅ Что было сделано

### 1. Обновлен метод `processCondition()` 

**Файл**: `core/TemplateEngine.php` (строки ~1390-1420)

Добавлена обработка фильтров в условиях:

```php
// Ищем все переменные с фильтрами (variable|filter или variable|filter|filter2)
// Останавливаемся перед: операторами сравнения, логическими операторами, концом
$filterProtected = [];
$condition = preg_replace_callback(
    '/([a-zA-Z_][a-zA-Z0-9_\.]*(?:\[[^\]]+\])*)((?:\|[a-zA-Z_][a-zA-Z0-9_]*(?:\([^)]*\))?)+)(?=\s*(?:[<>=!+\-*\/]|\band\b|\bor\b)|$|%|\))/i',
    function ($matches) use (&$filterProtected) {
        // Компилируем фильтры
        $parts = $this->splitByPipe($variable . $filterPart);
        $varExpr = $this->processVariable(array_shift($parts));
        $compiled = $this->compileFilters($varExpr, $parts);
        
        // Сохраняем как placeholder
        $placeholder = '___FILTER_' . count($filterProtected) . '___';
        $filterProtected[$placeholder] = $compiled;
        return $placeholder;
    },
    $condition
);
```

**Что компилируется**:
```twig
{% if users|length > 0 %}
```
↓
```php
<?php if ($__tpl->applyFilter('length', $users) > 0): ?>
```

### 2. Обновлен метод `processExpression()`

**Файл**: `core/TemplateEngine.php` (строки ~1967-1991)

Добавлена **такая же** обработка фильтров для выражений:

```php
// ОБРАБАТЫВАЕМ ФИЛЬТРЫ (как в processCondition)
$filterProtected = [];
$expression = preg_replace_callback(
    '/([a-zA-Z_][a-zA-Z0-9_\.]*(?:\[[^\]]+\])*)((?:\|[a-zA-Z_][a-zA-Z0-9_]*(?:\([^)]*\))?)+)(?=\s*(?:[<>=!+\-*\/~]|\band\b|\bor\b)|$|%|\)|,)/i',
    function ($matches) use (&$filterProtected) {
        // Та же логика компиляции
    },
    $expression
);
```

**Что компилируется**:
```twig
{% set count = items|length %}
```
↓
```php
<?php $count = $__tpl->applyFilter('length', $items); ?>
```

### 3. Восстановление плейсхолдеров

В обоих методах добавлено восстановление `$filterProtected`:

```php
// В processCondition (строка ~1509)
$replacements = $testProtected + $inProtected + $startsEndsProtected + $filterProtected;

// В processExpression (строка ~2062)
$replacements = $functionProtected + $protected + $arrayLiterals + $ternaryProtected + $filterProtected;
```

## 📊 Регулярное выражение

### Основная регулярка

```regex
/([a-zA-Z_][a-zA-Z0-9_\.]*(?:\[[^\]]+\])*)((?:\|[a-zA-Z_][a-zA-Z0-9_]*(?:\([^)]*\))?)+)(?=\s*(?:[<>=!+\-*\/]|\band\b|\bor\b)|$|%|\))/i
```

### Что захватывает

**Группа 1**: Переменная
- `[a-zA-Z_][a-zA-Z0-9_\.]*` - имя переменной (включая точки для свойств)
- `(?:\[[^\]]+\])*` - доступ к массиву `[key]`

**Группа 2**: Фильтры
- `(?:\|[a-zA-Z_][a-zA-Z0-9_]*(?:\([^)]*\))?)+` - один или несколько фильтров
- `\|[a-zA-Z_][a-zA-Z0-9_]*` - имя фильтра после пайпа
- `(?:\([^)]*\))?` - опциональные аргументы в скобках

**Lookahead (где останавливается)**:
- `\s*(?:[<>=!+\-*\/]|\band\b|\bor\b)` - операторы сравнения и логические
- `$` - конец строки
- `%` - конец тега Twig
- `\)` - закрывающая скобка
- `,` - запятая (для массивов)

## 🧪 Тесты

### Создан файл тестов

**Файл**: `tests/Unit/Core/Template/TemplateEngineFiltersInConditionsTest.php`

**Всего тестов**: 25
**Группы тестов**: 6

#### 1. Filters in IF conditions (8 тестов)
- `length`, `count`, `upper`, `lower`, `trim`, `abs`, `first`, `last`

#### 2. Multiple filters in IF conditions (4 теста)
- Цепочки фильтров
- Фильтры с аргументами
- Комплексные условия с `and`/`or`

#### 3. Filters in ELSEIF conditions (2 теста)
- Фильтры в elseif
- Разные фильтры в if и elseif

#### 4. Edge cases for filters in conditions (4 теста)
- Пустые массивы
- Операторы сравнения
- Вложенные условия

#### 5. Arithmetic operations with filters (3 теста)
- Фильтры через `{% set %}`
- Фильтры в скобках
- Сравнение двух отфильтрованных значений

#### 6. Real-world use cases (4 теста)
- Проверка постов
- Валидация email
- Пагинация
- Разные сообщения по размеру

## 📝 Примеры использования

### Базовое использование

```twig
{# Проверка наличия элементов #}
{% if users|length > 0 %}
    Найдено {{ users|length }} пользователей
{% endif %}

{# Сравнение строк #}
{% if name|upper == "ADMIN" %}
    Администратор
{% endif %}

{# Проверка пустоты после trim #}
{% if text|trim != "" %}
    Есть текст
{% endif %}
```

### Цепочки фильтров

```twig
{% if name|trim|upper == "JOHN" %}
    Совпадение!
{% endif %}

{% if email|lower|trim != "" %}
    Email валиден
{% endif %}
```

### Сложные условия

```twig
{% if users|length > 0 and status|upper == "ACTIVE" %}
    Показываем активных пользователей
{% endif %}

{% if items|length > 10 or priority|upper == "HIGH" %}
    Много элементов или высокий приоритет
{% endif %}
```

### Использование с {% set %}

```twig
{# Сохраняем результат фильтра #}
{% set count = items|length %}
{% set total = count + 5 %}

{% if total > 10 %}
    Более 10 элементов
{% endif %}
```

### Elseif с фильтрами

```twig
{% if users|length > 100 %}
    Много пользователей
{% elseif users|length > 10 %}
    Средне пользователей
{% elseif users|length > 0 %}
    Мало пользователей
{% else %}
    Нет пользователей
{% endif %}
```

### Вложенные условия

```twig
{% if posts|length > 0 %}
    {% if posts|first|upper == "ВАЖНО" %}
        Первый пост важный!
    {% endif %}
{% endif %}
```

### Сравнение двух фильтрованных значений

```twig
{% if name1|upper == name2|upper %}
    Имена совпадают (без учета регистра)
{% endif %}

{% if email1|lower == email2|lower %}
    Email'ы одинаковые
{% endif %}
```

## 🔍 Что компилируется

### Пример 1: Простое условие

**Исходный Twig**:
```twig
{% if users|length > 0 %}Has users{% endif %}
```

**Скомпилированный PHP**:
```php
<?php if ($__tpl->applyFilter('length', $users) > 0): ?>Has users<?php endif; ?>
```

### Пример 2: Цепочка фильтров

**Исходный Twig**:
```twig
{% if name|trim|upper == "JOHN" %}Match{% endif %}
```

**Скомпилированный PHP**:
```php
<?php if ($__tpl->applyFilter('upper', $__tpl->applyFilter('trim', $name)) == "JOHN"): ?>Match<?php endif; ?>
```

### Пример 3: {% set %} с фильтром

**Исходный Twig**:
```twig
{% set count = items|length %}
{% if count > 5 %}Many{% endif %}
```

**Скомпилированный PHP**:
```php
<?php $count = $__tpl->applyFilter('length', $items); ?>
<?php if ($count > 5): ?>Many<?php endif; ?>
```

### Пример 4: Два фильтра в одном условии

**Исходный Twig**:
```twig
{% if name1|upper == name2|upper %}Same{% endif %}
```

**Скомпилированный PHP**:
```php
<?php if ($__tpl->applyFilter('upper', $name1) == $__tpl->applyFilter('upper', $name2)): ?>Same<?php endif; ?>
```

## 📚 Документация

Создана документация:

1. **docs/TemplateEngineFiltersFix.md** - описание исправления
2. **docs/TemplateEngineFiltersInConditionsTests.md** - документация по тестам
3. **TEMPLATE_ENGINE_FILTERS_COMPLETE.md** - этот файл (полное резюме)

## ✨ Преимущества

### До исправления

❌ **Не работало**:
```twig
{% if users|length > 0 %}  {# Ошибка: Undefined variable $length #}
```

### После исправления

✅ **Работает**:
```twig
{% if users|length > 0 %}
    Найдено {{ users|length }} пользователей
{% endif %}

{% set count = items|length %}
{% if count + 5 > 10 %}
    Много элементов
{% endif %}

{% if name|trim|upper == "ADMIN" %}
    Администратор
{% endif %}
```

## 🎯 Итоговые изменения

### Измененные файлы

1. **core/TemplateEngine.php**
   - Метод `processCondition()` - добавлена обработка фильтров
   - Метод `processExpression()` - добавлена обработка фильтров

2. **tests/Unit/Core/Template/TemplateEngineFiltersInConditionsTest.php**
   - Создан новый файл с 25 тестами

3. **docs/TemplateEngineFiltersFix.md** - документация
4. **docs/TemplateEngineFiltersInConditionsTests.md** - описание тестов
5. **TEMPLATE_ENGINE_FILTERS_COMPLETE.md** - полное резюме

### Строки кода

- **Добавлено**: ~300 строк (код + тесты + документация)
- **Изменено**: 2 метода в TemplateEngine.php

## 🧪 Запуск тестов

```bash
# Все тесты для фильтров в условиях
php vendor/bin/pest tests/Unit/Core/Template/TemplateEngineFiltersInConditionsTest.php

# Все тесты шаблонизатора
php vendor/bin/pest tests/Unit/Core/Template/

# Только фильтры
php vendor/bin/pest --filter="Filters in IF conditions"
```

## 🎉 Результат

- ✅ **25/25 тестов** проходят успешно
- ✅ Полная поддержка фильтров в условиях
- ✅ Полная поддержка фильтров в выражениях
- ✅ Обратная совместимость сохранена
- ✅ Производительность не пострадала
- ✅ Код чистый и понятный

---

**Теперь Vilnius Template Engine поддерживает фильтры везде, где это необходимо! 🚀**

