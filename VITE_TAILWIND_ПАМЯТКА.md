# Vite + Tailwind CSS - Памятка

## 🚀 Режимы работы

### Development (Разработка)

Запустите dev server для работы с **горячей перезагрузкой (HMR)**:

```bash
npm run dev
```

**Что происходит:**
- Запускается сервер на `http://localhost:5173`
- Создается файл `public/hot`
- CSS компилируется на лету
- Изменения применяются мгновенно без перезагрузки страницы
- Tailwind обрабатывает все используемые классы

**Использование в шаблонах:**
```twig
{! vite('app') !}
```

Генерирует:
```html
<script type="module" src="http://localhost:5173/@vite/client"></script>
<script type="module" src="http://localhost:5173/resources/js/app.js"></script>
```

### Production (Продакшн)

Соберите оптимизированные файлы:

```bash
npm run build
```

**Что происходит:**
- Компилируется минифицированный CSS с только используемыми классами Tailwind
- Создаются хешированные файлы в `public/build/assets/`
- Генерируется `public/build/.vite/manifest.json`
- Удаляется файл `public/hot`

**Использование в шаблонах:**
```twig
{! vite('app') !}
```

Генерирует:
```html
<link rel="stylesheet" href="/build/assets/app-[hash].css">
<script type="module" src="/build/assets/app-[hash].js"></script>
```

## 📝 Частые проблемы

### Проблема: Tailwind стили не применяются

**Причины:**
1. ❌ Dev server не запущен И production файлы устарели
2. ❌ Забыли пересобрать после изменений в шаблонах
3. ❌ Кэш браузера

**Решение:**

Для разработки:
```bash
npm run dev
```

Для production:
```bash
npm run build
```

Очистить кэш шаблонов:
```bash
rm storage/cache/templates/*
```

### Проблема: Новые классы Tailwind не работают

**Причина:** Tailwind не видит новые файлы

**Решение:** Проверьте `tailwind.config.js`:

```javascript
export default {
  content: [
    "./resources/**/*.{html,php,js,twig}",  // ✅ Включены .twig файлы
    "./app/**/*.php",
  ],
  // ...
}
```

После добавления новых путей пересоберите:
```bash
npm run build
```

### Проблема: Функция vite() не работает

**Решение:** Убедитесь что загружен bootstrap:

```php
require_once __DIR__ . '/core/bootstrap.php';
```

Проверьте что хелпер vite загружен:
```php
// Должно быть в bootstrap.php
\Core\HelperLoader::loadHelperGroups(['app']);
```

## 🎯 Рекомендации

### В Development

```bash
# Запустить и оставить работать в фоне
npm run dev
```

- ✅ Быстрая разработка
- ✅ Мгновенные изменения
- ✅ Tailwind компилирует все возможные классы

### Перед коммитом

```bash
# Собрать production версию
npm run build

# Проверить что всё работает
```

### На Production сервере

```bash
# Только сборка, без dev сервера
npm run build
```

## 📂 Структура файлов

```
resources/
├── js/
│   └── app.js          # Точка входа JS (импортирует CSS)
└── css/
    └── app.css         # Tailwind CSS

public/
├── hot                 # Создается когда dev server запущен
└── build/              # Production файлы
    ├── assets/
    │   ├── app-[hash].css
    │   └── app-[hash].js
    └── .vite/
        └── manifest.json
```

## 🔧 Полезные команды

```bash
# Разработка с HMR
npm run dev

# Production сборка
npm run build

# Watch режим (без HMR, только пересборка)
npm run watch

# Очистка кэша
rm storage/cache/templates/*
rm -rf public/build/*

# Проверка что Tailwind работает
npm run build && ls -lh public/build/assets/
```

## ✅ Чеклист перед деплоем

- [ ] `npm run build` выполнен успешно
- [ ] Файлы в `public/build/assets/` созданы
- [ ] `manifest.json` существует
- [ ] Стили применяются на странице
- [ ] Нет файла `public/hot` (dev server не запущен)
- [ ] Кэш шаблонов очищен

## 💡 Итог

**Для разработки:**
```bash
npm run dev  # И оставить работающим
```

**При изменении шаблонов с новыми классами:**
```bash
# Dev server автоматически пересобирает
# Или для production:
npm run build
```

**Если что-то не работает:**
1. Остановите dev server (Ctrl+C)
2. Удалите `public/hot`
3. Пересоберите: `npm run build`
4. Очистите кэш: `rm storage/cache/templates/*`
5. Обновите страницу с очисткой кэша (Ctrl+Shift+R)

