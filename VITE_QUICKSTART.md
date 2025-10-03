# 🚀 Быстрый старт Vite с OSPanel

## Проблема
Vite dev сервер на `localhost:5173`, а PHP на `torrentpier.loc` → HMR не работает ❌

## Решение в 3 шага

### 1. Создайте `.env` файл
```env
VITE_DEV_SERVER_URL=http://torrentpier.loc:5173
```
**Замените `torrentpier.loc` на ваш домен из OSPanel!**

### 2. Обновите `vite.config.js`

Найдите секцию `hmr`:
```javascript
server: {
  cors: true,
  strictPort: true,
  port: 5173,
  host: '0.0.0.0',
  hmr: {
    host: 'torrentpier.loc', // ← Измените на ваш домен!
  },
},
```

### 3. Запустите Vite
```bash
npm run dev
```

## Готово! ✅

Откройте `http://torrentpier.loc` в браузере — HMR должен работать!

---

## Production сборка

Для production просто:
```bash
npm run build
```

Никаких дополнительных настроек не требуется.

---

## Подробная документация

- **OSPanel и другие окружения:** [docs/ViteSetup.md](docs/ViteSetup.md)
- **Полная документация Vite:** [docs/Vite.md](docs/Vite.md)
- **API хелперов:** [docs/Helpers.md](docs/Helpers.md)

---

## Troubleshooting

**HMR всё еще не работает?**

1. Проверьте консоль браузера на ошибки WebSocket
2. Убедитесь что домен в `.env` совпадает с `vite.config.js`
3. Перезапустите Vite: `Ctrl+C` → `npm run dev`
4. Очистите кеш браузера

**Файл `public/hot` не создается?**

Плагин в `vite.config.js` должен быть настроен (он уже есть в конфиге).

**Порт 5173 занят?**

Измените порт в `vite.config.js` и в `.env`:
```javascript
port: 5174,  // в vite.config.js
```
```env
VITE_DEV_SERVER_URL=http://torrentpier.loc:5174  # в .env
```

