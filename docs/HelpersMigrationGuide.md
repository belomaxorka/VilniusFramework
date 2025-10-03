# 🚀 Руководство по миграции хелперов

## Что изменилось?

Фреймворк теперь использует **минималистичный подход** к helper-функциям.

### ✅ Осталось только 4 критичных хелпера:

```php
config()  // Конфигурация
env()     // Переменные окружения
__()      // Локализация
vite()    // Vite assets
```

### 🗑️ Все остальное удалено

---

## 📋 Быстрая шпаргалка по миграции

### В контроллерах

```php
// ❌ БЫЛО
function myRoute() {
    $name = request('name');
    return json(['data' => $data]);
}

// ✅ СТАЛО
class MyController extends Controller {
    public function myAction(): Response {
        $name = $this->request->input('name');
        return $this->json(['data' => $data]);
    }
}
```

### View и шаблоны

```php
// ❌ БЫЛО
echo view('welcome', ['name' => 'John']);

// ✅ СТАЛО (в контроллере)
return $this->view('welcome', ['name' => 'John']);
```

### Cache

```php
// ❌ БЫЛО
cache_remember('key', 3600, fn() => expensive());

// ✅ СТАЛО
use Core\Cache;
Cache::remember('key', 3600, fn() => expensive());
```

### Debug

```php
// ❌ БЫЛО
dd($var);
dump($data);

// ✅ СТАЛО
use Core\Debug;
Debug::dd($var);
Debug::dump($data);
```

### Logger

```php
// ❌ БЫЛО
log_info('Message');

// ✅ СТАЛО
use Core\Logger;
Logger::info('Message');
```

### Container

```php
// ❌ БЫЛО
$service = app(MyService::class);

// ✅ СТАЛО
use Core\Container;
$service = Container::getInstance()->make(MyService::class);
```

### CSRF

```php
<!-- ❌ БЫЛО -->
<?= csrf_field() ?>

<!-- ✅ СТАЛО -->
<?php use Core\Session; ?>
<input type="hidden" name="_csrf_token" value="<?= Session::generateCsrfToken() ?>">
```

---

## 🎯 Основные принципы

1. **В контроллерах** → используйте методы базового `Controller`
2. **Для сервисов** → используйте классы напрямую (`Cache::`, `Logger::`, etc.)
3. **Для конфигурации** → используйте `config()` и `env()` хелперы (они остались!)
4. **Для локализации** → используйте `__()` хелпер (он остался!)
5. **Для assets** → используйте `vite()` хелпер (он остался!)

---

## 💡 Почему это хорошо?

✅ **Явность** - понятно откуда берется метод  
✅ **IDE support** - автодополнение работает идеально  
✅ **Type safety** - PHPStan/Psalm работают лучше  
✅ **Производительность** - меньше загрузок файлов  
✅ **Меньше магии** - код проще понять и отладить  

---

## 📖 Подробная документация

- [Helpers.md](Helpers.md) - Документация оставшихся хелперов
- [DeprecatedHelpers.md](DeprecatedHelpers.md) - Полный список удаленных хелперов с примерами миграции

---

**Меньше магии — больше ясности! 🎯**

