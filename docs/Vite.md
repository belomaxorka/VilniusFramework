# Vite Integration

Интеграция Vite для сборки фронтенд-ассетов (JavaScript, CSS) с поддержкой Hot Module Replacement (HMR) в режиме разработки.

> 💡 **Используете OSPanel или другой локальный домен?** Смотрите [ViteSetup.md](./ViteSetup.md) для настройки dev сервера на разных окружениях.

## Конфигурация

### config/vite.php

Основные настройки интеграции с Vite:

```php
return [
    // URL Vite dev сервера
    'dev_server_url' => env('VITE_DEV_SERVER_URL', 'http://localhost:5173'),
    
    // Путь к манифест-файлу (относительно корня проекта)
    'manifest_path' => 'public/build/.vite/manifest.json',
    
    // Путь к hot-файлу (создается при запуске dev сервера)
    'hot_file' => 'public/hot',
    
    // Базовый путь к собранным ассетам
    'build_path' => '/build',
    
    // Точки входа (должны соответствовать vite.config.js)
    'entries' => [
        'app' => 'resources/js/app.js',
    ],
];
```

**Переменная окружения `VITE_DEV_SERVER_URL`:**
- Устанавливается в `.env` файле
- По умолчанию: `http://localhost:5173`
- Для OSPanel: `http://torrentpier.loc:5173` (замените на ваш домен)
- Для мобильной разработки: `http://192.168.1.100:5173` (ваш IP)

### vite.config.js

```javascript
import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
  root: '.',
  publicDir: false, // Отключаем копирование статических файлов
  build: {
    outDir: 'public/build',
    manifest: true, // Генерируем manifest.json
    emptyOutDir: true, // Очищаем папку перед сборкой
    rollupOptions: {
      input: {
        app: resolve(__dirname, 'resources/js/app.js'),
      },
    },
  },
  server: {
    cors: true,
    strictPort: true,
    port: 5173,
    host: '0.0.0.0', // Слушать на всех интерфейсах
    hmr: {
      host: 'localhost', // Измените на ваш домен для OSPanel
    },
  },
  plugins: [
    {
      name: 'vite-plugin-hot-file',
      configureServer(server) {
        const hotFile = resolve(__dirname, 'public/hot');
        
        // Создаем hot файл при старте
        server.httpServer?.once('listening', () => {
          fs.writeFileSync(hotFile, '');
        });

        // Удаляем при остановке
        process.on('SIGINT', () => {
          if (fs.existsSync(hotFile)) {
            fs.unlinkSync(hotFile);
          }
          process.exit();
        });
      },
    },
  ],
});
```

### package.json

```json
{
  "type": "module",
  "private": true,
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "watch": "vite build --watch"
  }
}
```

## Структура файлов

```
resources/
├── js/
│   └── app.js          # Точка входа JavaScript
└── css/
    └── app.css         # Точка входа CSS (импортируется в app.js)

public/
└── build/              # Скомпилированные ассеты (создается автоматически)
    ├── .vite/
    │   └── manifest.json
    └── assets/
        ├── app-xxx.js
        └── app-xxx.css
```

## Использование в шаблонах

### Основной способ (рекомендуется)

```twig
<!DOCTYPE html>
<html>
<head>
    <title>{{ title }}</title>
    
    <!-- Подключение всех ассетов (CSS + JS) -->
    {! vite('app') !}
</head>
<body>
    <!-- Контент -->
</body>
</html>
```

### Результат в production

```html
<link rel="stylesheet" href="/build/assets/app-ny2b4VcA.css">
<script type="module" src="/build/assets/app-BcnlEAfe.js"></script>
```

### Результат в development (с запущенным `npm run dev`)

```html
<script type="module" src="http://localhost:5173/@vite/client"></script>
<script type="module" src="http://localhost:5173/resources/js/app.js"></script>
```

> **Примечание:** URL может отличаться в зависимости от `VITE_DEV_SERVER_URL` в вашем `.env` файле.

## API хелперов

### `vite_config(string $key, mixed $default = null): mixed`

Получить значение из конфигурации Vite.

**Параметры:**
- `$key` - ключ конфигурации
- `$default` - значение по умолчанию (опционально)

**Возвращает:** значение конфигурации

**Пример:**
```php
$devUrl = vite_config('dev_server_url');
// http://localhost:5173

$hotFile = vite_config('hot_file');
// public/hot
```

---

### `vite_is_dev_mode(): bool`

Проверяет, запущен ли Vite dev server.

**Возвращает:** `true`, если dev server запущен

**Пример:**
```php
if (vite_is_dev_mode()) {
    echo "Development mode";
} else {
    echo "Production mode";
}
```

---

### `vite_dev_server_url(): string`

Получить URL Vite dev сервера из конфигурации.

**Возвращает:** URL без завершающего слэша

**Пример:**
```php
$url = vite_dev_server_url();
// http://localhost:5173
// или http://torrentpier.loc:5173
```

---

### `vite(string $entry = 'app'): string`

Генерирует HTML-теги для подключения CSS и JS.

**Параметры:**
- `$entry` - имя точки входа (по умолчанию `'app'`)

**Возвращает:** HTML-строку с тегами `<link>` и `<script>`

**Пример:**
```php
echo vite('app');
// <link rel="stylesheet" href="/build/assets/app-xxx.css">
// <script type="module" src="/build/assets/app-xxx.js"></script>
```

### `vite_asset(string $entry, string $type = 'js'): ?string`

Получает URL конкретного ассета из манифеста.

**Параметры:**
- `$entry` - имя точки входа
- `$type` - тип ассета (`'js'` или `'css'`)

**Возвращает:** URL ассета или `null`, если не найден

**Пример:**
```php
$cssUrl = vite_asset('app', 'css');
// /build/assets/app-ny2b4VcA.css

$jsUrl = vite_asset('app', 'js');
// /build/assets/app-BcnlEAfe.js
```

### `vite_is_dev_mode(): bool`

Проверяет, запущен ли Vite dev server.

**Возвращает:** `true`, если dev server запущен

**Пример:**
```php
if (vite_is_dev_mode()) {
    echo "Development mode";
} else {
    echo "Production mode";
}
```

## Режимы работы

### Development режим

**Запуск:**
```bash
npm run dev
```

**Особенности:**
- Hot Module Replacement (HMR) - мгновенное обновление без перезагрузки
- Ассеты загружаются с `http://localhost:5173`
- Не требует пересборки при изменениях
- CSS инжектируется через JavaScript

### Production режим

**Сборка:**
```bash
npm run build
```

**Особенности:**
- Минификация JS и CSS
- Хеширование файлов для кеширования
- CSS в отдельных файлах
- Оптимизация размера бандла

### Watch режим

**Запуск:**
```bash
npm run watch
```

**Особенности:**
- Автоматическая пересборка при изменениях
- Без HMR (нужна перезагрузка страницы)
- Полезен для автоматической сборки на продакшене

## Tailwind CSS

### Конфигурация

Tailwind настраивается через `tailwind.config.js`:

```javascript
export default {
  content: [
    "./resources/**/*.{html,tpl,php,js,twig}",
    "./app/**/*.php",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f0f9ff',
          // ... остальные цвета
        },
      },
    },
  },
  plugins: [],
}
```

### Использование в CSS

```css
/* resources/css/app.css */
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Кастомные компоненты */
@layer components {
  .btn-primary {
    @apply bg-primary-600 hover:bg-primary-700 text-white;
  }
}
```

### Импорт в JavaScript

```javascript
// resources/js/app.js
import '../css/app.css'; // Импортируем CSS в JS

// Остальной код приложения
console.log('App loaded');
```

## Почему CSS импортируется в JS?

Это стандартный подход для Vite/Webpack:

1. **Единая точка входа** - Vite анализирует зависимости, начиная с JS
2. **HMR для CSS** - мгновенное обновление стилей без перезагрузки
3. **Автоматическое управление** - хеширование и кеширование
4. **Code splitting** - оптимизация при множественных точках входа

**Важно:** На выходе CSS и JS всё равно остаются **отдельными файлами**!

## Добавление новых точек входа

### 1. Обновите vite.config.js

```javascript
rollupOptions: {
  input: {
    app: resolve(__dirname, 'resources/js/app.js'),
    admin: resolve(__dirname, 'resources/js/admin.js'), // Новая точка
  },
}
```

### 2. Создайте файл

```javascript
// resources/js/admin.js
import '../css/admin.css';

console.log('Admin panel loaded');
```

### 3. Используйте в шаблоне

```twig
{! vite('admin') !}
```

## Troubleshooting

### Ассеты не загружаются

1. Проверьте, что выполнена сборка: `npm run build`
2. Убедитесь, что существует `public/build/.vite/manifest.json`
3. Проверьте права доступа к папке `public/build/`

### HMR не работает

1. Убедитесь, что `npm run dev` запущен
2. Проверьте, что порт 5173 не занят
3. Убедитесь, что `VITE_DEV_SERVER_URL` в `.env` соответствует домену вашего PHP приложения
4. Проверьте, что `hmr.host` в `vite.config.js` совпадает с доменом
5. **Для OSPanel:** используйте тот же домен для PHP и Vite (см. [ViteSetup.md](./ViteSetup.md))

### Tailwind классы не применяются

1. Проверьте, что файлы указаны в `content` в `tailwind.config.js`
2. Убедитесь, что `@tailwind` директивы есть в `app.css`
3. Пересоберите: `npm run build`

### Старые файлы остаются в build/

Используется `emptyOutDir: true` в конфигурации, что очищает папку перед сборкой.

## Best Practices

1. **Не храните в git** папку `public/build/` (добавьте в `.gitignore`)
2. **Используйте production сборку** на продакшене, а не dev server
3. **Версионируйте** `manifest.json` для отслеживания изменений ассетов
4. **Минимизируйте точки входа** - используйте динамические импорты для code splitting
5. **Тестируйте production сборку** перед деплоем
6. **Используйте `.env`** для настройки `VITE_DEV_SERVER_URL` — не коммитьте его в git
7. **Создайте `.env.example`** с примерами настроек для команды

## См. также

- [ViteSetup.md](./ViteSetup.md) — подробная настройка для OSPanel и других окружений
- [Официальная документация Vite](https://vitejs.dev/)

