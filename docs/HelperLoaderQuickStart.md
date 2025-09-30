# HelperLoader Quick Start

Быстрый старт с загрузчиком хелперов.

## 🚀 Самый простой способ

```php
// В bootstrap.php или в начале приложения
\Core\HelperLoader::loadAllHelpers();

// Готово! Все 66 функций доступны
config('app.name');
dump($data);
timer_start('request');
```

Метод `loadAllHelpers()` автоматически найдёт и загрузит все группы из `core/helpers/`.

---

## 🎯 Для больших проектов

```php
// Загрузить только нужные группы
\Core\HelperLoader::loadHelperGroups([
    'app',          // config(), env(), view()
    'environment',  // is_debug(), is_dev()
    'debug',        // dd(), dump(), trace()
]);

// Догрузить по требованию
if (is_dev()) {
    \Core\HelperLoader::loadHelperGroups(['profiler', 'database']);
}
```

---

## 📋 Все способы загрузки

### Одна группа
```php
\Core\HelperLoader::loadHelperGroup('profiler');
```

### Несколько групп
```php
\Core\HelperLoader::loadHelperGroups(['app', 'debug', 'profiler']);
```

### Все группы
```php
\Core\HelperLoader::loadAllHelpers();  // ⭐ Самый простой
```

---

## 🔍 Диагностика

```php
$loader = \Core\HelperLoader::getInstance();

// Что доступно?
$available = $loader->getAvailableGroups();
// ['app', 'environment', 'debug', 'profiler', 'database', 'context']

// Что загружено?
$loaded = $loader->getLoaded();
// ['group:app', 'group:debug']

// Проверить конкретную группу
if ($loader->isLoaded('group:profiler')) {
    echo "✅ Profiler загружен";
}
```

---

## 💡 Рекомендации

| Ситуация | Решение |
|----------|---------|
| Небольшой проект | `loadAllHelpers()` |
| Большой проект | `loadHelperGroups(['app', 'environment', ...])` |
| Прототип | `loadAllHelpers()` |
| Production | Выборочная загрузка + условия |
| Не знаю что нужно | `loadAllHelpers()` сначала, оптимизируйте потом |

---

## 📚 Полная документация

- [HelperLoader API](HelperLoaderAPI.md) - Все методы и примеры
- [Helpers.md](Helpers.md) - Документация по функциям
- [Helper Loading Flow](HelperLoadingFlow.md) - Как это работает

---

## ✅ Чек-лист

- [ ] Выбрал способ загрузки (loadAll vs loadGroups)
- [ ] Добавил загрузку в bootstrap.php
- [ ] Проверил что функции работают
- [ ] (Опционально) Настроил условную загрузку для dev/prod
- [ ] (Опционально) Добавил свои группы хелперов

---

Готово! Теперь все хелперы доступны в вашем приложении 🎉

