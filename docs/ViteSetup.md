# Настройка Vite для разных окружений

## 🎯 Проблема

По умолчанию Vite dev сервер работает на `http://localhost:5173`, но PHP приложение может работать на другом адресе (например, через OSPanel на `http://torrentpier.loc`). Это создает проблемы:

- ❌ Браузер не может подключиться к Vite dev серверу
- ❌ HMR (Hot Module Replacement) не работает
- ❌ CORS ошибки при загрузке assets

## ✅ Решение

Настройте `VITE_DEV_SERVER_URL` в вашем окружении через `.env` файл или конфигурацию.

---

## 🔧 Настройка для OSPanel (локальный домен)

### Вариант 1: Через .env файл (рекомендуется)

Создайте файл `.env` в корне проекта:

```env
VITE_DEV_SERVER_URL=http://torrentpier.loc:5173
```

**Замените** `torrentpier.loc` на ваш реальный домен из OSPanel!

### Вариант 2: Через config/vite.php

Откройте `config/vite.php` и измените:

```php
'dev_server_url' => 'http://torrentpier.loc:5173',
```

### Обновите vite.config.js

В файле `vite.config.js` измените настройку HMR:

```javascript
server: {
  cors: true,
  strictPort: true,
  port: 5173,
  host: '0.0.0.0', // Слушать на всех интерфейсах
  hmr: {
    host: 'torrentpier.loc', // ← Ваш домен OSPanel!
  },
},
```

### Запуск

1. Запустите Vite dev сервер:
   ```bash
   npm run dev
   ```

2. Откройте ваше приложение в браузере:
   ```
   http://torrentpier.loc
   ```

3. HMR теперь должен работать! ✨

---

## 🌐 Настройка для других устройств (мобильная разработка)

Если вы хотите тестировать на телефоне или планшете в локальной сети:

### .env файл

```env
VITE_DEV_SERVER_URL=http://192.168.1.100:5173
```

**Замените** `192.168.1.100` на ваш реальный IP адрес компьютера!

### vite.config.js

```javascript
server: {
  cors: true,
  strictPort: true,
  port: 5173,
  host: '0.0.0.0', // Обязательно!
  hmr: {
    host: '192.168.1.100', // ← Ваш IP адрес
  },
},
```

### Как узнать свой IP адрес

**Windows:**
```bash
ipconfig
```
Ищите строку "IPv4 адрес" в разделе вашего активного подключения.

**Linux/Mac:**
```bash
ifconfig
```
или
```bash
ip addr show
```

---

## 💻 Стандартная настройка (localhost)

Если вы используете стандартный `http://localhost` для PHP:

### .env
```env
# Можно не указывать, используется по умолчанию
VITE_DEV_SERVER_URL=http://localhost:5173
```

### vite.config.js
```javascript
server: {
  cors: true,
  strictPort: true,
  port: 5173,
  hmr: {
    host: 'localhost',
  },
},
```

---

## 🚀 Production сборка

Для production окружения настройки не нужны — просто выполните сборку:

```bash
npm run build
```

Это создаст оптимизированные файлы в `public/build/`, которые будут автоматически использоваться вместо dev сервера.

---

## 🔍 Диагностика проблем

### HMR не подключается

**Проверьте консоль браузера:**
- Если видите ошибку подключения к WebSocket → проверьте `hmr.host` в `vite.config.js`
- Если видите CORS ошибки → убедитесь что `cors: true` в `vite.config.js`

**Проверьте файл `public/hot`:**
```bash
# Windows
type public\hot

# Linux/Mac
cat public/hot
```
Если файл не существует → Vite dev сервер не запущен или упал при старте.

### Vite не стартует

**Порт 5173 занят:**
```bash
# Windows
netstat -ano | findstr :5173

# Linux/Mac
lsof -i :5173
```

Закройте процесс или измените порт в `vite.config.js` и `.env`.

### Assets не загружаются в production

**Проверьте наличие manifest:**
```bash
ls -la public/build/.vite/manifest.json
```

Если файла нет:
```bash
npm run build
```

**Проверьте права доступа:**
```bash
chmod -R 755 public/build
```

---

## 📝 Пример полной настройки для OSPanel

### 1. `.env`
```env
VITE_DEV_SERVER_URL=http://torrentpier.loc:5173
```

### 2. `vite.config.js`
```javascript
import { defineConfig } from 'vite';
import { resolve } from 'path';
import fs from 'fs';

export default defineConfig({
  root: '.',
  publicDir: false,
  build: {
    outDir: 'public/build',
    manifest: true,
    emptyOutDir: true,
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
    host: '0.0.0.0',
    hmr: {
      host: 'torrentpier.loc', // ← Ваш домен
    },
  },
  plugins: [
    {
      name: 'vite-plugin-hot-file',
      configureServer(server) {
        const hotFile = resolve(__dirname, 'public/hot');
        
        server.httpServer?.once('listening', () => {
          fs.writeFileSync(hotFile, '');
          console.log(`✓ Hot file created`);
        });

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

### 3. Запуск
```bash
npm run dev
```

### 4. Использование в шаблонах
```twig
<!DOCTYPE html>
<html>
<head>
    <title>{{ title }}</title>
    {! vite('app') !}
</head>
<body>
    <h1>Hello, World!</h1>
</body>
</html>
```

---

## 🎨 Как это работает

### Development режим

Когда вы запускаете `npm run dev`:

1. Vite создает файл `public/hot`
2. PHP хелпер `vite()` определяет что это dev режим
3. Генерируется HTML:
   ```html
   <script type="module" src="http://torrentpier.loc:5173/@vite/client"></script>
   <script type="module" src="http://torrentpier.loc:5173/resources/js/app.js"></script>
   ```
4. Браузер подключается к Vite dev серверу через WebSocket для HMR

### Production режим

После `npm run build`:

1. Vite компилирует assets в `public/build/`
2. Создается `manifest.json` с хешированными именами файлов
3. Файл `public/hot` удаляется (или отсутствует)
4. PHP хелпер `vite()` читает manifest и генерирует:
   ```html
   <link rel="stylesheet" href="/build/assets/app-ny2b4VcA.css">
   <script type="module" src="/build/assets/app-BcnlEAfe.js"></script>
   ```

---

## 🎯 Best Practices

1. **Используйте .env** для настройки dev server URL — это позволяет каждому разработчику использовать свои настройки
2. **Не коммитьте .env** в git (добавьте в `.gitignore`)
3. **Создайте .env.example** с примерами настроек
4. **Тестируйте production build** перед деплоем: `npm run build && php -S localhost:8000 -t public`
5. **Используйте `host: '0.0.0.0'`** в vite.config.js для доступа с других устройств

---

## 📚 Дополнительные ресурсы

- [Документация Vite](https://vitejs.dev/config/)
- [Vite Server Options](https://vitejs.dev/config/server-options.html)
- [docs/Vite.md](./Vite.md) — основная документация по интеграции Vite

