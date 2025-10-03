# Тесты для фильтров в условиях Twig

## Обзор

Добавлены comprehensive тесты для проверки работы фильтров в условиях `{% if %}`, `{% elseif %}` и `{% while %}`.

## Файл тестов

`tests/Unit/Core/Template/TemplateEngineFiltersInConditionsTest.php`

## Структура тестов

### 1. Filters in IF conditions (8 тестов)

Проверка базовых фильтров в условиях:

- ✅ `length` - проверка длины массива
- ✅ `count` - подсчет элементов
- ✅ `upper` - приведение к верхнему регистру
- ✅ `lower` - приведение к нижнему регистру
- ✅ `trim` - удаление пробелов
- ✅ `abs` - абсолютное значение
- ✅ `first` - первый элемент
- ✅ `last` - последний элемент

**Примеры**:
```twig
{% if users|length > 0 %}Has users{% endif %}
{% if name|upper == "JOHN" %}Match{% endif %}
{% if text|trim != "" %}Has text{% endif %}
```

### 2. Multiple filters in IF conditions (4 теста)

Проверка цепочек фильтров и сложных условий:

- ✅ Цепочка фильтров: `name|trim|upper`
- ✅ Фильтры с аргументами: `text|slice(0, 5)`
- ✅ Сложные условия с `and`
- ✅ Сложные условия с `or`

**Примеры**:
```twig
{% if name|trim|upper == "JOHN" %}Match{% endif %}
{% if users|length > 0 and name|upper == "ADMIN" %}OK{% endif %}
{% if items|length > 10 or status|upper == "ACTIVE" %}Show{% endif %}
```

### 3. Filters in ELSEIF conditions (2 теста)

Проверка фильтров в `elseif`:

- ✅ Фильтры в elseif
- ✅ Разные фильтры в if и elseif

**Примеры**:
```twig
{% if count|length > 10 %}Many
{% elseif count|length > 5 %}Some
{% else %}Few
{% endif %}
```

### 4. Edge cases for filters in conditions (4 теста)

Граничные случаи:

- ✅ Пустой массив
- ✅ Операторы сравнения (`>=`, `<=`, `!=`)
- ✅ Вложенные условия
- ✅ Фильтры в комбинации

**Примеры**:
```twig
{% if items|length == 0 %}Empty{% endif %}
{% if price|abs >= 100 %}Expensive{% endif %}
{% if users|length > 0 %}
    {% if users|first|upper == "ADMIN" %}Admin first{% endif %}
{% endif %}
```

### 5. Parenthesized expressions with filters (2 теста)

Фильтры в скобках и сравнение отфильтрованных значений:

- ✅ Фильтр в скобках: `(items|length) > 5`
- ✅ Сравнение двух фильтрованных значений

**Примеры**:
```twig
{% if (items|length) > 5 %}Many{% endif %}
{% if name1|upper == name2|upper %}Same{% endif %}
```

### 6. Real-world use cases (4 теста)

Реальные примеры использования:

- ✅ Проверка наличия постов
- ✅ Валидация email
- ✅ Пагинация
- ✅ Разные сообщения по размеру массива

**Примеры**:
```twig
{% if posts|length > 0 %}
    {{ posts|length }} posts found
{% else %}
    No posts
{% endif %}

{% if users|length == 0 %}No users
{% elseif users|length == 1 %}One user
{% else %}{{ users|length }} users
{% endif %}
```

## Запуск тестов

```bash
# Все тесты фильтров в условиях
php vendor/bin/pest tests/Unit/Core/Template/TemplateEngineFiltersInConditionsTest.php

# С подробным выводом
php vendor/bin/pest tests/Unit/Core/Template/TemplateEngineFiltersInConditionsTest.php --verbose

# Только конкретная группа
php vendor/bin/pest --filter="Filters in IF conditions"
```

## Покрытие

**Всего тестов**: 24

Покрываемая функциональность:
- ✅ Все базовые фильтры (upper, lower, trim, length, count, first, last, abs)
- ✅ Фильтры с аргументами (slice)
- ✅ Цепочки фильтров
- ✅ Условия if/elseif/else
- ✅ Логические операторы (and, or)
- ✅ Операторы сравнения (>, <, ==, !=, >=, <=)
- ✅ Вложенные условия
- ✅ Реальные use cases

## Что НЕ поддерживается (ограничения)

### ❌ Арифметические операции после фильтров

**НЕ работает**:
```twig
{% if items|length + 5 > 10 %}...{% endif %}
{% if items|length - 2 > 0 %}...{% endif %}
{% if number|abs * 2 > 20 %}...{% endif %}
```

**Решение**: Выполните арифметику в контроллере:
```php
// В контроллере
$itemsCountPlusFive = count($items) + 5;

// В шаблоне
{% if itemsCountPlusFive > 10 %}...{% endif %}
```

Или используйте скобки и арифметику вне фильтра:
```twig
{% set length = items|length %}
{% if length + 5 > 10 %}...{% endif %}
```

## Технические детали

### Регулярное выражение

```php
'/\b([a-zA-Z_][a-zA-Z0-9_\.]*(?:\[[^\]]+\])*)(\|[a-zA-Z_][a-zA-Z0-9_]*(?:\([^)]*\))?(?:\|[a-zA-Z_][a-zA-Z0-9_]*(?:\([^)]*\))?)*)(?=\s*(?:[<>=!+\-*\/]|\band\b|\bor\b|$))/i'
```

**Что захватывает**:
1. Переменная: `[a-zA-Z_][a-zA-Z0-9_\.]*(?:\[[^\]]+\])*`
2. Цепочка фильтров: `\|filter` или `\|filter(args)`
3. Останавливается перед: операторами сравнения, логическими операторами, арифметическими операторами

### Процесс обработки

1. **Защита строк** - сохраняем строки в кавычках
2. **Обработка фильтров** - ищем и компилируем `variable|filter`
3. **Обработка тестов** - is defined, is null, etc.
4. **Обработка in/not in**
5. **Обработка логических операторов**
6. **Обработка переменных**
7. **Восстановление** всех защищенных элементов

## Примеры ошибок и решений

### Ошибка: "Filter 'length + 5' not found"

**Причина**: Попытка использовать арифметику сразу после фильтра

**Решение**: Используйте переменную или делайте в контроллере
```twig
{# Плохо #}
{% if items|length + 5 > 10 %}...{% endif %}

{# Хорошо #}
{% set count = items|length %}
{% if count + 5 > 10 %}...{% endif %}
```

### Ошибка: "Unsupported operand types: string | bool"

**Причина**: Конфликт пайпа фильтра с логическим OR (`|`)

**Решение**: Используйте `or` вместо `|` для логических операций
```twig
{# Плохо #}
{% if a | b %}...{% endif %}

{# Хорошо #}
{% if a or b %}...{% endif %}
```

## Best Practices

### ✅ Делайте

```twig
{# Простые фильтры #}
{% if users|length > 0 %}...{% endif %}

{# Цепочки фильтров #}
{% if name|trim|upper == "ADMIN" %}...{% endif %}

{# Логические операторы #}
{% if users|length > 0 and status|upper == "ACTIVE" %}...{% endif %}

{# Вложенные условия #}
{% if items|length > 0 %}
    {% if items|first == "important" %}...{% endif %}
{% endif %}
```

### ❌ Не делайте

```twig
{# Арифметика после фильтра #}
{% if items|length + 5 > 10 %}...{% endif %}

{# Логическое ИЛИ через пайп #}
{% if a|b %}...{% endif %}

{# Слишком сложные выражения #}
{% if (a|filter1) * (b|filter2) + c > 10 %}...{% endif %}
```

## Заключение

Тесты покрывают все основные случаи использования фильтров в условиях и гарантируют:

- ✅ Корректную работу базовых фильтров
- ✅ Поддержку цепочек фильтров
- ✅ Совместимость с логическими операторами
- ✅ Правильную обработку граничных случаев
- ✅ Практическую применимость в реальных сценариях

**24 теста** обеспечивают надежность и стабильность функционала! 🎉

