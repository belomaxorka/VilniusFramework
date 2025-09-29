# Фильтры шаблонизатора

Фильтры позволяют модифицировать переменные перед их выводом в шаблоне.

## Синтаксис

```twig
{{ variable|filter }}
{{ variable|filter(arg1, arg2) }}
{{ variable|filter1|filter2|filter3 }}
```

## Встроенные фильтры

### Текстовые фильтры

#### `upper`
Преобразует текст в верхний регистр.
```twig
{{ "hello"|upper }}  {# HELLO #}
{{ name|upper }}
```

#### `lower`
Преобразует текст в нижний регистр.
```twig
{{ "HELLO"|lower }}  {# hello #}
{{ name|lower }}
```

#### `capitalize`
Делает первую букву каждого слова заглавной.
```twig
{{ "hello world"|capitalize }}  {# Hello World #}
```

#### `trim`
Удаляет пробелы в начале и конце строки.
```twig
{{ "  hello  "|trim }}  {# hello #}
```

#### `truncate(length, suffix)`
Обрезает строку до указанной длины и добавляет суффикс.
```twig
{{ text|truncate(50, "...") }}
{{ description|truncate(100) }}
```

#### `replace(search, replace)`
Заменяет подстроку в тексте.
```twig
{{ text|replace("world", "PHP") }}
{{ "Hello World"|replace("World", "Universe") }}  {# Hello Universe #}
```

#### `split(delimiter)`
Разбивает строку на массив по разделителю.
```twig
{{ "apple,banana,cherry"|split(",") }}  {# ['apple', 'banana', 'cherry'] #}
```

#### `reverse`
Переворачивает строку или массив.
```twig
{{ "hello"|reverse }}  {# olleh #}
{{ items|reverse }}
```

### HTML фильтры

#### `escape` / `e`
Экранирует HTML специальные символы.
```twig
{{ html|escape }}
{{ "<script>alert('xss')</script>"|e }}
```

#### `striptags`
Удаляет HTML теги.
```twig
{{ "<p>Hello <b>world</b></p>"|striptags }}  {# Hello world #}
```

#### `nl2br`
Преобразует переводы строк в HTML `<br>` теги.
```twig
{! "Line 1\nLine 2"|nl2br !}  {# Line 1<br />Line 2 #}
```

### Числовые фильтры

#### `abs`
Возвращает абсолютное значение числа.
```twig
{{ -42|abs }}  {# 42 #}
{{ number|abs }}
```

#### `round(precision)`
Округляет число до указанной точности.
```twig
{{ 3.14159|round(2) }}  {# 3.14 #}
{{ price|round(0) }}  {# Округляет до целого #}
```

#### `number_format(decimals, dec_point, thousands_sep)`
Форматирует число.
```twig
{{ 1234.56|number_format(2, ".", ",") }}  {# 1,234.56 #}
{{ price|number_format(2) }}
```

### Фильтры для массивов

#### `length`
Возвращает длину массива или строки.
```twig
{{ items|length }}
{{ "hello"|length }}  {# 5 #}
```

#### `count`
Подсчитывает количество элементов в массиве.
```twig
{{ items|count }}
```

#### `join(separator)`
Объединяет элементы массива в строку.
```twig
{{ ["apple", "banana", "cherry"]|join(", ") }}  {# apple, banana, cherry #}
{{ tags|join(" | ") }}
```

#### `first`
Возвращает первый элемент массива.
```twig
{{ items|first }}
```

#### `last`
Возвращает последний элемент массива.
```twig
{{ items|last }}
```

#### `keys`
Возвращает ключи массива.
```twig
{{ user|keys }}  {# ['name', 'email', 'age'] #}
```

#### `values`
Возвращает значения массива.
```twig
{{ user|values }}
```

### Фильтры форматирования

#### `date(format)`
Форматирует дату/timestamp.
```twig
{{ timestamp|date("Y-m-d") }}
{{ timestamp|date("d/m/Y H:i:s") }}
{{ "2024-01-15"|date("d.m.Y") }}
```

#### `default(value)`
Возвращает значение по умолчанию, если переменная пуста.
```twig
{{ name|default("Guest") }}
{{ title|default("Без названия") }}
```

### JSON фильтры

#### `json`
Преобразует значение в JSON.
```twig
{! data|json !}
{! user|json !}  {# {"name":"John","age":30} #}
```

#### `json_decode`
Декодирует JSON строку.
```twig
{{ '{"name":"John"}'|json_decode }}
```

### URL фильтры

#### `url_encode`
Кодирует строку для использования в URL.
```twig
{{ "hello world"|url_encode }}  {# hello+world #}
{{ query|url_encode }}
```

#### `url_decode`
Декодирует URL-encoded строку.
```twig
{{ "hello+world"|url_decode }}  {# hello world #}
```

### Фильтры отладки

#### `dump`
Выводит переменную в читаемом виде (для отладки).
```twig
{! variable|dump !}
```

## Цепочки фильтров

Фильтры можно объединять в цепочки:

```twig
{{ name|trim|lower|capitalize }}
{{ text|truncate(100, "...")|upper }}
{{ items|first|upper }}
{{ price|round(2)|number_format(2, ".", ",") }}
```

## Пользовательские фильтры

Вы можете добавлять свои собственные фильтры через PHP API:

```php
// Простой фильтр без аргументов
$engine->addFilter('double', function($value) {
    return $value * 2;
});

// Фильтр с аргументами
$engine->addFilter('repeat', function($value, $times) {
    return str_repeat($value, $times);
});

// Фильтр для форматирования цены
$engine->addFilter('price', function($value, $currency = 'USD') {
    return number_format($value, 2) . ' ' . $currency;
});
```

Использование в шаблоне:
```twig
{{ 5|double }}  {# 10 #}
{{ "Ha"|repeat(3) }}  {# HaHaHa #}
{{ 1234.56|price("EUR") }}  {# 1,234.56 EUR #}
```

## Проверка существования фильтра

```php
if ($engine->hasFilter('myfilter')) {
    // Фильтр существует
}
```

## Примеры использования

### Форматирование вывода пользователя

```twig
<h1>{{ user.name|capitalize }}</h1>
<p>Email: {{ user.email|lower }}</p>
<p>Registered: {{ user.created_at|date("d.m.Y") }}</p>
```

### Работа с текстом

```twig
<div class="preview">
    {{ article.content|striptags|truncate(200, "...") }}
</div>
```

### Безопасный вывод HTML

```twig
{# Экранированный вывод (по умолчанию) #}
{{ user_input }}

{# Неэкранированный вывод с обработкой #}
{! trusted_html|nl2br !}
```

### Работа с массивами

```twig
<p>Tags: {{ tags|join(", ") }}</p>
<p>Total items: {{ items|count }}</p>
<p>First item: {{ items|first }}</p>
<p>Last item: {{ items|last }}</p>
```

### Значения по умолчанию

```twig
<p>Welcome, {{ username|default("Guest") }}!</p>
<p>Description: {{ description|default("No description available") }}</p>
```

## Совместимость с другими функциями

Фильтры работают со всеми возможностями шаблонизатора:

```twig
{# С доступом к свойствам #}
{{ user.profile.bio|truncate(150) }}

{# В циклах #}
{% for item in items %}
    {{ item.name|upper }}
{% endfor %}

{# В условиях #}
{% if title|length > 50 %}
    {{ title|truncate(50) }}
{% else %}
    {{ title }}
{% endif %}
```

## Производительность

- Фильтры компилируются в нативный PHP код
- Нет накладных расходов на интерпретацию во время выполнения
- Встроенные фильтры оптимизированы для производительности
- Кэширование скомпилированных шаблонов работает с фильтрами

## Рекомендации

1. **Используйте фильтры для форматирования** вместо логики в PHP
2. **Цепочки фильтров** удобны для сложных преобразований
3. **Пользовательские фильтры** помогают избежать дублирования кода
4. **Фильтр `default`** полезен для опциональных данных
5. **Всегда экранируйте пользовательский ввод** (используйте `{{ }}` вместо `{! !}`)
