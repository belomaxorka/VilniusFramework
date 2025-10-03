# 🎉 Улучшения интеграции Vite

## Что было сделано

### ✅ Проблема решена

**До:**
- Vite dev server URL был хардкоден как `http://localhost:5173`
- Невозможно было использовать с OSPanel на локальных доменах
- HMR не работал при использовании разных хостов для PHP и Vite
- Нет возможности тестировать на мобильных устройствах

**После:**
- ✨ Настраиваемый URL через `.env` файл
- ✨ Полная поддержка OSPanel и локальных доменов
- ✨ HMR работает корректно на любых хостах
- ✨ Поддержка тестирования с мобильных устройств в локальной сети
- ✨ Автоматическое создание/удаление `public/hot` файла

---

## 📁 Новые файлы

### 1. `config/vite.php` — Конфигурация Vite
```php
return [
    'dev_server_url' => env('VITE_DEV_SERVER_URL', 'http://localhost:5173'),
    'manifest_path' => 'public/build/.vite/manifest.json',
    'hot_file' => 'public/hot',
    'build_path' => '/build',
    'entries' => [
        'app' => 'resources/js/app.js',
    ],
];
```

### 2. `docs/ViteSetup.md` — Подробная настройка для разных окружений
- Настройка для OSPanel
- Настройка для мобильной разработки
- Troubleshooting
- Примеры конфигурации

### 3. `VITE_QUICKSTART.md` — Быстрый старт
Пошаговая инструкция для немедленного начала работы с OSPanel.

### 4. `tests/Unit/Core/Helpers/ViteHelpersTest.php` — Тесты
Тесты для всех Vite хелперов.

---

## 🔧 Обновленные файлы

### 1. `core/helpers/app/vite.php`
**Новые функции:**
- ✨ `vite_config()` — получить значение из конфигурации
- ✨ `vite_dev_server_url()` — получить URL dev сервера

**Улучшенные функции:**
- `vite()` — теперь использует настраиваемый URL
- `vite_asset()` — поддержка кастомных entry points
- `vite_is_dev_mode()` — использует настраиваемый путь к hot файлу

### 2. `vite.config.js`
**Добавлено:**
- `host: '0.0.0.0'` — слушать на всех интерфейсах
- Плагин для автоматического создания/удаления `public/hot` файла
- Улучшенная обработка сигналов SIGINT/SIGTERM

### 3. `docs/Vite.md`
**Обновлено:**
- Добавлена секция конфигурации `config/vite.php`
- Документация новых хелперов
- Ссылка на `ViteSetup.md`
- Улучшенный раздел Troubleshooting

### 4. `docs/Helpers.md`
**Добавлено:**
- Документация `vite_config()`
- Документация `vite_dev_server_url()`
- Документация `vite_is_dev_mode()`
- Примеры настройки через `.env`

---

## 🚀 Как использовать

### Быстрый старт для OSPanel

#### 1. Создайте `.env` файл:
```env
VITE_DEV_SERVER_URL=http://torrentpier.loc:5173
```
**Замените `torrentpier.loc` на ваш домен!**

#### 2. Обновите `vite.config.js`:
```javascript
server: {
  cors: true,
  strictPort: true,
  port: 5173,
  host: '0.0.0.0',
  hmr: {
    host: 'torrentpier.loc', // ← Ваш домен
  },
},
```

#### 3. Запустите:
```bash
npm run dev
```

#### 4. Готово! ✅
Откройте `http://torrentpier.loc` — HMR работает!

---

## 📚 Документация

- **Быстрый старт:** [VITE_QUICKSTART.md](VITE_QUICKSTART.md)
- **Детальная настройка:** [docs/ViteSetup.md](docs/ViteSetup.md)
- **Полная документация:** [docs/Vite.md](docs/Vite.md)
- **API хелперов:** [docs/Helpers.md](docs/Helpers.md)

---

## 🎯 Примеры использования

### Стандартный localhost
```env
VITE_DEV_SERVER_URL=http://localhost:5173
```

### OSPanel
```env
VITE_DEV_SERVER_URL=http://torrentpier.loc:5173
```

### Мобильная разработка
```env
VITE_DEV_SERVER_URL=http://192.168.1.100:5173
```

---

## 🧪 Тестирование

Запустите тесты:
```bash
php vendor/bin/pest tests/Unit/Core/Helpers/ViteHelpersTest.php
```

Или все тесты:
```bash
php vendor/bin/pest
```

---

## 🔍 Технические детали

### Как работает определение режима

1. **Development режим:**
   - Vite dev server создает файл `public/hot` при запуске
   - `vite_is_dev_mode()` проверяет наличие этого файла
   - Если файл существует → используется dev server URL из конфигурации

2. **Production режим:**
   - Файл `public/hot` отсутствует
   - `vite()` читает `manifest.json` и генерирует теги со сборкой

### Автоматическое управление hot файлом

Плагин в `vite.config.js` автоматически:
- ✅ Создает `public/hot` при старте dev сервера
- ✅ Удаляет `public/hot` при остановке (Ctrl+C)
- ✅ Обрабатывает SIGINT и SIGTERM сигналы

---

## 🎨 Best Practices

1. ✅ Используйте `.env` для настройки URL (не коммитьте его в git)
2. ✅ Создайте `.env.example` с примерами
3. ✅ Убедитесь что `hmr.host` в `vite.config.js` совпадает с доменом PHP
4. ✅ Используйте `host: '0.0.0.0'` для доступа с других устройств
5. ✅ Тестируйте production сборку перед деплоем

---

## 📝 Чеклист миграции

Если вы обновляете существующий проект:

- [ ] Создать `config/vite.php`
- [ ] Создать `.env` с `VITE_DEV_SERVER_URL`
- [ ] Обновить `vite.config.js` (добавить плагин и `host: '0.0.0.0'`)
- [ ] Обновить `core/helpers/app/vite.php`
- [ ] Настроить `hmr.host` в `vite.config.js`
- [ ] Протестировать `npm run dev`
- [ ] Протестировать `npm run build`
- [ ] Проверить работу HMR

---

## 💡 Полезные команды

```bash
# Development с HMR
npm run dev

# Production сборка
npm run build

# Watch режим (без HMR)
npm run watch

# Проверка конфигурации
php -r "require 'config/vite.php'; var_dump(\$config);"

# Проверка хелперов
php -r "require 'core/helpers/app/vite.php'; echo vite_dev_server_url();"
```

---

## 🐛 Troubleshooting

### HMR не подключается

1. Проверьте консоль браузера на ошибки WebSocket
2. Убедитесь что домен в `.env` совпадает с `hmr.host` в `vite.config.js`
3. Проверьте что файл `public/hot` существует при запущенном dev server
4. Перезапустите Vite: `Ctrl+C` → `npm run dev`

### Ассеты не загружаются в production

1. Выполните `npm run build`
2. Проверьте наличие `public/build/.vite/manifest.json`
3. Убедитесь что файл `public/hot` отсутствует

### Порт 5173 занят

Измените порт:
- В `vite.config.js`: `port: 5174`
- В `.env`: `VITE_DEV_SERVER_URL=http://torrentpier.loc:5174`

---

## 🎊 Заключение

Теперь ваша интеграция Vite:
- ✅ Работает с любыми доменами и хостами
- ✅ Полностью настраивается через конфигурацию
- ✅ Поддерживает OSPanel и мобильную разработку
- ✅ HMR работает корректно во всех окружениях
- ✅ Имеет автоматическое управление режимами
- ✅ Полностью задокументирована
- ✅ Покрыта тестами

Приятной разработки! 🚀

