# 🔧 Hotfix: Исправление ошибки view() в Response.php

## Проблема

После удаления хелпера `view()` возникла ошибка:
```
Call to undefined function Core\view() in core/Response.php:334
```

## Причина

В методе `Response::view()` использовался удаленный хелпер `view()`:

```php
public function view(string $template, array $data = [], ?int $status = null, array $headers = []): self
{
    $content = view($template, $data); // ❌ Вызов удаленного хелпера
    return $this->html($content, $status, $headers);
}
```

## Решение

Заменен вызов хелпера на прямой вызов `TemplateEngine`:

```php
public function view(string $template, array $data = [], ?int $status = null, array $headers = []): self
{
    $content = \Core\TemplateEngine::getInstance()->render($template, $data); // ✅ Прямой вызов
    return $this->html($content, $status, $headers);
}
```

## Файлы изменены

- ✅ `core/Response.php` - строка 334

## Проверка

- ✅ Линтер-ошибок нет
- ✅ Других вызовов удаленных хелперов в `core/` не найдено
- ✅ Других вызовов удаленных хелперов в `app/` не найдено

## Статус

**ИСПРАВЛЕНО ✅**

Приложение должно работать корректно.

