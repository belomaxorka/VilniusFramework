# Устаревшие Helper-функции

## Миграция на прямое использование классов

С целью улучшения читаемости кода и упрощения отладки, рекомендуется использовать методы классов напрямую вместо helper-функций.

## Debug функции

### Устарело → Используйте

| Устаревшая функция | Замена | Описание |
|-------------------|--------|----------|
| `has_debug_output()` | `Debug::hasOutput()` | Проверить наличие debug данных |
| `debug_output()` | `Debug::getOutput()` | Получить debug вывод как строку |
| `debug_flush()` | `Debug::flush()` | Вывести и очистить debug буфер |
| `debug_render_on_page()` | `Debug::setRenderOnPage()` | Включить/выключить рендеринг |
| `render_debug()` | `Debug::getOutput()` | Получить debug вывод |
| `render_debug_toolbar()` | `DebugToolbar::render()` | Отрендерить debug toolbar |

### Примеры миграции

**Было:**
```php
if (has_debug_output()) {
    $output = debug_output();
    echo $output;
}

debug_flush();
debug_render_on_page(false);

$toolbar = render_debug_toolbar();
```

**Стало:**
```php
use Core\Debug;
use Core\DebugToolbar;

if (Debug::hasOutput()) {
    $output = Debug::getOutput();
    echo $output;
}

Debug::flush();
Debug::setRenderOnPage(false);

$toolbar = DebugToolbar::render();
```

## Преимущества прямого использования классов

1. **Явность** - сразу понятно, из какого класса вызывается метод
2. **IDE поддержка** - автодополнение и переход к определению работают лучше
3. **Типизация** - PHPStan/Psalm лучше анализируют статические вызовы
4. **Производительность** - нет overhead на загрузку helper файлов
5. **Простота** - меньше магии, проще понять что происходит

## Статус helper-функций

Helper-функции debug группы помечены как `@deprecated` и могут быть удалены в будущих версиях.
Рекомендуется обновить код для использования классов напрямую.

## Обратная совместимость

Для обеспечения обратной совместимости, helper-функции временно сохранены в `core/helpers/debug/output.php`,
но их использование не рекомендуется в новом коде.

